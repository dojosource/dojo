<?php
/**
 * Stripe payment extension model
 */

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
}

