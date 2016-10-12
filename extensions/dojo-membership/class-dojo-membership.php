<?php
/**
 * Dojo membership extension
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

// non-standard class dependencies
require_once 'class-dojo-rank-types-table.php';
require_once 'class-dojo-programs-table.php';
require_once 'class-dojo-contracts-table.php';
require_once 'class-dojo-documents-table.php';
require_once 'class-dojo-students-table.php';


class Dojo_Membership extends Dojo_Extension {
	private static $instance;

	protected $selected_contract;
	protected $selected_program;
	protected $selected_document;
	protected $selected_rank_type;

	/**** membership statuses ****/

	// default befer and enrollment selected
	const MEMBERSHIP_NA = '';

	// enrollment selected, document signing may be in process
	const MEMBERSHIP_PENDING = 'pending';

	// submitted membership application, invoice created if extension enabled
	const MEMBERSHIP_SUBMITTED = 'submitted';

	// payment received for membership
	const MEMBERSHIP_PAID = 'paid';

	// membership accepted and activated
	const MEMBERSHIP_ACTIVE = 'active';

	// membership frozen until freeze expires or unfrozen manually
	const MEMBERSHIP_FROZEN = 'frozen';

	// payment is due
	const MEMBERSHIP_DUE = 'due';

	// membership has been canceled, won't end until future date
	const MEMBERSHIP_CANCELED = 'canceled';

	// payment is due on canceled membership
	const MEMBERSHIP_CANCELED_DUE = 'canceled-due';

	// membership has ended
	const MEMBERSHIP_ENDED = 'ended';


	/**** Cancellation Policies ****/
	const CANCELLATION_NONE     = 'none';
	const CANCELLATION_ANYTIME  = 'anytime';
	const CANCELLATION_DAYS     = 'days';


	/**** User Dashboard Block Positions ****/

	const USER_DASHBOARD_LEFT  = 'left';
	const USER_DASHBOARD_RIGHT = 'right';


	protected function __construct() {
		parent::__construct( 'Membership' );

		// if we are in debug mode check if we need to do date override
		if ( defined( 'DOJO_DEBUG' ) && DOJO_DEBUG ) {
			$settings = Dojo_Settings::instance();
			$date_override = $settings->get( 'membership_debug_date');
			if ( '' != $date_override ) {
				self::set_timestamp_override( strtotime( $date_override ) );
			}
		}

		$this->register_action_handlers( array (
			'dojo_update',
			'dojo_register_settings',
			'dojo_settings_updated',
			'dojo_register_menus',
			'dojo_add_dashboard_blocks',
			'dojo_invoice_line_item_paid',
		) );

		$this->register_shortcodes( array (
			'dojo_active_member',
			'dojo_inactive_member',
			'dojo_guest',
		) );

		$this->register_membership_page();
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**** Ajax Endpoints ****/

	public function api_force_update() {
		$this->require_admin();

		do_action( 'dojo_update' );

		return 'Updates complete';
	}

	public function api_signup() {
		if ( '1' != $this->get_setting( 'membership_enable_username' ) ) {
			$_POST['username'] = $_POST['email'];
		}
		if ( username_exists( $_POST['username'] ) ) {
			if ( '1' == $this->get_setting( 'membership_enable_username' ) ) {
				echo 'That username is already in use';
			} else {
				// using email for username so change error message
				echo 'That email address is already in use';
			}
		} elseif ( ! is_email( $_POST['email'] ) ) {
			echo 'Invalid email address';
		} elseif ( email_exists( $_POST['email'] ) ) {
			echo 'That email address is already in use';
		} elseif ( $_POST['password'] != $_POST['confirmpassword'] ) {
			echo 'Confirmation password doesn\'t match';
		} else {
			// create new user
			$user_id = wp_insert_user(array(
				'user_login' => $_POST['username'],
				'user_pass'  => $_POST['password'],
				'user_email' => $_POST['email'],
				'first_name' => $_POST['firstname'],
				'last_name'  => $_POST['lastname']
			) );
			update_user_meta( $user_id, 'phone', $_POST['phone'] );

			// login as user
			wp_signon( array(
				'user_login'    => $_POST['username'],
				'user_password' => $_POST['password'],
				'remember'      => false
			) );

			// create account record
			$this->model()->get_user_account( $user_id );

			// notify admin of new user
			try {
				$adminEmail = get_option( 'admin_email' );
				wp_mail( $adminEmail, 'New Member Login '.$this->time(), '
New Member Login Created
Name: '.$_POST['firstname'].' '.$_POST['lastname'].'
Username: '.$_POST['username'].'
Email: '.$_POST['email'].'
Phone: '.$_POST['phone']
				);
			} catch ( Exception $ex ) {
				$this->debug( 'Error sending email ' . $ex->getMessage() );
			}

			echo 'success';
		}
	}

	public function api_check_coupon() {
		$coupon = strtolower( $_POST['coupon'] );

		$response = array( 'result' => 'invalid' );

		if ( 'smith' == $coupon ) {
			$response = array(
				'result'        => 'success',
				'multiplier'    => 0.75,
				'description'   => 'Smith Academy 25% discount'
				);
		}

		echo json_encode($response);
	}

	public function api_get_charge_date() {
		$day = (int) $_POST['charge-day'];
		$chargeDate = $this->get_charge_date( $day );
		echo 'After today\'s payment, automatic payments will begin <strong>' . $this->date( 'm/d/Y', $chargeDate ) . '</strong>.';
	}

	public function api_new_program() {
		$this->require_admin();

		$program_id = $this->model()->create_program( $_POST );

		wp_redirect( admin_url( 'admin.php?page=dojo-programs' ) );
	}

	public function api_save_program() {
		$this->require_admin();

		if ( isset( $_POST['program_id'] ) ) {
			$this->model()->update_program( $_POST['program_id'], $_POST );
		}
		wp_redirect( admin_url( 'admin.php?page=dojo-programs' ) );
	}

	public function api_delete_program() {
		$this->require_admin();

		if ( isset( $_POST['program_id'] ) ) {
			$this->model()->delete_program( $_POST['program_id'] );
		}
		wp_redirect( admin_url( 'admin.php?page=dojo-programs' ) );
	}

	public function api_new_rank_type() {
		$this->require_admin();

		$this->model()->create_rank_type( $_POST );

		wp_redirect( admin_url( 'admin.php?page=dojo-ranks' ) );
	}

	public function api_save_rank_type() {
		$this->require_admin();

		if ( ! isset( $_POST['rank_type_id'] ) ) {
			$rank_type_id = $this->model()->create_rank_type( $_POST );
		} else {
			$rank_type_id = $_POST['rank_type_id'];
			$this->model()->update_rank_type( $rank_type_id, $_POST );
		}

		$ranks = json_decode( str_replace( '\"', '"', $_POST['ranks'] ) );

		foreach ( $ranks as $index => $rank ) {
			if ( isset( $rank->id ) ) {
				$rank_id = $rank->id;
			} else {
				$rank_id = $this->model()->create_rank( $rank_type_id, $rank->title );
			}
			$this->model()->update_rank( $rank_id, array(
				'title'         => $rank->title,
				'order_index'   => $index,
			) );
		}

		wp_redirect( admin_url( 'admin.php?page=dojo-ranks' ) );
	}

	public function api_delete_rank_type() {
		$this->require_admin();

		if ( isset( $_POST['rank_type_id'] ) ) {
			$this->model()->delete_rank_type( $_POST['rank_type_id'] );
		}
		wp_redirect( admin_url( 'admin.php?page=dojo-ranks' ) );
	}

	public function api_save_contract() {
		$this->require_admin();

		$_POST['new_memberships_only']          = 'new' == $_POST['membership_restriction'] ? 1 : 0;
		$_POST['continuing_memberships_only']   = 'continuing' == $_POST['membership_restriction'] ? 1 : 0;

		// serialize price plan details
		$price_plan = new Dojo_Price_Plan();
		$price_plan->handle_post();
		$_POST['family_pricing'] = (string)$price_plan;

		// update contract
		if ( isset( $_POST['contract_id'] ) ) {
			$this->model()->update_contract( $_POST['contract_id'], $_POST );
		} else {
			$_POST['contract_id'] = $this->model()->create_contract( $_POST );
		}

		// set contract programs and documents
		$contract_programs = array();
		$contract_documents = array();
		foreach ( $_POST as $name => $val ) {
			if ( preg_match( '/^program_\d+$/', $name ) ) {
				$contract_programs[] = $val;
			}
			if ( preg_match( '/^document_\d+$/', $name ) ) {
				$contract_documents[] = $val;
			}
		}
		$this->model()->set_contract_programs( $_POST['contract_id'], $contract_programs );
		$this->model()->set_contract_documents( $_POST['contract_id'], $contract_documents );

		wp_redirect( admin_url( 'admin.php?page=dojo-contracts' ) );
	}

	public function api_delete_rank() {
		$this->require_admin();

		if ( isset( $_POST['rank_id'] ) ) {
			$this->model()->delete_rank( $_POST['rank_id'] );
		}
		return 'success';
	}

	public function api_delete_contract() {
		$this->require_admin();

		if ( isset( $_POST['contract_id'] ) ) {
			$this->model()->delete_contract( $_POST['contract_id'] );
		}
		wp_redirect( admin_url( 'admin.php?page=dojo-contracts' ) );
	}

	public function api_save_document() {
		$this->require_admin();

		if ( empty( $_POST ) ) {
			$error = 'Error saving document, please check the size of file you are trying to upload';
			wp_redirect( admin_url( 'admin.php?page=dojo-documents&action=add-new&e=' . urlencode( $error ) ) );
			return;
		}

		$params = array( 'title' => $_POST['title'] );
		$error = false;

		if ( ! isset( $_POST['document_id'] ) ) {
			$doc_id = $this->model()->create_document( $params );
			$is_new = true;
		} else {
			$doc_id = $_POST['document_id'];
			$document = $this->model()->get_document( $doc_id );
			$is_new = false;
		}

		if ( isset( $_FILES['doc'] ) && ! empty( $_FILES['doc']['name'] ) ) {
			$file_info = $_FILES['doc'];
			if ( 0 != $file_info['error'] ) {
				if ( 1 == $file_info['error'] ) {
					$error = 'File exceeds max upload file size of ' . ini_get( 'upload_max_filesize' );
				} else {
					$error = 'Error ' . $file_info['error'] . ' when uploading file.';
				}
				if ( $is_new ) {
					wp_redirect( admin_url( 'admin.php?page=dojo-documents&action=add-new&e=' . urlencode( $error ) ) );
				} else {
					wp_redirect( admin_url( 'admin.php?page=dojo-documents&action=edit&document=' . $doc_id . '&e=' . urlencode( $error ) ) );

				}
				return;
			}

			// docs folder off of plugin root in folder specific to this contract
			$docs_folder = Dojo::instance()->path_of( 'docs/' . $doc_id );
			if ( ! file_exists( $docs_folder ) ) {
				mkdir( $docs_folder, 0777, true );
			}

			// remove old file for this doc if it exists
			if ( ! $is_new && ! empty( $document->filename ) ) {
				unlink( $docs_folder . '/' . $document->filename );
			}

			// move uploaded file to docs folder
			if ( ! move_uploaded_file( $file_info['tmp_name'], $docs_folder . '/' . $file_info['name'] ) ) {
				$error = 'Error moving file after upload';
				wp_redirect( admin_url( 'admin.php?page=dojo-documents&document=' . $doc_id . '&error=' . urlencode( $error ) ) );
				return;
			}

			$params['filename'] = $file_info['name'];
			$this->model()->update_document( $doc_id, $params );
		} elseif( ! $is_new ) {
			// update existing doc title
			$this->model()->update_document( $doc_id, $params );
		}

		wp_redirect( admin_url( 'admin.php?page=dojo-documents' ) );
	}

	public function api_delete_document() {
		$this->require_admin();

		if ( isset( $_POST['document_id'] ) ) {
			$doc = $this->model()->get_document( $_POST['document_id'] );
			if ( null !== $doc ) {
				$folder = Dojo::instance()->path_of( 'docs/'. $doc->ID );
				$file_path =  $folder . '/' . $doc->filename;
				unlink( $file_path );
				rmdir( $folder );
				$this->model()->delete_document( $_POST['document_id'] );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=dojo-documents' ) );
	}

	public function api_save_student( $is_admin ) {
		$user = wp_get_current_user();

		if ( $is_admin ) {
			// make sure user has access to be acting as admin
			$this->require_admin();
		}

		if ( isset( $_POST['student_id'] ) ) {
			if ( $is_admin || $this->model()->is_user_student( $user->ID, $_POST['student_id'] ) ) {
				$this->model()->update_student( $_POST['student_id'], $_POST );
				foreach ( $_POST as $key => $value ) {
					if ( 0 === strpos( $key, 'rank_type_' ) ) {
						$rank_type_id = substr( $key, 10 );
						$this->model()->set_student_rank( $_POST['student_id'], $rank_type_id, $value );
					}
				}
			} else {
				echo 'Invalid id';
			}
		} else {
			$this->model()->create_student( $user->ID, $_POST );
		}

		if ( $is_admin && isset( $_POST['is_admin'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=dojo-students' ) );
		} else {
			wp_redirect( $this->membership_url( 'students' ) );
		}
	}

	public function api_delete_student() {
		$user = wp_get_current_user();

		if ( isset( $_POST['student_id'] ) ) {
			// if student does belong to this user
			if ( $this->model()->is_user_student( $user->ID, $_POST['student_id'] ) ) {
				$this->model()->delete_student( $_POST['student_id'] );
			} else {
				echo 'Invalid id';
			}
		}

		wp_redirect( $this->membership_url( 'students' ) );
	}

	/**
	 * Called every time the drop down select changes on a student to save state and update line items
	 */
	public function api_save_enrollment() {
		$user = wp_get_current_user();

		if ( ! isset( $_POST['refresh_only'] ) ) {
			foreach ( $_POST as $key => $value ) {
				$matches = array();
				if ( preg_match( '/^enrollment_(\d+)$/', $key, $matches ) ) {
					$student_id = $matches[1];
					$contract_id = $value;

					// make empty a null for db
					if ( '' == $contract_id ) {
						$contract_id = null;
					}

					// make sure this student belongs to this account
					if ( $this->model()->is_user_student( $user->ID, $student_id ) ) {

						// get current student membership (new empty one created if none)
						$membership = $this->model()->get_student_membership( $student_id );

						// make sure current membership set on user record
						$this->model()->update_student( $student_id, array( 'current_membership_id' => $membership->ID ) );

						// if membership not submitted yet
						if ( ! $this->is_status_submitted( $membership->status ) ) {

							// set/change the contract id
							$this->model()->update_membership( $membership->ID, array( 'contract_id' => $contract_id ) );
						}
					}
				}
			}
		}

		// return line items to refresh checkout table
		$line_items = $this->get_new_contract_line_items( $user->ID );
		echo json_encode( $line_items );
	}

	/**
	 * Called to start enrollment process. Will move student memberships with selected contracts to pending state.
	 */
	public function api_enrollment_start() {
		$user = wp_get_current_user();

		// apply membership selection
		foreach ( $_POST as $key => $value ) {
			$matches = array();
			if ( preg_match( '/^enrollment_(\d+)$/', $key, $matches ) ) {
				$student_id = $matches[1];
				$contract_id = $value;

				// ignore students without selected contract
				if ( '' == $contract_id ) {
					continue;
				}

				// make sure this student belongs to this user account
				if ( $this->model()->is_user_student( $user->ID, $student_id ) ) {

					// get current student membership (new empty one created if none)
					$membership = $this->model()->get_student_membership( $student_id );

					// make sure current membership set on user record
					$this->model()->update_student( $student_id, array( 'current_membership_id' => $membership->ID ) );

					// make sure we are dealing with a new membership record
					if ( self::MEMBERSHIP_NA == $membership->status ) {

						// update the contract id and change status to pending
						$membership_update = array(
							'contract_id' => $contract_id,
							'status' => self::MEMBERSHIP_PENDING,
						);
						$this->model()->update_membership( $membership->ID, $membership_update );

						// trigger actions that need to follow a switch to pending status
						$membership->contract_id = $contract_id;
						$membership->status = self::MEMBERSHIP_PENDING;
						do_action( 'dojo_membership_enrollment_start', $membership );
					}
				}
			}
		}

		// redirect path can be overridden with a filter
		$url = apply_filters( 'dojo_membership_enrollment_start_redirect', $this->membership_url( 'enroll/apply' ) );
		wp_redirect( $url );
	}

	/**
	 * Called to submit enrollment. Will move student memberships from pending to submitted state.
	 */
	public function api_submit_application() {
		$user = wp_get_current_user();

		$students = array();
		foreach ( $_POST as $name => $val ) {
			if ( preg_match( '/^student_\d+$/', $name ) ) {
				$students[ $val ] = $val;
			}
		}

		$contracts = $this->model()->get_user_contracts( $user->ID );
		$new_students = array();
		foreach ( $contracts as $contract ) {

			// only dealing with pending membership contracts
			if ( self::MEMBERSHIP_PENDING != $contract->membership_status ) {
				continue;
			}

			// only the students passed in
			if ( ! isset( $students[ $contract->student_id ] ) ) {
				continue;
			}

			$student = $this->model()->get_student( $students[ $contract->student_id ] );
			$student->contract = $contract;
			$new_students[] = $student;

			// change state to submitted
			$this->model()->update_membership( $contract->membership_id, array( 'status' => self::MEMBERSHIP_SUBMITTED ) );

			// actions following change to submitted status
			$membership = $this->model()->get_membership( $contract->membership_id );
			do_action( 'dojo_membership_submit_application', $membership );
		}

		// todo - send confirmation to user


		try {
			$adminEmail = get_option( 'admin_email' );
			$message = '
New Member Application

Account: ' . $user->first_name . ' ' . $user->last_name . '
Email: ' . $user->user_email . '
Phone: ' . get_user_meta( $user->ID, 'phone', true ) . '
';
			foreach ( $new_students as $student ) {
				$message .= '
Student Name: ' . $this->student_name( $student ) . '
DOB: ' . $this->date( 'm/d/Y', strtotime( $student->dob ) ) . '
Membership: ' . $student->contract->title . '

';
			}
			$this->log_event( $message );
			wp_mail( $adminEmail, 'New Member Application '.$this->time(), $message );
		} catch ( Exception $ex ) {
			debug( 'Error sending email ' . $ex->getMessage() );
		}

		// redirect path can be overridden with a filter
		$url = apply_filters( 'dojo_membership_submit_application_redirect', $this->membership_url( '?application_submitted' ) );
		wp_redirect( $url );
	}

	public function api_cancel_membership() {
		$user = wp_get_current_user();

		$membership = $this->model()->get_membership( $_POST['membership_id'] );
		if ( ! $membership ) {
			return 'Invalid membership';
		}

		$student = $this->model()->get_student( $membership->student_id );
		if ( ! $student || $user->ID != $student->user_id ) {
			return 'Invalid membership or access denied';
		}

		if ( $membership->ID != $student->current_membership_id ) {
			return 'Membership is not active';
		}

		$contract = $this->model()->get_contract( $membership->contract_id );
		if ( self::CANCELLATION_ANYTIME == $contract->cancellation_policy ) {
			$cancel_execute_date = $this->time( 'mysql' );
		} elseif ( self::CANCELLATION_DAYS == $contract->cancellation_policy ) {
			$cancel_execute_date = $this->date( 'm/d/Y', $this->time() + $contract->cancellation_days * 24 * 3600 );
		} else {
			return 'Cancellation not permitted';
		}

		$this->model()->update_membership( $membership->ID, array(
			'cancel_request_date'   => $this->time( 'mysql' ),
			'cancel_execute_date'   => $cancel_execute_date,
			'status'                => self::MEMBERSHIP_CANCELED,
		));

		$this->log_event( 'Membership canceled for ' . $this->student_name( $student ) );

		do_action( 'dojo_membership_cancel_requested', $membership->ID );

		return 'success';
	}

	public function api_record_payment_received() {
		$this->require_admin();

		$membership = $this->model()->get_student_membership( $_POST['student'] );

		$this->apply_membership_payment( $membership->ID, 1 );

		$student = $this->model()->get_student( $membership->student_id );
		$this->log_event( 'Admin month payment recorded for ' . $this->student_name( $student ) );

		return 'success';
	}

	public function api_approve_application() {
		$this->require_admin();

		$membership = $this->model()->get_student_membership( $_POST['student'] );

		if ( self::MEMBERSHIP_PAID != $membership->status ) {
			return 'Membership not paid';
		}

		// get contract to determine membership end date
		$contract = $this->model()->get_contract( $membership->contract_id );
		if ( ! $contract ) {
			return 'Error finding contract';
		}
		$end_date = $this->get_date_plus_months( $this->date('m/d/Y'), $contract->term_months );

		$this->model()->update_membership( $membership->ID, array(
			'status'    => self::MEMBERSHIP_ACTIVE,
			'end_date'  => $end_date,
		) );

		// get student record to see if this is a first time student
		$student = $this->model()->get_student( $_POST['student'] );
		if ( ! $student->start_date ) {
			// first time student start date is now
			$this->model()->update_student( $student->ID, array(
				'start_date' => $this->time( 'mysql' ),
			) );
		}

		$this->log_event( 'Membership application approved for ' . $this->student_name( $student ) );

		return 'success';
	}

	public function api_save_billing_options() {
		$user = wp_get_current_user();

		$this->set_user_billing_day( $user->ID, (int) $_POST['billing_day'] );

		$this->log_event( 'Billing options updated for account ' . $user->user_login );

		do_action( 'dojo_membership_save_user_billing_options', $user );

		return 'success';
	}


	/**** Action Handlers ****/


	public function handle_dojo_register_settings( $settings ) {
		if ( defined( 'DOJO_DEBUG' ) && DOJO_DEBUG ) {
			$settings->register_section(
				'dojo_member_debug_section',    // section id
				'Debug',                        // section title
				'<a href="javascript:jQuery.post(\'' . $this->ajax( 'force_update' ) . '\', {});">Force Update</a>'
			);

			$settings->register_option( 'dojo_member_debug_section', 'membership_debug_date', 'Override Current Date', $this );
		}

		$settings->register_section(
			'dojo_member_signup_section',   // section id
			'Member Sign Up',               // section title
			''                              // section subtitle
		);

		$settings->register_option( 'dojo_member_signup_section', 'membership_enable_username', 'Enable Username', $this );
		$settings->register_option( 'dojo_member_signup_section', 'membership_slug', 'Membership Url Slug', $this );
		$settings->register_option( 'dojo_member_signup_section', 'membership_signup_header', 'Sign Up Header', $this );
	}

	public function handle_dojo_settings_updated( $settings ) {
		global $wp_rewrite;

		// re-register membership page with possibly updated slug
		$this->register_membership_page();

		// refresh rewrite rules
		$wp_rewrite->flush_rules( false );
	}

	public function handle_dojo_register_menus( $menus ) {
		$menus->add_menu( 'Students', $this );
		$menus->add_menu( 'Programs', $this );
		$menus->add_menu( 'Contracts', $this );
		$menus->add_menu( 'Documents', $this );
		$menus->add_menu( 'Ranks', $this );
	}

	public function handle_dojo_add_dashboard_blocks( $dashboard ) {
		// set up state for views rendered from render_dashboard_block
		$this->submitted_memberships = $this->model()->get_memberships( array( self::MEMBERSHIP_SUBMITTED ), true );
		$this->paid_memberships = $this->model()->get_memberships( array( self::MEMBERSHIP_PAID ), true );
		$this->due_memberships = $this->model()->get_memberships( array( self::MEMBERSHIP_DUE, self::MEMBERSHIP_CANCELED_DUE ), true );

		$dashboard->add_dashboard_block( 'membership-alerts', Dojo_Menu::DASHBOARD_TOP, $this );
	}

	public function handle_dojo_invoice_line_item_paid( $line_item ) {
		$meta = unserialize( $line_item->meta );

		if ( is_array( $meta ) ) {
			if ( isset( $meta['is_membership_payment'] ) && $meta['is_membership_payment'] ) {
				// apply one month of membership payment
				$this->apply_membership_payment( $line_item->membership_id, 1 );

				// log event
				$membership = $this->model()->get_membership( $line_item->membership_id );
				$student = $this->model()->get_student( $membership->student_id );
				$this->log_event( 'Invoice payment applied to one month of membership for ' . $this->student_name( $student ) );
			}
		}
	}

	public function handle_dojo_update() {
		$this->debug( 'Running membership updates' );

		$month = (int) $this->date( 'n' );
		$day   = (int) $this->date( 'j' );
		$year  = (int) $this->date( 'Y' );

		$month_start = strtotime( $month . '/1/' . $year );

		$due_accounts = $this->model()->get_due_accounts();
		$memberships_due = array();
		$accounts = array();

		// normalize accounts and memberships to user_id
		foreach ( $due_accounts as $account ) {
			$memberships_due[ $account->user_id ][] = $account;
			$accounts[ $account->user_id ] = $account;
		}

		foreach ( $accounts as $user_id => $account ) {
			if ( strtotime( $account->last_upcoming_payment_event ) < $month_start ) {
				// update account
				$this->model()->update_user_account( $user_id, array(
					'last_upcoming_payment_event'   => $this->time( 'mysql' )
				) );
				$this->log_event( 'Upcoming payment due', $user_id );

				$info = array(
					'user_id'         => $user_id,
					'memberships_due' => $memberships_due[ $user_id ],
					'line_items'      => $this->get_contract_line_items( $memberships_due[ $user_id ] ),
				);
				do_action( 'dojo_membership_upcoming_payment_due', $info );
			} elseif ( strtotime( $account->last_payment_event ) < $month_start and $day >= $account->billing_day ) {
				// if updates didn't run so these run back to back the elseif will at least separate them by
				// an update interval (currently one hour)

				// change membership status to due or canceled-due
				foreach ( $memberships_due[ $user_id ] as $membership ) {
					if ( self::MEMBERSHIP_ACTIVE == $membership->status ) {
						$this->model()->update_membership( $membership->membership_id, array(
							'status'    => self::MEMBERSHIP_DUE,
						) );
					} elseif ( self::MEMBERSHIP_CANCELED == $membership->status ) {
						 $this->model()->update_membership( $membership->membership_id, array(
							'status'    => self::MEMBERSHIP_CANCELED_DUE,
						) );
					}
				}

				// update account
				$this->model()->update_user_account( $user_id, array(
					'last_payment_event'   => $this->time( 'mysql' )
				) );
				$this->log_event( 'Payment due', $user_id );

				$info = array(
					'user_id'         => $user_id,
					'memberships_due' => $memberships_due[ $user_id ],
					'line_items'      => $this->get_contract_line_items( $memberships_due[ $user_id ] ),
				);
				do_action( 'dojo_membership_payment_due', $info );
			}
		}

		// check for cancellation complete
		$cancellations = $this->model()->get_completed_cancellations();
		foreach ( $cancellations as $cancellation ) {
			$this->model()->update_membership( $cancellation->membership_id, array(
				'status'    => self::MEMBERSHIP_ENDED,
			) );
			$this->model()->update_student( $cancellation->student_id, array( 'current_membership_id' => NULL ) );
			$this->log_event( 'Membership ended for ' . $this->student_name( $cancellation ), $cancellation->user_id );
			do_action( 'dojo_membership_ended', $cancellation->membership_id );
		}

		// check for membership ended
		$ended_memberships = $this->model()->get_ended_memberships();
		foreach ( $ended_memberships as $membership ) {
			 $this->model()->update_membership( $membership->membership_id, array(
				'status'    => self::MEMBERSHIP_ENDED,
			) );
			$this->model()->update_student( $membership->student_id, array( 'current_membership_id' => NULL ) );
			$this->log_event( 'Membership ended for ' . $this->student_name( $membership ), $membership->user_id );
			do_action( 'dojo_membership_ended', $membership->membership_id );
		}

		// todo
		// check for freeze ended

	}


	/**** Render Options ****/

	public function render_option_membership_signup_header() {
		?>
		<p>Header to display above the member sign up form:</p>
		<br />
		<?php

		wp_editor( $this->get_setting( 'membership_signup_header' ), 'membership_signup_header', array(
			'textarea_name' => 'dojo_options[membership_signup_header]',
			'textarea_rows' => 8,
		) );
	}

	public function render_option_membership_enable_username() {
		$this->render_option_checkbox( 'membership_enable_username', 'Ask for a Login Username in addition to name and email address.' );
	}

	public function render_option_membership_slug() {
		$url = $this->membership_url( '' );
		$this->render_option_regular_text( 'membership_slug', 'All membership pages are under <a href="' . esc_attr( $url ) . '">' . esc_html( $url ) . '</a>' );
	}

	public function render_option_membership_debug_date() {
		$this->render_option_regular_text( 'membership_debug_date', 'Set this to simulate time passing on contracts' );
	}


	/**** Render Menus ****/

	public function render_menu_ranks() {
		$this->require_admin();

		// instantiate programs table
		$this->rank_types_table = new Dojo_Rank_Types_Table( $this->model() );

		// render view for current action
		if ( isset ( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'add-new' :
					echo $this->render( 'rank-types-edit' );
					break;

				case 'edit' :
					$this->selected_rank_type = $this->model()->get_rank_type( $_GET['rank_type'] );
					$this->ranks = $this->model()->get_ranks( $_GET['rank_type'] );
					echo $this->render( 'rank-types-edit' );
					break;

				case 'delete' :
					$this->selected_rank_type = $this->model()->get_rank_type( $_GET['rank_type'] );
					echo $this->render( 'rank-types-delete' );
					break;
			}
		} else {
			// main menu view
			echo $this->render( 'rank-types-menu' );
		}
	}

	public function render_menu_programs() {
		$this->require_admin();

		// instantiate programs table
		$this->programs_table = new Dojo_Programs_Table( $this->model() );

		// render view for current action
		if ( isset ( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'add-new' :
					echo $this->render( 'programs-new' );
					break;

				case 'edit' :
					$this->selected_program = $this->model()->get_program( $_GET['program'] );
					$this->program_contracts = $this->model()->get_program_contracts( $_GET['program'] );
					echo $this->render( 'programs-edit' );
					break;

				case 'delete' :
					$this->selected_program = $this->model()->get_program( $_GET['program'] );
					echo $this->render( 'programs-delete' );
					break;
			}
		} else {
			// main menu view
			echo $this->render( 'programs-menu' );
		}
	}

	public function render_menu_contracts() {
		$this->require_admin();

		// instantiate contracts table
		$this->contracts_table = new Dojo_Contracts_Table( $this->model() );

		// render view for current action
		if ( isset ( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'add-new' :
					$this->contract_price_plan = new Dojo_Price_Plan();
					$this->contract_programs = array();
					$this->contract_documents = array();
					$this->programs = $this->model()->get_programs();
					$this->documents = $this->model()->get_documents();
					echo $this->render( 'contracts-edit' );
					break;

				case 'edit' :
					$this->selected_contract = $this->model()->get_contract( $_GET['contract'] );

					$contract_programs = $this->model()->get_contract_programs( $_GET['contract'] );
					$this->contract_programs = array();
					foreach ( $contract_programs as $program ) {
						$this->contract_programs[ $program->ID ] = $program;
					}
					$this->programs = $this->model()->get_programs();

					$contract_documents = $this->model()->get_contract_documents( $_GET['contract'] );
					$this->contract_documents = array();
					foreach ( $contract_documents as $document ) {
						$this->contract_documents[ $document->ID ] = $document;
					}
					$this->documents = $this->model()->get_documents();

					$this->contract_price_plan = new Dojo_Price_Plan( $this->selected_contract->family_pricing );
					echo $this->render( 'contracts-edit' );
					break;

				case 'delete' :
					$this->selected_contract = $this->model()->get_contract( $_GET['contract'] );
					echo $this->render( 'contracts-delete' );
					break;
			}
		} else {
			// main menu view
			echo $this->render( 'contracts-menu' );
		}
	}

	public function render_menu_documents() {
		$this->require_admin();

		// instantiate contracts table
		$this->documents_table = new Dojo_Documents_Table( $this->model() );

		// render view for current action
		if ( isset ( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'add-new' :
					echo $this->render( 'documents-edit' );
					break;

				case 'edit' :
					$this->selected_document = $this->model()->get_document( $_GET['document'] );
					echo $this->render( 'documents-edit' );
					break;

				case 'delete' :
					$this->selected_document = $this->model()->get_document( $_GET['document'] );
					echo $this->render( 'documents-delete' );
					break;
			}
		} else {
			// main menu view
			echo $this->render( 'documents-menu' );
		}
	}

	public function render_menu_students() {
		$this->require_admin();

		// instantiate students table
		$this->students_table = new Dojo_Students_Table( $this->model() );

		// render view for current action
		if ( isset ( $_GET['action'] ) ) {
			switch ( $_GET['action'] ) {
				case 'edit' :
					$this->selected_student = $this->model()->get_student( $_GET['student'] );
					$this->selected_student->contract = $this->model()->get_contract( $this->selected_student->contract_id );
					$this->rank_types = $this->model()->get_rank_types();
					foreach ( $this->rank_types as $index => $rank_type ) {
						$ranks = $this->model()->get_ranks( $rank_type->ID );
						if ( is_array( $ranks ) ) {
							$this->rank_types[ $index ]->ranks = $ranks;
							$student_rank =
							$this->rank_types[ $index ]->student_rank = $this->model()->get_student_rank( $this->selected_student->ID, $rank_type->ID );
						} else {
							$this->rank_types[ $index ]->ranks = array();
						}
					}
					echo $this->render( 'students-edit' );
					break;

				case 'delete' :
					$this->selected_student = $this->model()->get_student( $_GET['student'] );
					echo $this->render( 'students-delete' );
					break;
			}
		} else {
			// main menu view
			echo $this->render( 'students-menu' );
		}
	}

	public function render_dashboard_block( $block_name ) {
		switch ( $block_name ) {
			case 'membership-alerts' :
				echo $this->render( 'dashboard-alerts' );
		}
	}


	/**** Custom Pages ****/

	public function custom_page_membership( $path ) {

		// non logged in users will be redirected to the root membership page
		if ( '' != $path && ! is_user_logged_in() ) {
			wp_redirect( $this->membership_url( '' ) );
			die();
		}

		// action to override or provide new pages under the membership slug
		ob_start();
		do_action( 'dojo_membership_render_page', $path );
		$render = ob_get_clean();
		if ( '' != $render ) {
			echo $render;
			return true;
		}

		switch ( $path ) {
			case '' :
				if ( ! is_user_logged_in() ) {
					echo $this->render( 'logged-out' );
				} else {
					$user_id = wp_get_current_user()->ID;
					$this->students = $this->model()->get_user_students( $user_id );
					$this->is_new = true;
					$this->ready_to_enroll = false;
					$this->has_active_membership = false;
					$this->billing_day = $this->get_user_billing_day( $user_id );
					foreach ( $this->students as $student ) {
						$membership = $this->model()->get_student_membership( $student->ID );
						$student->membership = $membership;
						if ( $this->is_status_submitted( $membership->status ) ) {
							$this->is_new = false;
						} else {
							$this->ready_to_enroll = true;
						}
						if ( $this->is_status_active( $membership->status ) ) {
							$this->has_active_membership = true;
						}
					}

					// initialize notifications
					$this->notifications = array();

					// action to register notifications to display on the membership home page
					// use Dojo_Membership::add_notification() in handler
					do_action( 'dojo_membership_add_notifications', $this );

					// initialize user dashboard blocks
					$this->user_dashboard_blocks = array(
						self::USER_DASHBOARD_LEFT  => array(),
						self::USER_DASHBOARD_RIGHT => array(),
					);

					// action to register blocks on the membership home page
					// use Dojo_Membership::add_user_dashboard_block() in handler
					do_action( 'dojo_membership_add_user_dashboard_blocks', $this );

					echo $this->render( 'user-membership' );
				}
				return true;

			case '/students' :
				$user_id = wp_get_current_user()->ID;
				$this->students = $this->model()->get_user_students( $user_id );
				echo $this->render( 'user-students' );
				return true;

			case '/students/edit' :
				if ( isset( $_GET['student'] ) ) {
					$this->current_student = $this->model()->get_student( $_GET['student'] );
					$this->current_membership = $this->model()->get_student_membership( $_GET['student'] );
					if ( $this->current_membership->contract_id ) {
						$this->current_contract = $this->model()->get_contract( $this->current_membership->contract_id );
						$this->contract_programs = $this->model()->get_contract_programs( $this->current_membership->contract_id );
					}
				}
				echo $this->render( 'user-students-edit' );
				return true;

			case '/students/delete' :
				if ( isset( $_GET['student'] ) ) {
					$this->current_student = $this->model()->get_student( $_GET['student'] );
				}
				echo $this->render( 'user-students-delete' );
				return true;

			case '/enroll' :
				$user_id = wp_get_current_user()->ID;
				$this->students = $this->model()->get_user_students( $user_id );
				$this->unenrolled_students = array();
				foreach ( $this->students as $student ) {
					$membership = $this->model()->get_student_membership( $student->ID );
					$student->membership = $membership;
					if ( ! $this->is_status_submitted( $membership->status ) ) {
						$this->unenrolled_students[] = $student;
					}
				}
				$this->contracts = $this->get_contracts();
				$this->line_items = $this->get_new_contract_line_items( $user_id );
				echo $this->render( 'user-enroll' );
				return true;

			case '/enroll/apply' :
				$user_id = wp_get_current_user()->ID;
				$this->contracts = $this->get_contracts();
				$this->students = $this->model()->get_user_students( $user_id );
				$this->pending_students = array();
				foreach ( $this->students as $student ) {
					$membership = $this->model()->get_student_membership( $student->ID );
					$student->membership = $membership;
					if ( self::MEMBERSHIP_PENDING == $membership->status ) {
						$this->pending_students[] = $student;
					}
				}
				echo $this->render( 'user-enroll-apply' );
				return true;

			case '/enroll/details' :
				if ( isset( $_GET['contract'] ) ) {
					$this->selected_contract = $this->model()->get_contract( $_GET['contract'] );
					$this->contract_programs = $this->model()->get_contract_programs( $_GET['contract'] );
					$this->contract_price_plan = new Dojo_Price_Plan( $this->selected_contract->family_pricing );
				}

				echo $this->render( 'user-enroll-details' );
				return true;

			case '/enroll/student' :
				echo $this->render( 'user-enroll-student' );
				return true;

			case '/enroll/student/freeze' :
				echo $this->render( 'user-enroll-freeze' );
				return true;

			case '/enroll/student/cancel' :
				echo $this->render( 'user-enroll-cancel' );
				return true;

			case '/billing' :
				$user_id = wp_get_current_user()->ID;
				$this->billing_day = $this->get_user_billing_day( $user_id );
				echo $this->render( 'user-billing' );
				return true;
		}

		// page not found
		return false;
	}

	public function custom_page_title_membership( $path ) {
		switch ( $path ) {
			case '/students' :
				$title = 'Students';
				break;

			case '/students/edit' :
				$title = 'Student';
				break;

			case '/students/delete' :
				$title = 'Edit Student';
				break;

			case '/enroll' :
				$title = 'Member Enrollment';
				break;

			case '/enroll/apply' :
				$title = 'Member Application';
				break;

			case '/enroll/details' :
				if ( isset( $_GET['contract'] ) ) {
					$contract = $this->model()->get_contract( $_GET['contract'] );
					if ( null !== $contract ) {
						$title = 'Membership: ' . $contract->title;
					} else {
						$title = 'Membership Details';
					}
				} else {
					$title = 'Membership Details';
				}
				break;

			case '/billing' :
				$title = 'Manage Billing';
				break;

			default:
				$title = 'Members';
		}

		return apply_filters( 'dojo_membership_page_title', $title, $path );
	}

	/**
	 * Use in handler for action dojo_membershi_add_notifications.
	 * Registers a notification to display on the membership home page.
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	public function add_notification( $html ) {
		$this->notifications[] = $html;
	}

	/**
	 * Use in handler for action dojo_membership_add_user_dashboard_blocks.
	 * Registers a block to render on the user dashboard page. Will result in
	 * callback to $owner->render_user_dashboard_block( $block_name ) which
	 * should echo the block content.
	 *
	 * @param string $block_name
	 * @param string $location Use constants Dojo_Membership::USER_DASHBOARD_*
	 * @param object $owner
	 *
	 * @return void
	 */
	public function add_user_dashboard_block( $block_name, $location, $owner ) {
		$this->user_dashboard_blocks[ $location ][ $block_name ] = $owner;
	}


	/**** Short Codes ****/

	public function shortcode_dojo_active_member( $atts ) {
	}

	public function shortcode_dojo_inactive_member( $atts ) {
	}

	public function shortcode_dojo_guest( $atts ) {
	}


	/**** Membership Process ****/

	/**
	 * Applies the given number of months worth of membership payments to a membership.
	 * This effectively advances the payment due date by that number of months and possibly
	 * changes the current membership status.
	 *
	 * @param int $membership_id
	 * @param int $months
	 *
	 * @return void
	 */
	public function apply_membership_payment( $membership_id, $months ) {
		$membership = $this->model()->get_membership( $membership_id );
		if ( ! $membership ) {
			$this->debug( 'Attempt to apply payment to invalid membership id ' . $membership_id );
		}

		// if membership in application state
		if ( self::MEMBERSHIP_SUBMITTED == $membership->status ) {
			// start date is now
			$start_date = $this->date('m/d/Y');
			$next_due = $this->get_date_plus_months( $start_date, $months );
			$this->model()->update_membership( $membership_id, array(
				'start_date'    => $start_date,
				'next_due_date' => $next_due,
				'status'        => self::MEMBERSHIP_PAID,
			) );
		} else {
			// advance next due date
			$next_due = $this->get_date_plus_months( $membership->next_due_date, $months );
			$status = $membership->status;

			// if next due is at least this month and we are not yet to billing day
			// todo
			if ( strtotime( $next_due ) > $this->time() ) {
				if ( self::MEMBERSHIP_DUE == $status ) {
					$status = self::MEMBERSHIP_ACTIVE;
				} elseif ( self::MEMBERSHIP_CANCELED_DUE == $status ) {
					$status = self::MEMBERSHIP_CANCELED;
				}
			}

			// update membership
			$this->model()->update_membership( $membership_id, array(
				'next_due_date' => $next_due,
				'status'        => $status,
			) );
		}

		do_action( 'dojo_membership_payment_completed', $membership_id );
	}


	/**** Utility ****/

	public function register_membership_page() {
		$settings = Dojo_Settings::instance();
		$slug = $settings->get( 'membership_slug' );
		if ( '' == $slug ) {
			$slug = 'members';
		}
		$this->register_custom_pages( array (
			'membership' => $slug,
		) );
	}

	/**
	 * Gets membership url with correct slug
	 *
	 * @param string $path Path relative to membership page
	 *
	 * @return string
	 */
	public function membership_url( $path ) {
		$slug = Dojo_Settings::instance()->get( 'membership_slug' );
		if ( '' == $slug ) {
			$slug = 'members';
		}
		return site_url( $slug . '/' . $path );
	}

	/**
	 * Get human readable description of membership status
	 *
	 * @param const $status
	 *
	 * @return string
	 */
	public function describe_status( $status ) {
		switch ( $status ) {
			case self::MEMBERSHIP_NA            : return 'Membership not started';
			case self::MEMBERSHIP_PENDING       : return 'Membership application not complete';
			case self::MEMBERSHIP_SUBMITTED     : return 'Membership application submitted';
			case self::MEMBERSHIP_PAID          : return 'Payment received, waiting for approval';
			case self::MEMBERSHIP_ACTIVE        : return 'Membership is active';
			case self::MEMBERSHIP_FROZEN        : return 'Membership is frozen';
			case self::MEMBERSHIP_DUE           : return 'Payment is past due';
			case self::MEMBERSHIP_CANCELED      : return 'Membership canceled but still active';
			case self::MEMBERSHIP_CANCELED_DUE  : return 'Payment is past due';
			case self::MEMBERSHIP_ENDED         : return 'Membership ended';
			default                             : return 'Membership status unknown';
		}
	}

	/**
	 * Get human readable description of cancellation policy
	 *
	 * @param object $contract
	 *
	 * @return string
	 */
	public function describe_cancellation_policy( $contract ) {
		switch ( $contract->cancellation_policy ) {
			case self::CANCELLATION_ANYTIME :
				return 'Cancel at any time.';

			case self::CANCELLATION_DAYS :
				return $contract->cancellation_days . ' days notice required to cancel.';

			case self::CANCELLATION_NONE :
				return 'No cancellations';
		}

		return 'Cancellation policy not set.';
	}

	/**
	 * Check if given status is active in the sense that the student may continue to participate
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function is_status_active( $status ) {
		return
			self::MEMBERSHIP_ACTIVE         == $status ||
			self::MEMBERSHIP_DUE            == $status ||
			self::MEMBERSHIP_CANCELED       == $status ||
			self::MEMBERSHIP_CANCELED_DUE   == $status
			;
	}

	/**
	 * Check if status is in a canceled state
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function is_status_canceled( $status ) {
		return
			self::MEMBERSHIP_CANCELED       == $status ||
			self::MEMBERSHIP_CANCELED_DUE   == $status
			;
	}

	/**
	 * Check if status has at least reached the submitted stage. Could be anything past submitted.
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function is_status_submitted( $status ) {
		return
			self::MEMBERSHIP_NA         != $status &&
			self::MEMBERSHIP_PENDING    != $status &&
			self::MEMBERSHIP_ENDED      != $status
			;
	}

	/**
	 * Get all contracts, indexed by id, parsed pricing plan and including contract document records
	 *
	 * @return array
	 */
	public function get_contracts() {
		$contracts = $this->model()->get_contracts();
		$response = array();
		// index contracts by id
		foreach ( $contracts as $contract ) {
			$response[ $contract->ID ] = $contract;
			$response[ $contract->ID ]->documents = $this->model()->get_contract_documents( $contract->ID );
			$response[ $contract->ID ]->pricing = new Dojo_Price_Plan( $contract->family_pricing );
		}
		return $response;
	}

	/**
	 * Gets students for current user
	 *
	 * @return array( object )
	 */
	public function get_current_students() {
		$user = wp_get_current_user();
		return $this->model()->get_user_students( $user->ID );
	}

	/**
	 * Sorts students by contract price in descending order. Student records in response
	 * will include contract and applicable contract_price based on sort order
	 *
	 * @param int $student_contracts Array( array( family_pricing, student_id ) )
	 *
	 * @return array ( student )
	 */
	public function sort_student_contracts( $student_contracts )
	{
		// parse pricing for each contract
		foreach ( $student_contracts as $contract ) {
			$contract->pricing = new Dojo_Price_Plan( $contract->family_pricing );
		}

		$results = array();
		$person = 1;
		while ( count( $student_contracts ) > 0 ) {
			$max = null;
			foreach ( $student_contracts as $index => $contract ) {
				$price = $contract->pricing->get_price( $person );
				if ( null === $max || $price > $max ) {
					$max = $price;
					$max_index = $index;
				}
			}
			$contract = $student_contracts[ $max_index ];
			$student = $this->model()->get_student( $contract->student_id );
			$student->contract = $contract;
			$student->contract_price = $max;
			$results[] = $student;
			$person ++;
			unset( $student_contracts[ $max_index] );
		}
		return $results;
	}

	/**
	 * Get line items for students with selected contracts that haven't yet made first payment on their contracts.
	 *
	 * @param int $user_id
	 *
	 * @return array Line items
	 */
	public function get_new_contract_line_items( $user_id ) {
		$contracts = $this->model()->get_user_contracts( $user_id );
		$students = $this->sort_student_contracts( $contracts );
		$line_items = array();
		foreach ( $students as $student ) {
			// if status not yet paid
			if ( null !== array_search( $student->contract->membership_status, array(
				self::MEMBERSHIP_NA,
				self::MEMBERSHIP_PENDING,
				self::MEMBERSHIP_SUBMITTED,
			) ) ) {
				$line_items[] = array(
					'student_id'        => $student->ID,
					'membership_id'     => $student->contract->membership_id,
					'membership_status' => $student->contract->membership_status,
					'description'       => $student->first_name . ' ' . $student->last_name . ' - ' . $student->contract->title,
					'amount_cents'      => (int) ( $student->contract_price * 100 ),
				);
			}
		}

		return $line_items;
	}

	/**
	 * Get line items for given contracts. Requires family_pricing, student_id, membership_id, membership_status and title on each record.
	 *
	 * @param $contracts
	 *
	 * @return array
	 */
	public function get_contract_line_items( $contracts ) {
		$students = $this->sort_student_contracts( $contracts );
		$line_items = array();
		foreach ( $students as $student ) {
			$line_items[] = array(
				'student_id'        => $student->ID,
				'membership_id'     => $student->contract->membership_id,
				'membership_status' => $student->contract->membership_status,
				'description'       => $student->first_name . ' ' . $student->last_name . ' - ' . $student->contract->title,
				'amount_cents'      => (int) ( $student->contract_price * 100 ),
			);
		}

		return $line_items;
	}

	public function student_name( $record ) {
		if ( is_object( $record) ) {
			$record = array(
				'first_name'    => $record->first_name,
				'last_name'     => $record->last_name,
				'alias'         => $record->alias,
			);
		}
		$name = $record['first_name'] . ' ' . $record['last_name'];
		if ( ! empty( $record['alias'] ) && $record['alias'] != $record['first_name'] ) {
			$name .= ' (' . $record['alias'] . ')';
		}
		return $name;
	}

	/**
	 * Gets day of month user is billed on recurring payments
	 *
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function get_user_billing_day( $user_id ) {
		$account = $this->model()->get_user_account( $user_id );
		return (int) $account->billing_day;
	}

	/**
	 * Sets day of month user is billed on recurring payments
	 *
	 * @param int $user_id
	 * @param int $billing_day
	 *
	 * @return void
	 */
	public function set_user_billing_day( $user_id, $billing_day ) {
		$this->model()->update_user_account( $user_id, array( 'billing_day' => $billing_day ) );
	}

	/**
	 * Adds given number of months to a date. Truncates day of month instead of rolling to next month.
	 *
	 * @param string $date Any format that strtotime can handle
	 * @param int $months
	 *
	 * @return string Formatted as mm/dd/yyyy
	 */
	public function get_date_plus_months( $date, $months ) {
		$time  = strtotime( $date );
		$month = (int) $this->date( 'n', $time );
		$day   = (int) $this->date( 'j', $time );
		$year  = (int) $this->date( 'Y', $time );

		// apply month adjustment
		$month += $months;

		// apply to year
		while ( $month > 12 ) {
			$year ++;
			$month -= 12;
		}

		// days in each month
		$month_days = array(
			1  => 31,
			2  => ( 0 == $year % 4 ) ? 29 : 28,
			3  => 31,
			4  => 30,
			5  => 31,
			6  => 30,
			7  => 31,
			8  => 31,
			9  => 30,
			10 => 31,
			11 => 30,
			12 => 31
		);

		// truncate day if it doesn't fit in this month
		if ( $day > $month_days[ $month ] ) {
			$day = $month_days[ $month ];
		}

		// return formatted with zero padding
		return $this->date( 'm/d/Y', strtotime( $month . '/' . $day . '/' .$year ) );
	}

	public function get_charge_date( $day )
	{
		$month = (int) $this->date( 'n' ) + 1;
		$year  = (int) $this->date( 'Y' );

		if ($month > 12) {
			$month = 1;
			$year++;
		}
		return strtotime( $month . '/' . $day . '/' . $year );

		$thisDay   = (int) $this->date( 'j' );
		$thisMonth = (int) $this->date( 'n' );
		$thisYear  = (int) $this->date( 'Y' );
		$nextYear  = $thisYear;
		$nextMonth = $thisMonth + 1;
		if ( $nextMonth > 12 ) {
			$nextMonth = 1;
			$nextYear = $thisYear + 1;
		}
		if ( $day != $thisDay ) {
			if ( $day < $thisDay ) {
				return strtotime( $nextMonth . '/' . $day . '/' . $nextYear );
			}
			else {
				return strtotime( $thisMonth . '/' . $day . '/' . $thisYear );
			}
		}

		// default to today
		return strtotime( $thisMonth . '/' . $thisDay . '/' . $thisYear );
	}
}


