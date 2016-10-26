<?php
/**
 * Membership extension model
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Dojo_Membership_Model extends Dojo_Model_Base {
	private static $instance;

	// table names
	public $notifications;
	public $accounts;
	public $students;
	public $memberships;
	public $membership_alerts;
	public $rank_types;
	public $ranks;
	public $student_ranks;
	public $programs;
	public $contracts;
	public $contract_programs;
	public $documents;
	public $contract_documents;
	public $membership_contract_documents;

	protected function __construct() {
		global $wpdb;

		$this->notifications                    = $wpdb->prefix . 'dojo_notifications';
		$this->accounts                         = $wpdb->prefix . 'dojo_accounts';
		$this->students                         = $wpdb->prefix . 'dojo_students';
		$this->memberships                      = $wpdb->prefix . 'dojo_memberships';
		$this->membership_alerts                = $wpdb->prefix . 'dojo_membership_alerts';
		$this->rank_types                       = $wpdb->prefix . 'dojo_rank_types';
		$this->ranks                            = $wpdb->prefix . 'dojo_ranks';
		$this->student_ranks                    = $wpdb->prefix . 'dojo_student_ranks';
		$this->programs                         = $wpdb->prefix . 'dojo_programs';
		$this->contracts                        = $wpdb->prefix . 'dojo_contracts';
		$this->contract_programs                = $wpdb->prefix . 'dojo_contract_programs';
		$this->documents                        = $wpdb->prefix . 'dojo_documents';
		$this->contract_documents               = $wpdb->prefix . 'dojo_contract_documents';
		$this->membership_contract_documents    = $wpdb->prefix . 'dojo_membership_contract_documents';
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Create a new notification
	 *
	 * @param int $user_id
	 * @param string $title
	 * @param string $body
	 * @param array $params
	 *
	 * @return int
	 */
	public function create_notification( $user_id, $title, $body, $params = array() ) {
		global $wpdb;

		$insert_params = $this->filter_params( $params, array(
			'type',
			'ref_id',
			'date_sent',
			'date_viewed',
			'date_expired',
		));

		$insert_params['user_id']   = $user_id;
		$insert_params['title']     = $title;
		$insert_params['body']      = $body;

		$wpdb->insert( $this->notifications, $insert_params );
		return $wpdb->insert_id;
	}

	/**
	 * Gets a user's account record. Creates a new empty account if none exists.
	 *
	 * @param int $user_id
	 *
	 * @return object
	 */
	public function get_user_account( $user_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->accounts WHERE user_id = %d", $user_id );
		$record = $wpdb->get_row( $sql );

		if ( null === $record ) {
			$wpdb->insert( $this->accounts, array(
				'user_id'       => $user_id,
				'billing_day'   => 1,
			) );
			$record = $wpdb->get_row( $sql );
		}

		return $record;
	}

	/**
	 * Update a user account
	 *
	 * @param int $user_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_user_account( $user_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'billing_day',
			'last_payment_event',
			'last_upcoming_payment_event',
		) );

		$update_params = $this->fix_dates( $update_params, array(
			'last_payment_event',
			'last_upcoming_payment_event',
		) );

		$where = array( 'user_id' => $user_id );
		$wpdb->update( $this->accounts, $update_params, $where );
	}

	/**
	 * Get accounts that are ready to issue an upcoming-payment event or payment event.
	 * Results may have multiple records for each account representing each student membership.
	 * All account records will be grouped together in the array results.
	 *
	 * @return array
	 */
	public function get_due_accounts() {
		global $wpdb;

		$day   = (int) $this->date( 'j' );
		$month = (int) $this->date( 'n' );
		$year  = (int) $this->date( 'Y' );

		$next_month = $month + 1;
		if ( $next_month > 12 ) {
			$next_month = 1;
			$next_month_year = $year + 1;
		} else {
			$next_month_year = $year;
		}

		$month_start = $this->date( 'Y-m-d 00:00:00', strtotime( $month . '/1/' . $year ) );
		$month_end = $this->date( 'Y-m-d 00:00:00', strtotime( $next_month . '/1/' . $next_month_year ) );

		$sql = $wpdb->prepare( "SELECT a.*, m.*, m.ID as membership_id, m.status as membership_status, c.registration_pricing, c.family_pricing, c.title
			FROM $this->accounts a
			INNER JOIN $this->students s ON a.user_id = s.user_id
			INNER JOIN $this->memberships m ON s.current_membership_id = m.ID
			INNER JOIN $this->contracts c ON m.contract_id = c.ID
			WHERE %d >= a.billing_day - 1
			AND m.next_due_date < %s
			AND m.status IN( 'active', 'due', 'canceled', 'canceled-due' )
			AND ( a.last_payment_event IS NULL OR a.last_upcoming_payment_event < %s OR a.last_payment_event < %s )
			AND s.deleted_date IS NULL
			ORDER BY a.ID
			", $day, $month_end, $month_start, $month_start );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets accounts that are ready to complete their cancellations.
	 * Results may have multiple records for each account representing each student membership.
	 * All account records will be grouped together in the array results.
	 *
	 * @return array
	 */
	public function get_completed_cancellations() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT s.*, m.*, m.ID as membership_id FROM $this->accounts a
			INNER JOIN $this->students s ON a.user_id = s.user_id
			INNER JOIN $this->memberships m ON s.current_membership_id = m.ID
			INNER JOIN $this->contracts c ON m.contract_id = c.ID
			WHERE c.cancellation_policy IN ( 'anytime', 'days' )
				AND m.cancel_request_date IS NOT NULL
				AND m.status IN( 'canceled', 'canceled-due' )
				AND ( c.cancellation_policy <> 'days' OR DATE_ADD(m.cancel_request_date, INTERVAL IFNULL(c.cancellation_days, 0) DAY) < %s )
				AND s.deleted_date IS NULL
				ORDER BY a.ID
			", $this->time( 'mysql') );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets active accounts that have reached their end date.
	 * Results may have multiple records for each account representing each student membership.
	 * All account records will be grouped together in the array results.
	 *
	 * @return array
	 */
	public function get_ended_memberships() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT s.*, m.*, m.ID as membership_id FROM $this->accounts a
			INNER JOIN $this->students s ON a.user_id = s.user_id
			INNER JOIN $this->memberships m ON s.current_membership_id = m.ID
			INNER JOIN $this->contracts c ON m.contract_id = c.ID
			WHERE m.status IN( 'active', 'due', 'canceled', 'canceled-due' )
				AND m.end_date <= %s
				AND s.deleted_date IS NULL
				ORDER BY a.ID
			", $this->time( 'mysql' ) );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets a student's membership record. Creates a new empty membership if none exists.
	 *
	 * @param string $student_id
	 *
	 * @return object Membership record
	 */
	public function get_student_membership( $student_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT m.* FROM $this->memberships m
			INNER JOIN $this->students s on m.ID = s.current_membership_id
			WHERE s.ID = %d", $student_id );
		$record = $wpdb->get_row( $sql );

		if ( null === $record ) {
			$wpdb->insert( $this->memberships, array(
				'status' => Dojo_Membership::MEMBERSHIP_NA,
				'student_id' => $student_id
			) );
			$this->update_student( $student_id, array( 'current_membership_id' => $wpdb->insert_id ) );

			$record = $wpdb->get_row( $sql );
		}

		return $record;
	}

	/**
	 * Set parameters on a membership
	 *
	 * @param int $membership_id
	 * @param array ( param => value ) $params
	 *
	 * @return void
	 */
	public function update_membership( $membership_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'start_date',
			'end_date',
			'next_due_date',
			'contract_id',
			'registration_fee',
			'status',
			'freeze_request_date',
			'freeze_end_date',
			'freeze_request_count',
			'cancel_request_date',
			'cancel_execute_date',
			'student_id',
			'notes',
		) );

		$update_params = $this->fix_dates( $update_params, array(
			'start_date',
			'end_date',
			'next_due_date',
			'freeze_request_date',
			'freeze_end_date',
			'cancel_request_date',
			'cancel_execute_date',
		) );

		$update_params['last_update'] = $this->time( 'mysql' );

		$where = array( 'ID' => $membership_id );
		$wpdb->update( $this->memberships, $update_params, $where );
	}

	/**
	 * Get a membership record
	 *
	 * @param int $membership_id
	 *
	 * @return object
	 */
	public function get_membership( $membership_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->memberships WHERE ID = %d", $membership_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Get all memberships except for memberships with ended status. Or if status filter given
	 * will return all memberships with given statuses.
	 *
	 * @param array $status_filter
	 * @param bool $include_student_details Includes student details in retuned records
	 *
	 * @return mixed
	 */
	public function get_memberships( $status_filter = array(), $include_student_details = false ) {
		global $wpdb;

		$sql = "SELECT m.*";
		if ( $include_student_details ) {
			$sql .= ", s.*";
		}

		$sql .= " FROM $this->memberships m INNER JOIN $this->students s ON m.student_id = s.ID";
		$sql .= " WHERE s.deleted_date IS NULL";

		if ( ! empty( $status_filter ) ) {
			$statuses = $this->esc_sql_quote( $status_filter );
			$sql .= " AND status IN ( $statuses )";
		} else {
			$sql .= $wpdb->prepare( " AND status <> %s", Dojo_Membership::MEMBERSHIP_ENDED );
		}

		return $wpdb->get_results( $sql );
	}

	 /**
	 * Gets a rank type record by id. Returns null if not found.
	 *
	 * @param int $rank_type_id
	 *
	 * @return object Rank type record
	 */
	public function get_rank_type( $rank_type_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->rank_types WHERE ID = %d", $rank_type_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Gets all rank type records
	 *
	 * @param $output_type output type predefined constant
	 *
	 * @return array( object )
	 */
	public function get_rank_types( $output_type = OBJECT ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM $this->rank_types", $output_type );
	}

	/**
	 * Create a new rank type record.
	 *
	 * @param array Insert params. Unsupported params will be filtered out so you can pass in a full $_POST.
	 *
	 * @return int ID of new record.
	 */
	public function create_rank_type( $params ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = $this->filter_params( $params, array(
			'title',
			'description',
		) );

		$wpdb->insert( $this->rank_types, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a rank type record
	 *
	 * @param int $rank_type_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_rank_type( $rank_type_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'title',
			'description',
		) );

		$where = array( 'ID' => $rank_type_id );
		$wpdb->update( $this->rank_types, $update_params, $where );
	}

	/**
	 * Delete a rank type record. Will also delete associated ranks and student rank records.
	 *
	 * @param int $rank_type_id
	 *
	 * @return void
	 */
	public function delete_rank_type( $rank_type_id ) {
		global $wpdb;

		$ranks = $this->get_ranks( $rank_type_id );
		foreach ( $ranks as $rank ) {
			$this->delete_rank( $rank->ID );
		}

		$where = array( 'ID' => $rank_type_id );
		$wpdb->delete( $this->rank_types, $where );
	}

	 /**
	  * Create a new rank record.
	  *
	  * @param int $rank_type_id
	  * @param string $title
	  *
	  * @return int ID of new record.
	  */
	public function create_rank( $rank_type_id, $title ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = array(
			'rank_type_id'  => $rank_type_id,
			'title'         => $title,
		);

		$wpdb->insert( $this->ranks, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a rank record
	 *
	 * @param int $rank_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_rank( $rank_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'title',
			'order_index',
		) );

		$where = array( 'ID' => $rank_id );
		$wpdb->update( $this->ranks, $update_params, $where );
	}

	/**
	 * Delete a rank record. Will also delete associated student ranks.
	 *
	 * @param int $rank_id
	 *
	 * @return void
	 */
	public function delete_rank( $rank_id ) {
		global $wpdb;

		$where = array( 'ID' => $rank_id );
		$wpdb->delete( $this->ranks, $where );

		$where = array( 'rank_id' => $rank_id );
		$wpdb->delete( $this->student_ranks, $where );
	}

	/**
	 * Sets student rank for a given rank type
	 *
	 * @param $student_id
	 * @param $rank_type_id
	 * @param $rank_id
	 */
	public function set_student_rank( $student_id, $rank_type_id, $rank_id ) {
		global $wpdb;

		// get current rank which will create a db entry for rank type if none exists
		$old_rank = $this->get_student_rank( $student_id, $rank_type_id );

		// update student rank
		$update_params = array(
			'rank_id'   => $rank_id,
		);
		$where = array(
			'student_id'    => $student_id,
			'rank_id'       => $old_rank->ID,
		);

		$wpdb->update( $this->student_ranks, $update_params, $where );
	}

	/**
	 * Get rank record for a given student and rank type.
	 *
	 * @param $student_id
	 * @param $rank_type_id
	 * @return object
	 */
	public function get_student_rank( $student_id, $rank_type_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT r.* FROM $this->rank_types rt
			INNER JOIN $this->ranks r ON rt.ID = r.rank_type_id
			INNER JOIN $this->student_ranks sr ON r.ID = sr.rank_id
			WHERE sr.student_id = %d AND rt.ID = %d",
			$student_id, $rank_type_id
		);
		$record = $wpdb->get_row( $sql );

		if ( NULL === $record ) {

			// get default rank
			$default_rank_id = $this->get_default_rank( $rank_type_id );

			// create record with default rank
			$params = array(
				'student_id'    => $student_id,
				'rank_id'       => $default_rank_id,
			);
			$wpdb->insert( $this->student_ranks, $params );

			$sql = $wpdb->prepare( "SELECT * FROM $this->ranks WHERE ID = %d", $default_rank_id );
			$record = $wpdb->get_row( $sql );
		}

		return $record;
	}

	/**
	 * Gets the default rank id for a given rank type.
	 * Will set default to lowest rank if not already set.
	 * On error will return null.
	 *
	 * @param $rank_type_id
	 * @return mixed
	 */
	public function get_default_rank( $rank_type_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->rank_types WHERE ID = %d", $rank_type_id );
		$rank_type_record = $wpdb->get_row( $sql );

		if ( NULL == $rank_type_record ) {
			$this->debug( 'Rank type not found' );
			return NULL;
		}

		if ( NULL !== $rank_type_record->default_rank_id ) {
			return $rank_type_record->default_rank_id;
		}

		// no default set so create a default based on first rank in order
		$sql = $wpdb->prepare( "SELECT * FROM $this->ranks
			WHERE rank_type_id = %d
			ORDER BY order_index ASC",
			$rank_type_id
		);
		$start_rank = $wpdb->get_row( $sql );
		if ( NULL == $start_rank ) {
			$this->debug( 'No ranks for rank type ' . $rank_type_id );
			return NULL;
		}
		$default_rank_id = $start_rank->ID;

		// update default on rank type record
		$wpdb->update( $this->rank_types, array( 'default_rank_id' => $default_rank_id ), array( 'ID' => $rank_type_id ) );

		return $default_rank_id;
	}

	/**
	 * Get all ranks for a given rank type in order starting with lowest.
	 *
	 * @param $rank_type_id
	 * @return array( object )
	 */
	public function get_ranks( $rank_type_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->ranks WHERE rank_type_id = %d ORDER BY order_index ASC", $rank_type_id );
		return $wpdb->get_results( $sql );
	}

	/**
	 * Gets a program record by id. Returns null if not found.
	 *
	 * @param int $program_id
	 *
	 * @return object Program record
	 */
	public function get_program( $program_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->programs WHERE ID = %d", $program_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Gets all program records
	 *
	 * @param $output_type output type predefined constant
	 *
	 * @return array( object )
	 */
	public function get_programs( $output_type = OBJECT ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM $this->programs", $output_type );
	}

	/**
	 * Create a new program record.
	 *
	 * @param array Insert params. Unsupported params will be filtered out so you can pass in a full $_POST.
	 *
	 * @return int ID of new record.
	 */
	public function create_program( $params ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = $this->filter_params( $params, array(
			'title',
			'description',
			'min_age',
			'max_age',
			'notes',
		) );

		$wpdb->insert( $this->programs, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a program record
	 *
	 * @param int $program_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_program( $program_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'title',
			'description',
			'min_age',
			'max_age',
			'notes',
		) );

		$where = array( 'ID' => $program_id );
		$wpdb->update( $this->programs, $update_params, $where );
	}

	/**
	 * Delete a program record
	 *
	 * @param int $program_id
	 *
	 * @return void
	 */
	public function delete_program( $program_id ) {
		global $wpdb;

		$where = array( 'ID' => $program_id );
		$wpdb->delete( $this->programs, $where );

		$where = array( 'program_id' => $program_id );
		$wpdb->delete( $this->contract_programs, $where );
	}

	/**
	 * Get all the contracts connected to a give program
	 *
	 * @param int $program_id
	 * @param $output_type output type predefined constant
	 *
	 * @return array
	 */
	public function get_program_contracts( $program_id, $output_type = OBJECT ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT c.* FROM $this->contracts c
			INNER JOIN $this->contract_programs cp ON c.ID = cp.contract_id
			WHERE program_id=%d",
			$program_id );

		return $wpdb->get_results( $sql, $output_type );
	}

	/**
	 * Get all the programs connected to a give contract
	 *
	 * @param int $contract_id
	 * @param $output_type output type predefined constant
	 *
	 * @return array
	 */
	public function get_contract_programs( $contract_id, $output_type = OBJECT ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT p.* FROM $this->programs p
			INNER JOIN $this->contract_programs cp ON p.ID = cp.program_id
			WHERE contract_id=%d",
			$contract_id );

		return $wpdb->get_results( $sql, $output_type );
	}

	/**
	 * Set programs available to a given contract
	 *
	 * @param int $contract_id
	 * @param array $programs
	 *
	 * @return void
	 */
	public function set_contract_programs( $contract_id, $programs ) {
		global $wpdb;

		// first delete current set
		$where = array( 'contract_id' => $contract_id );
		$wpdb->delete( $this->contract_programs, $where );

		// insert all the selected programs
		foreach ( $programs as $program_id ) {
			$wpdb->insert( $this->contract_programs, array(
				'contract_id' => $contract_id,
				'program_id' => $program_id,
			) );
		}
	}

	/**
	 * Create a new contract record.
	 *
	 * @param array $params Insert params. Unsupported params will be filtered out so you can pass in a full $_POST.
	 *
	 * @return int ID of new record.
	 */
	public function create_contract( $params ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = $this->filter_params( $params, array(
			'is_active',
			'term_months',
			'terms_url',
			'title',
			'new_memberships_only',
			'continuing_memberships_only',
			'registration_pricing',
			'family_pricing',
			'cancellation_policy',
			'cancellation_days',
			'notes',
		) );

		$wpdb->insert( $this->contracts, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a contract record
	 *
	 * @param int $contract_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_contract( $contract_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'is_active',
			'term_months',
			'terms_url',
			'title',
			'new_memberships_only',
			'continuing_memberships_only',
			'registration_pricing',
			'family_pricing',
			'cancellation_policy',
			'cancellation_days',
			'notes',
		) );

		$where = array( 'ID' => $contract_id );
		$wpdb->update( $this->contracts, $update_params, $where );
	}

	/**
	 * Delete a contract record
	 *
	 * @param int $contract_id
	 *
	 * @return void
	 */
	public function delete_contract( $contract_id ) {
		global $wpdb;

		$where = array( 'ID' => $contract_id );
		$wpdb->delete( $this->contracts, $where );

		$where = array( 'contract_id' => $contract_id );
		$wpdb->delete( $this->contract_programs, $where );
	}

	/**
	 * Gets all contract records
	 *
	 * @param $output_type output type predefined constant
	 *
	 * @return array( object )
	 */
	public function get_contracts( $output_type = OBJECT ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM $this->contracts", $output_type );
	}

	/**
	 * Gets a contract record by id. Returns null if not found.
	 *
	 * @param int $contract_id
	 *
	 * @return object Contract record
	 */
	public function get_contract( $contract_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->contracts WHERE ID = %d", $contract_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Gets all the current contracts for students attached to the given user. Contracts may not
	 * be active yet.
	 *
	 * @param int $user_id
	 *
	 * @return array ( object )
	 */
	public function get_user_contracts( $user_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT s.ID as student_id, m.ID as membership_id, m.status as membership_status, c.* FROM $this->students s
			INNER JOIN $this->memberships m ON s.current_membership_id = m.ID
			INNER JOIN $this->contracts c on m.contract_id = c.ID
			WHERE s.deleted_date IS NULL AND s.user_id = %d", $user_id );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Create a new document record.
	 *
	 * @param array $params Insert params.
	 *
	 * @return int ID of new record.
	 */
	public function create_document( $params ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = $this->filter_params( $params, array(
			'title',
			'filename',
		) );

		$wpdb->insert( $this->documents, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a document record
	 *
	 * @param int $document_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_document( $document_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'title',
			'filename',
		) );

		$where = array( 'ID' => $document_id );
		$wpdb->update( $this->documents, $update_params, $where );
	}

	/**
	 * Gets all document records
	 *
	 * @param const $output_type
	 *
	 * @return array( object )
	 */
	public function get_documents( $output_type = OBJECT ) {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM $this->documents", $output_type );
	}

	/**
	 * Gets a document record by id. Returns null if not found.
	 *
	 * @param int $document_id
	 *
	 * @return object Document record
	 */
	public function get_document( $document_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->documents WHERE ID = %d", $document_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Delete a document record
	 *
	 * @param int $document_id
	 *
	 * @return void
	 */
	public function delete_document( $document_id ) {
		global $wpdb;

		$where = array( 'ID' => $document_id );
		$wpdb->delete( $this->documents, $where );

		$where = array( 'document_id' => $document_id );
		$wpdb->delete( $this->contract_documents, $where );
	}

	/**
	 * Set documents available to a given contract
	 *
	 * @param int $contract_id
	 * @param array $documents
	 *
	 * @return void
	 */
	public function set_contract_documents( $contract_id, $documents ) {
		global $wpdb;

		// first delete current set
		$where = array( 'contract_id' => $contract_id );
		$wpdb->delete( $this->contract_documents, $where );

		// insert all the selected documents
		foreach ( $documents as $document_id ) {
			$wpdb->insert( $this->contract_documents, array(
				'contract_id' => $contract_id,
				'document_id' => $document_id,
			) );
		}
	}

	/**
	 * Get all the documents connected to a give contract
	 *
	 * @param int $contract_id
	 * @param $output_type output type predefined constant
	 *
	 * @return array
	 */
	public function get_contract_documents( $contract_id, $output_type = OBJECT ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT d.* FROM $this->documents d
			INNER JOIN $this->contract_documents cd ON d.ID = cd.document_id
			WHERE contract_id=%d",
			$contract_id );

		return $wpdb->get_results( $sql, $output_type );
	}

	 /**
	 * Gets all student records
	 *
	 * @param $output_type output type predefined constant
	 *
	 * @return array( output_type )
	 */
	public function get_students( $output_type = OBJECT ) {
		global $wpdb;

		$sql = "SELECT s.*, m.status FROM $this->students s
			INNER JOIN $this->memberships m ON s.current_membership_id = m.ID
			WHERE s.deleted_date IS NULL ORDER BY last_name, first_name";
		return $wpdb->get_results( $sql, $output_type );
	}

	/**
	 * Gets all student records for a given user
	 *
	 * @param int $user_id
	 * @param $output_type output type predefined constant
	 *
	 * @return array( output_type )
	 */
	public function get_user_students( $user_id, $output_type = OBJECT ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT s.*, m.status FROM $this->students s
			LEFT JOIN $this->memberships m ON s.current_membership_id = m.ID
			WHERE s.user_id = %d AND s.deleted_date IS NULL", $user_id );
		return $wpdb->get_results( $sql, $output_type );
	}

	/**
	 * Gets a student record
	 *
	 * @param int $student_id
	 *
	 * @return object Student record
	 */
	public function get_student( $student_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT s.*, m.status, m.next_due_date, m.contract_id FROM $this->students s
			LEFT JOIN $this->memberships m ON s.current_membership_id = m.ID
			WHERE s.ID = %d AND s.deleted_date IS NULL", $student_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Create a new student record for the given user
	 *
	 * @param int $user_id
	 * @param array( field => value ) $params
	 *
	 * @return int Student id
	 */
	public function create_student( $user_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$insert_params = $this->filter_params( $params, array(
			'first_name',
			'last_name',
			'alias',
			'dob',
			'notes',
			'start_date',
			'belt',
		) );

		// make sure dates are mysql format
		$insert_params = $this->fix_dates( $insert_params, array( 'dob', 'start_date' ) );

		$insert_params['user_id'] = $user_id;
		$wpdb->insert( $this->students, $insert_params );

		return $wpdb->insert_id;
	}

	/**
	 * Update a student record
	 *
	 * @param int $student_id
	 * @param array $params
	 *
	 * @return void
	 */
	public function update_student( $student_id, $params ) {
		global $wpdb;

		// filter to only valid params
		$update_params = $this->filter_params( $params, array(
			'first_name',
			'last_name',
			'alias',
			'dob',
			'notes',
			'start_date',
			'current_membership_id',
			'belt',
			'delete_date',
		) );

		// make sure dates are mysql format
		$update_params = $this->fix_dates( $update_params, array( 'dob', 'start_date' ) );

		$where = array( 'ID' => $student_id );
		$wpdb->update( $this->students, $update_params, $where );
	}

	/**
	 * Checks if given student id is attached to given user id
	 *
	 * @param int $user_id
	 * @param int $student_id
	 *
	 * @return bool
	 */
	public function is_user_student( $user_id, $student_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->students WHERE ID = %d AND user_id = %d", $student_id, $user_id );
		$result = $wpdb->get_row( $sql );
		return null !== $result;
	}

	/**
	 * Delete the given student. Student records are not removed from the database, just flagged as deleted
	 * so all past membership records and charges still exist in case of disputes.
	 *
	 * @param int $student_id
	 *
	 * @return void
	 */
	public function delete_student( $student_id ) {
		global $wpdb;

		$params = array(
			'deleted_date'  => $this->time( 'mysql' ),
		);

		$where = array( 'ID' => $student_id );

		$wpdb->update( $this->students, $params, $where );
	}


	/**
	 * Create a new charge on student account. Charge will be associated with the student's membership.
	 *
	 * @param int $student_id
	 * @param string $description
	 * @param int $amount_cents
	 * @param int $on_paid_callback Optional callback to execute when charge is paid
	 *
	 * @return int Charge id
	 */
	public function create_membership_charge( $student_id, $description, $amount_cents, $on_paid_callback = null ) {
		global $wpdb;

		$membership_id = $this->get_student_membership( $student_id )->ID;

		$insert_params = array(
			'charge_date'       => $this->time( 'mysql' ),
			'membership_id'     => $membership_id,
			'student_id'        => $student_id,
			'description'       => $description,
			'amount_cents'      => $amount_cents,
			'on_paid_callback' => $on_paid_callback,
		);

		$wpdb->insert( $this->membership_charges, $insert_params );
		return $wpdb->insert_id;
	}

	/**
	 * Create a new empty invoice for a user
	 *
	 * @param int $user_id
	 *
	 * @return int Invoice id
	 */
	public function create_invoice( $user_id ) {
		global $wpdb;

		$insert_params = array(
			'invoice_date'      => $this->time( 'mysql' ),
			'user_id'           => $user_id,
		);

		$wpdb->insert( $this->user_invoices, $insert_params );
		return $wpdb->insert_id;
	}

	/**
	 * Get an invoice record
	 *
	 * @param int $invoice_id
	 *
	 * @return object Invoice record
	 */
	public function get_invoice( $invoice_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $this->user_invoices WHERE ID = %d", $invoice_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Add a charge to an invoice
	 *
	 * @param int $invoice_id
	 * @param int $membership_charge_id
	 *
	 * @return int insert id
	 */
	public function add_invoice_membership_charge( $invoice_id, $membership_charge_id ) {
		global $wpdb;

		$insert_params = array(
			'invoice_id'            => $invoice_id,
			'membership_charge_id'  => $membership_charge_id,
		);

		$wpdb->insert( $this->invoice_charges, $insert_params );
		return $wpdb->insert_id;
	}

	/**
	 * Gets line items for a given invoice
	 *
	 * @param int $invoice_id
	 * @param $output_type output type predefined constant
	 *
	 * @return mixed
	 */
	public function get_invoice_line_items( $invoice_id, $output_type = OBJECT ) {
		global $wpdb;


		$sql = $wpdb->prepare( "SELECT mc.* FROM $this->invoice_charges ic
			INNER JOIN $this->membership_charges mc ON ic.membership_charge_id = mc.ID
			WHERE ic.invoice_id = %d", $invoice_id );

		return $wpdb->get_results( $sql, $output_type );
	}

	/**
	 * Create a new charge against a user.
	 * Will need to create an invoice later to collect outstanding charges.
	 *
	 * @param int $user_id Wordpress user id
	 * @param string $description
	 * @param int $amount_cents
	 * @param string $notes Optional notes
	 * @param int $student_id Optional association to a specific student
	 * @param int $on_paid_callback Optional registered callback to execute when charge is paid
	 *
	 * @return int Charge id
	 */
	public function create_charge( $user_id, $description, $amount_cents, $notes = '', $student_id = null, $on_paid_callback = null ) {
		global $wpdb;

		$membership_id = $this->get_user_membership( $user_id )->ID;

		$insert_params = array(
			'charge_date' => $this->time( 'mysql' ),
			'membership_id' => $membership_id,
			'description' => $description,
			'amount_cents' => $amount_cents,
			'on_paid_callback' => $on_paid_callback,
			'notes' => $notes
		);

		if ( null !== $student_id ) {
			$insert_params['student_id'] = $student_id;
		}

		$wpdb->insert( $this->membership_charges, $insert_params );
	}
}

