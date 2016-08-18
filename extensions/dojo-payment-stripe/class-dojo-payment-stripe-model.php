<?php
/**
 * Stripe payment extension model
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Payment_Stripe_Model extends Dojo_Model_Base {
    private static $instance;

    // table names
    private $customers;
    private $sources;
    private $charges;
    private $events;

    protected function __construct() {
        global $wpdb;

        $this->customers    = $wpdb->prefix . 'dojo_stripe_customers';
        $this->sources      = $wpdb->prefix . 'dojo_stripe_sources';
        $this->charges      = $wpdb->prefix . 'dojo_stripe_charges';
        $this->events       = $wpdb->prefix . 'dojo_stripe_events';
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Log event received from stripe
     *
     * @param string $event JSON text received from stripe
     * @param bool $processed True if event was routed and processed
     */
    public function log_event( $event, $processed ) {
        global $wpdb;

        $wpdb->insert( $this->events, array(
            'event'     => $event,
            'processed' => $processed ? 1 : 0
        ) );
    }

    /**
     * Create a new customer record
     *
     * @param int $user_id
     * @param object $stripe_customer As returned from Stripe
     *
     * @return int Row id
     */
    public function create_customer( $user_id, $stripe_customer ) {
        global $wpdb;

        $insert_params = array(
            'user_id'           => $user_id,
            'customer_id'       => $stripe_customer->id,
            'default_source'    => $stripe_customer->default_source,
            'is_live'           => $stripe_customer->livemode ? 1 : 0,
        );

        $wpdb->insert( $this->customers, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Updates a customer record.
     *
     * @param object $stripe_customer As returned from Stripe
     *
     * @return void
     */
    public function update_customer( $stripe_customer ) {
        global $wpdb;

        $update_params = array(
            'default_source' => $stripe_customer->default_source,
        );

        $where = array( 'customer_id' => $stripe_customer->id );
        $wpdb->update( $this->customers, $update_params, $where );
    }

    /**
     * Get the customer record associated to the given user
     *
     * @param int $user_id
     * @param bool $is_live. True if operating in live mode with Stripe.
     *
     * @return object
     */
    public function get_user_customer( $user_id, $is_live ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT * FROM $this->customers WHERE is_live = %d AND user_id = %d", $is_live ? '1' : 0, $user_id );
        return $wpdb->get_row( $sql );
    }

    /**
     * Create a new source ( payment method )
     * @param object $stripe_source Source object returned from Stripe or with equivalent properties
     *
     * @return int Source row id
     */
    public function create_source( $stripe_source ) {
        global $wpdb;

        $insert_params = array(
            'source_id'     => $stripe_source->id,
            'customer_id'   => $stripe_source->customer,
            'brand'         => $stripe_source->brand,
            'exp_month'     => $stripe_source->exp_month,
            'exp_year'      => $stripe_source->exp_year,
            'last_4'        => $stripe_source->last4,
        );

        $wpdb->insert( $this->sources, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Get a specific source. Includes user_id from associated customer record.
     *
     * @param string $source_id
     *
     * @return object
     */
    public function get_source( $source_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT s.*, c.user_id FROM $this->sources s
            LEFT JOIN $this->customers c ON s.customer_id = c.customer_id
            WHERE source_id = %s", $source_id );
        return $wpdb->get_row( $sql );
    }

    /**
     * Delete a source
     *
     * @param string $source_id
     *
     * @return void
     */
    public function delete_source( $source_id ) {
        global $wpdb;

        $wpdb->delete( $this->sources, array( 'source_id' => $source_id ) );
    }

    /**
     * Get all sources for a given customer
     *
     * @param string $customer_id
     *
     * @return object
     */
    public function get_customer_payment_methods( $customer_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT * FROM $this->sources WHERE customer_id = %s", $customer_id );
        return $wpdb->get_results( $sql );
    }

    /**
     * Create a new charge record
     * @param object $stripe_charge Charge object returned from Stripe or with equivalent properties
     * @param int $invoice_payment_id
     * @param bool $is_live
     *
     * @return int Charge row id
     */
    public function create_charge( $stripe_charge, $invoice_payment_id, $is_live ) {
        global $wpdb;

        $insert_params = array(
            'charge_id'             => $stripe_charge->id,
            'customer_id'           => $stripe_charge->customer,
            'source_id'             => $stripe_charge->source->id,
            'invoice_payment_id'    => $invoice_payment_id,
            'is_live'               => $is_live ? '1' : '0',
        );

        $wpdb->insert( $this->charges, $insert_params );
        return $wpdb->insert_id;
    }
}

