<?php
/**
 * Stripe payment extension installer
 */

class Dojo_Payment_Stripe_Installer extends Dojo_Installer_Base {
    private static $instance;

    // table names
    private $customers;
    private $sources;
    private $charges;
    private $events;

    protected function __construct() {
        global $wpdb;

        parent::__construct( __CLASS__ );

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

    public function check_integrity() {
        $rev = $this->get_rev();

        if ( $rev >= 1 ) {
            $rev1_tables = array(
                $this->customers,
                $this->sources,
                $this->charges,
                $this->events,
            );

            if ( ! $this->table_exists( $rev1_tables ) ) {
                $this->set_rev( 0 );
            }
        }
    }

    public function rev_1() {
        global $wpdb;

        $wpdb->query( '
            CREATE TABLE ' . $this->customers . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            customer_id VARCHAR(255) NULL,
            user_id INT NULL,
            default_source VARCHAR(255) NULL,
            is_live TINYINT NULL,
            PRIMARY KEY (ID),
            KEY customer_id (customer_id),
            KEY user_id (user_id),
            KEY is_live (is_live));
        ' );

         $wpdb->query( '
            CREATE TABLE ' . $this->sources . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            source_id VARCHAR(255) NULL,
            customer_id VARCHAR(255) NULL,
            brand VARCHAR(255) NULL,
            exp_month INT NULL,
            exp_year INT NULL,
            last_4 VARCHAR(10) NULL,
            PRIMARY KEY (ID),
            KEY source_id (source_id),
            KEY customer_id (customer_id));
        ' );

         $wpdb->query( '
            CREATE TABLE ' . $this->charges . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            charge_id VARCHAR(255) NULL,
            customer_id VARCHAR(255) NULL,
            source_id VARCHAR(255) NULL,
            invoice_payment_id INT NULL,
            paid TINYINT NULL,
            captured TINYINT NULL,
            is_live TINYINT NULL,
            PRIMARY KEY (ID),
            KEY source_id (source_id),
            KEY customer_id (customer_id));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->events . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            event TEXT NULL,
            processed TINYINT NULL,
            PRIMARY KEY (ID),
            KEY processed (processed));
        ' );

        return true;
    }
}


