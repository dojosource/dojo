<?php
/**
 * Invoice extension installer
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Invoice_Installer extends Dojo_Installer_Base {
    private static $instance;

    // table names
    private $invoices;
    private $charges;
    private $invoice_charges;
    private $payments;

    protected function __construct() {
        global $wpdb;

        parent::__construct( __CLASS__ );

        $this->invoices         = $wpdb->prefix . 'dojo_invoices';
        $this->charges          = $wpdb->prefix . 'dojo_charges';
        $this->invoice_charges  = $wpdb->prefix . 'dojo_invoice_charges';
        $this->payments         = $wpdb->prefix . 'dojo_payments';
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
                $this->invoices,
                $this->charges,
                $this->invoice_charges,
                $this->payments,
            );

            if ( ! $this->table_exists( $rev1_tables ) ) {
                $this->set_rev( 0 );
            }
        }
    }

    public function uninstall() {
        parent::uninstall();

        $this->drop_tables( array(
            $this->invoices,
            $this->charges,
            $this->invoice_charges,
            $this->payments,
        ) );
    }

    public function rev_1() {
        global $wpdb;

        $wpdb->query( '
            CREATE TABLE ' . $this->invoices . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            description VARCHAR(255) NULL,
            invoice_date DATETIME,
            user_id INT NULL,
            amount_cents INT NULL,
            status VARCHAR(255) NULL,
            settled_date DATETIME,
            settled_method VARCHAR(255) NULL,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY invoice_date (invoice_date),
            KEY user_id (user_id),
            KEY status (status),
            KEY settled_date (settled_date));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->charges . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            charge_date DATETIME,
            user_id INT NULL,
            student_id INT NULL,
            membership_id INT NULL,
            description VARCHAR(255) NULL,
            amount_cents INT NULL,
            notes TEXT NULL,
            meta TEXT NULL,
            PRIMARY KEY (ID),
            KEY charge_date (charge_date),
            KEY user_id (user_id),
            KEY student_id (student_id),
            KEY membership_id (membership_id));
        ' );


        $wpdb->query( '
            CREATE TABLE ' . $this->invoice_charges . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            invoice_id INT NULL,
            charge_id INT NULL,
            PRIMARY KEY (ID),
            KEY invoice_id (invoice_id),
            KEY charge_id (charge_id));
        ' );

         $wpdb->query( '
            CREATE TABLE ' . $this->payments . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            payment_date DATETIME,
            invoice_id INT NULL,
            amount_cents INT NULL,
            method VARCHAR(255) NULL,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY payment_date (payment_date),
            KEY invoice_id (invoice_id));
        ' );

        return true;
    }
}

