<?php
/**
 * Payment extension model
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Payment_Model extends Dojo_Model_Base {
    private static $instance;

    // table names
    private $customers;
    private $sources;
    private $charges;
    private $events;

    protected function __construct() {
        global $wpdb;

        $this->customers    = $wpdb->prefix . 'dojo_payment_customers';
        $this->sources      = $wpdb->prefix . 'dojo_payment_sources';
        $this->charges      = $wpdb->prefix . 'dojo_payment_charges';
        $this->events       = $wpdb->prefix . 'dojo_payment_events';
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Log event received from gateway
     *
     * @param string $gateway Name of the gateway (i.e. stripe)
     * @param string $event JSON text received from gateway
     * @param bool $processed True if event was routed and processed
     */
    public function log_event( $gateway, $event, $processed ) {
        global $wpdb;

        $wpdb->insert( $this->events, array(
            'gateway'   => $gateway,
            'event'     => $event,
            'processed' => $processed ? 1 : 0
        ) );
    }

    /**
     * Create a new customer record
     *
     * @param int $user_id
     * @param object $gateway_customer As returned from gateway
     *
     * @return int Row id
     */
    public function create_customer( $user_id, $gateway_customer ) {
        global $wpdb;

        $insert_params = array(
            'user_id'           => $user_id,
            'customer_id'       => $gateway_customer->id,
            'default_source'    => $gateway_customer->default_source,
            'is_live'           => $gateway_customer->livemode ? 1 : 0,
        );

        $wpdb->insert( $this->customers, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Updates a customer record.
     *
     * @param object $gateway_customer As returned from gateway
     *
     * @return void
     */
    public function update_customer( $gateway_customer ) {
        global $wpdb;

        $update_params = array(
            'default_source' => $gateway_customer->default_source,
        );

        $where = array( 'customer_id' => $gateway_customer->id );
        $wpdb->update( $this->customers, $update_params, $where );
    }

    /**
     * Get the customer record associated to the given user
     *
     * @param int $user_id
     * @param bool $is_live. True if operating in live mode with gateway.
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
     * @param object $gateway_source Source object returned from gateway or with equivalent properties
     *
     * @return int Source row id
     */
    public function create_source( $gateway_source ) {
        global $wpdb;

        $insert_params = array(
            'source_id'     => $gateway_source->id,
            'customer_id'   => $gateway_source->customer,
            'brand'         => $gateway_source->brand,
            'exp_month'     => $gateway_source->exp_month,
            'exp_year'      => $gateway_source->exp_year,
            'last_4'        => $gateway_source->last4,
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
     * @param object $gateway_charge Charge object returned from gateway or with equivalent properties
     * @param int $invoice_payment_id
     * @param bool $is_live
     *
     * @return int Charge row id
     */
    public function create_charge( $gateway_charge, $invoice_payment_id, $is_live ) {
        global $wpdb;

        $insert_params = array(
            'charge_id'             => $gateway_charge->id,
            'customer_id'           => $gateway_charge->customer,
            'source_id'             => $gateway_charge->source->id,
            'invoice_payment_id'    => $invoice_payment_id,
            'is_live'               => $is_live ? '1' : '0',
        );

        $wpdb->insert( $this->charges, $insert_params );
        return $wpdb->insert_id;
    }
}

