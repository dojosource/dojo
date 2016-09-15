<?php
/**
 * Class Test_Dojo_Membership_Payments
 *
 * @package Dojo
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Test_Dojo_Membership_Payments extends WP_UnitTestCase {
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

    public function test_initial_payment() {

        // set current time
        Dojo_WP_Base::set_timestamp_override( strtotime( '1/1/2016' ) );

        $stub = new Stub_Dojo_Payments();

        $dojo_membership = Dojo_Membership::instance();

        $model = $dojo_membership->model();
        $user_id = $this->user->ID;

        // create user account
        $model->get_user_account( $user_id );

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

        // set up submitted membership
        $membership = $model->get_student_membership( $student_id );
        $model->update_membership( $membership->ID, array(
            'contract_id'   => $contract_id,
            'status'        => Dojo_Membership::MEMBERSHIP_SUBMITTED,
        ) );

        // attach student to membership
        $model->update_student( $student_id, array(
            'current_membership_id' => $membership->ID,
        ) );

        // apply initial payment
        $dojo_membership->apply_membership_payment( $membership->ID, 1 );

        // verify we got payment completed event for this membership
        $this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_completed'] ) );
        $this->assertEquals( $membership->ID, $stub->handled_events['dojo_membership_payment_completed'] );
    }

    public function test_due_payment() {

        // set current time
        Dojo_WP_Base::set_timestamp_override( strtotime( '3/10/2016' ) );

        $stub = new Stub_Dojo_Payments();

        $dojo_membership = Dojo_Membership::instance();

        $model = $dojo_membership->model();
        $user_id = $this->user->ID;

        // create user account
        $model->get_user_account( $user_id );

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

        // set up submitted membership
        $membership = $model->get_student_membership( $student_id );
        $model->update_membership( $membership->ID, array(
            'contract_id'   => $contract_id,
            'status'        => Dojo_Membership::MEMBERSHIP_DUE,
            'start_date'    => '1/1/2016',
            'end_date'      => '7/1/2016',
            'next_due_date' => '2/1/2016',
        ) );

        // attach student to membership
        $model->update_student( $student_id, array(
            'current_membership_id' => $membership->ID,
        ) );

        // apply initial payment
        $dojo_membership->apply_membership_payment( $membership->ID, 1 );

        // verify we got payment completed event for this membership
        $this->assertTrue( isset( $stub->handled_events['dojo_membership_payment_completed'] ) );
        $this->assertEquals( $membership->ID, $stub->handled_events['dojo_membership_payment_completed'] );
    }

}


class Stub_Dojo_Payments {
    public $handled_events = array();

    public function __construct() {
        add_action( 'dojo_membership_payment_completed', array( $this, 'handle_dojo_membership_payment_completed' ) );
    }

    public function handle_dojo_membership_payment_completed( $membership_id ) {
        $this->handled_events['dojo_membership_payment_completed'] = $membership_id;
    }
}
