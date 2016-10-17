<?php
/**
 * Class Test_Dojo_Updates
 *
 * @package Dojo
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Test_Dojo_Updates extends WP_UnitTestCase {
	private $user;

	public function setUp() {
		// create a new subscriber and set as current user
		$user_id = self::factory()->user->create( array(
			'first_name'    => 'User First',
			'last_name'     => 'User Last',
			'role'          => 'subscriber',
		) );
		wp_set_current_user( $user_id );

		$this->user = wp_get_current_user();
	}

	public function tearDown() {
		self::delete_user( $this->user->ID );
	}

	public function test_payment_due_events() {

		// set current time
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/1/2016' ) );

		$stub = new Stub_Dojo_Updates();

		$dojo_membership = Dojo_Membership::instance();

		$model = $dojo_membership->model();
		$user_id = $this->user->ID;

		// create user account
		$account_id = $model->get_user_account( $user_id );

		// set billing day to the 10th (later this month)
		$model->update_user_account( $user_id, array( 'billing_day' => 10 ) );

		// create a new student
		$student_id = $model->create_student( $user_id, array(
			'first_name'    => 'First',
			'last_name'     => 'Last',
			'alias'         => 'Alias',
			'dob'           => '1/1/1980',
			'notes'         => 'notes',
		) );

		// create pricing structure
		$pricing = new Dojo_Price_Plan( json_encode( array(
			'simple_price'  => '42.5',
		) ) );

		// create a new contract
		$contract_id = $model->create_contract( array(
			'is_active'                     => true,
			'term_months'                   => 6,
			'title'                         => 'Test Contract',
			'new_memberships_only'          => false,
			'continuing_memberships_only'   => false,
			'family_pricing'                => (string) $pricing,
			'cancellation_policy'           => Dojo_Membership::CANCELLATION_NONE,
			'cancellation_days'             => NULL,
			'notes'                         => 'Contract notes',
		) );

		// set up membership
		$membership = $model->get_student_membership( $student_id );
		$model->update_membership( $membership->ID, array(
			'contract_id'   => $contract_id,
			'status'        => Dojo_Membership::MEMBERSHIP_ACTIVE,
			'start_date'    => '1/1/2016',
			'end_date'      => '7/1/2016',
			'next_due_date' => '2/1/2016',
		) );

		// activate student on membership
		$model->update_student( $student_id, array(
			'current_membership_id' => $membership->ID,
		) );

		// trigger an update
		do_action( 'dojo_update' );

		// should not generate updates until next month
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );

		// make time go by, same month but after billing day.
		// should still not cause update since it's the same month
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/15/2016' ) );

		do_action( 'dojo_update' );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );

		// make time go by, next month but before billing day.
		// should still not cause auto update since it's before billing day
		Dojo_WP_Base::set_timestamp_override( strtotime( '2/5/2016' ) );

		do_action( 'dojo_update' );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );

		// make time go by, next month the day before billing day.
		// should only trigger upcoming payment due
		Dojo_WP_Base::set_timestamp_override( strtotime( '2/9/2016' ) );

		// Do two updates since we wouldn't see payment due until after an upcoming payment due triggered
		do_action( 'dojo_update' );
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// repeat with next_due_date later but still in the same month
		$model->update_membership( $membership->ID, array(
			'next_due_date' => '2/28/2016',
		) );

		// clear upcoming payment due received
		$model->update_user_account( $user_id, array(
			'last_upcoming_payment_event' => NULL,
		) );

		// even though we haven't reached next due date should still see event because we are at billing day
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// should not trigger anything since upcoming payment due already received and still before billing day
		do_action( 'dojo_update' );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// clear upcoming payment due received
		$model->update_user_account( $user_id, array(
			'last_upcoming_payment_event' => NULL,
		) );

		// make time go by, next month on billing day.
		// should only trigger upcoming since not recorded yet
		Dojo_WP_Base::set_timestamp_override( strtotime( '2/10/2016' ) );

		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// now should get payment due event since upcoming already received
		do_action( 'dojo_update' );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// both already received this month so shouldn't get either
		do_action( 'dojo_update' );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$stub->handled_events = array();

		// add another student membership starting next month
		$student_id2 = $model->create_student( $user_id, array(
			'first_name'    => 'Second',
			'last_name'     => 'Last',
			'alias'         => 'Alias',
			'dob'           => '1/1/1980',
		) );

		$membership2 = $model->get_student_membership( $student_id2 );
		$model->update_membership( $membership2->ID, array(
			'contract_id'   => $contract_id,
			'status'        => Dojo_Membership::MEMBERSHIP_ACTIVE,
			'start_date'    => '3/5/2016',
			'end_date'      => '9/5/2016',
			'next_due_date' => '4/5/2016',
		) );

		// activate student on membership
		$model->update_student( $student_id2, array(
			'current_membership_id' => $membership2->ID,
		) );

		// move to next month billing day
		Dojo_WP_Base::set_timestamp_override( strtotime( '3/10/2016' ) );

		// verify there is only one membership in the updates
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertEquals( 1, count( $stub->handled_events['dojo_membership_upcoming_payment_due']['memberships_due'] ) );
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$this->assertEquals( 1, count( $stub->handled_events['dojo_membership_payment_due']['memberships_due'] ) );
		$stub->handled_events = array();

		// move to next month billing day
		Dojo_WP_Base::set_timestamp_override( strtotime( '4/10/2016' ) );

		// verify there are two memberships in the updates
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );
		$this->assertEquals( 2, count( $stub->handled_events['dojo_membership_upcoming_payment_due']['memberships_due'] ) );
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_due'] ) );
		$this->assertEquals( 2, count( $stub->handled_events['dojo_membership_payment_due']['memberships_due'] ) );
		$stub->handled_events = array();

		// move to end of first membership
		Dojo_WP_Base::set_timestamp_override( strtotime( '7/1/2016' ) );

		// verify we get first membership ending
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_ended'] ) );
		$this->assertEquals( $membership->ID, $stub->handled_events['dojo_membership_ended'] );
		$stub->handled_events = array();

		// move to end of second membership
		Dojo_WP_Base::set_timestamp_override( strtotime( '9/5/2016' ) );

		// verify we get second membership ending
		do_action( 'dojo_update' );
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_ended'] ) );
		$this->assertEquals( $membership2->ID, $stub->handled_events['dojo_membership_ended'] );
		$stub->handled_events = array();
	}

	public function test_cancellation_policy_anytime() {

		// set current time
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/1/2016' ) );

		$stub = new Stub_Dojo_Updates();

		$dojo_membership = Dojo_Membership::instance();

		$model = $dojo_membership->model();
		$user_id = $this->user->ID;

		// create user account
		$account_id = $model->get_user_account( $user_id );

		// set billing day to the 10th (later this month)
		$model->update_user_account( $user_id, array( 'billing_day' => 10 ) );

		// create a new student
		$student_id = $model->create_student( $user_id, array(
			'first_name'    => 'First',
			'last_name'     => 'Last',
			'alias'         => 'Alias',
			'dob'           => '1/1/1980',
			'notes'         => 'notes',
		) );

		// create pricing structure
		$pricing = new Dojo_Price_Plan( json_encode( array(
			'simple_price'  => '42.5',
		) ) );

		// create a new contract
		$contract_id = $model->create_contract( array(
			'is_active'                     => true,
			'term_months'                   => 6,
			'title'                         => 'Test Contract',
			'new_memberships_only'          => false,
			'continuing_memberships_only'   => false,
			'family_pricing'                => (string) $pricing,
			'cancellation_policy'           => Dojo_Membership::CANCELLATION_ANYTIME,
			'cancellation_days'             => NULL,
			'notes'                         => 'Contract notes',
		) );

		// set up membership
		$membership = $model->get_student_membership( $student_id );
		$model->update_membership( $membership->ID, array(
			'contract_id'   => $contract_id,
			'status'        => Dojo_Membership::MEMBERSHIP_ACTIVE,
			'start_date'    => '1/1/2016',
			'end_date'      => '7/1/2016',
			'next_due_date' => '2/1/2016',
		) );

		// activate student on membership
		$model->update_student( $student_id, array(
			'current_membership_id' => $membership->ID,
		) );

		// move forward a few days
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/5/2016' ) );

		$_POST['membership_id'] = $membership->ID;

		// request cancellation
		$dojo_membership->ajax_cancel_membership( false );

		// verify cancellation request occurred
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_cancel_requested'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_ended'] ) );

		// verify membership status is canceled
		$membership = $model->get_membership( $stub->handled_events['dojo_membership_cancel_requested'] );
		$this->assertEquals( Dojo_Membership::MEMBERSHIP_CANCELED, $membership->status );

		// run updates
		do_action( 'dojo_update' );

		// verify cancellation completed on update
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_ended'] ) );
	}

	public function test_cancellation_policy_days() {

		// set current time
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/1/2016' ) );

		$stub = new Stub_Dojo_Updates();

		$dojo_membership = Dojo_Membership::instance();

		$model = $dojo_membership->model();
		$user_id = $this->user->ID;

		// create user account
		$account_id = $model->get_user_account( $user_id );

		// set billing day to the 10th (later this month)
		$model->update_user_account( $user_id, array( 'billing_day' => 10 ) );

		// create a new student
		$student_id = $model->create_student( $user_id, array(
			'first_name'    => 'First',
			'last_name'     => 'Last',
			'alias'         => 'Alias',
			'dob'           => '1/1/1980',
			'notes'         => 'notes',
		) );

		// create pricing structure
		$pricing = new Dojo_Price_Plan( json_encode( array(
			'simple_price'  => '42.5',
		) ) );

		// create a new contract
		$contract_id = $model->create_contract( array(
			'is_active'                     => true,
			'term_months'                   => 6,
			'title'                         => 'Test Contract',
			'new_memberships_only'          => false,
			'continuing_memberships_only'   => false,
			'family_pricing'                => (string) $pricing,
			'cancellation_policy'           => Dojo_Membership::CANCELLATION_DAYS,
			'cancellation_days'             => 60,
			'notes'                         => 'Contract notes',
		) );

		// set up membership
		$membership = $model->get_student_membership( $student_id );
		$model->update_membership( $membership->ID, array(
			'contract_id'   => $contract_id,
			'status'        => Dojo_Membership::MEMBERSHIP_ACTIVE,
			'start_date'    => '1/1/2016',
			'end_date'      => '7/1/2016',
			'next_due_date' => '2/1/2016',
		) );

		// activate student on membership
		$model->update_student( $student_id, array(
			'current_membership_id' => $membership->ID,
		) );

		// move forward a few days
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/5/2016' ) );

		$_POST['membership_id'] = $membership->ID;

		// request cancellation
		$dojo_membership->ajax_cancel_membership( false );

		// verify cancellation request occurred
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_cancel_requested'] ) );
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_ended'] ) );

		// verify membership status is canceled
		$membership = $model->get_membership( $stub->handled_events['dojo_membership_cancel_requested'] );
		$this->assertEquals( Dojo_Membership::MEMBERSHIP_CANCELED, $membership->status );

		// run updates
		do_action( 'dojo_update' );

		// should not have received cancellation completed on update
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_ended'] ) );

		// move ahead past next month billing day
		Dojo_WP_Base::set_timestamp_override( strtotime( '2/15/2016' ) );

		do_action( 'dojo_update' );

		// should have received upcoming payment due notice
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_upcoming_payment_due'] ) );

		do_action( 'dojo_update' );

		// should have received payment due notice
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_due'] ) );

		// verify status now canceled-due
		$membership = $model->get_membership( $membership->ID );
		$this->assertEquals( Dojo_Membership::MEMBERSHIP_CANCELED_DUE, $membership->status );

		// should still not have received cancellation completed on update
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_ended'] ) );

		// move ahead one day before cancellation policy days (canceled on 1/5/16)
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/5/2016' ) + 59 * 24 * 3600 );

		do_action( 'dojo_update' );

		// should still not have received cancellation completed on update
		$this->assertTrue( ! isset( $stub->handled_events['dojo_membership_ended'] ) );

		// move ahead one second after cancellation policy days (canceled on 1/5/16)
		Dojo_WP_Base::set_timestamp_override( strtotime( '1/5/2016' ) + 60 * 24 * 3600 + 1 );

		do_action( 'dojo_update' );

		// make sure we hit the end of cancellation
		$this->assertTrue( isset( $stub->handled_events['dojo_membership_ended'] ) );
	}
}


class Stub_Dojo_Updates {
	public $handled_events = array();

	public function __construct() {
		add_action( 'dojo_membership_upcoming_payment_due', array( $this, 'handle_dojo_membership_upcoming_payment_due' ) );
		add_action( 'dojo_membership_payment_due', array( $this, 'handle_dojo_membership_payment_due' ) );
		add_action( 'dojo_membership_cancel_requested', array( $this, 'handle_dojo_membership_cancel_requested' ) );
		add_action( 'dojo_membership_ended', array( $this, 'handle_dojo_membership_ended' ) );
	}

	public function handle_dojo_membership_upcoming_payment_due( $info ) {
		$this->handled_events['dojo_membership_upcoming_payment_due'] = $info;
	}

	public function handle_dojo_membership_payment_due( $info ) {
		$this->handled_events['dojo_membership_payment_due'] = $info;
	}

	public function handle_dojo_membership_cancel_requested( $membership_id ) {
		$this->handled_events['dojo_membership_cancel_requested'] = $membership_id;
	}

	public function handle_dojo_membership_ended( $membership_id ) {
		$this->handled_events['dojo_membership_ended'] = $membership_id;
	}
}
