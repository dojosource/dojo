<?php
/**
 * Membership extension installer
 */

class Dojo_Membership_Installer extends Dojo_Installer_Base {
    private static $instance;

    // table names
    private $notifications;
    private $students;
    private $memberships;
    private $membership_alerts;
    private $programs;
    private $contracts;
    private $contract_programs;
    private $documents;
    private $contract_documents;
    private $membership_contract_documents;

    protected function __construct() {
        global $wpdb;

        parent::__construct( __CLASS__ );

        $this->notifications                    = $wpdb->prefix . 'dojo_notifications';
        $this->students                         = $wpdb->prefix . 'dojo_students';
        $this->memberships                      = $wpdb->prefix . 'dojo_memberships';
        $this->membership_alerts                = $wpdb->prefix . 'dojo_membership_alerts';
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

    public function check_integrity() {
        $rev = $this->get_rev();

        if ( $rev >= 1 ) {
            $rev1_tables = array(
                $this->notifications,
                $this->students,
                $this->memberships,
                $this->membership_alerts,
                $this->programs,
                $this->contracts,
                $this->contract_programs,
                $this->documents,
                $this->contract_documents,
                $this->membership_contract_documents,
            );

            if ( ! $this->table_exists( $rev1_tables ) ) {
                $this->set_rev( 0 );
            }
        }
    }

    public function rev_1() {
        global $wpdb;

        $wpdb->query( '
            CREATE TABLE ' . $this->notifications . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            user_id INT NULL,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            type VARCHAR(255) NULL,
            ref_id INT NULL,
            title VARCHAR(255) NULL,
            body TEXT NULL,
            date_sent DATETIME NULL,
            date_viewed DATETIME NULL,
            date_expired DATETIME NULL,
            PRIMARY KEY (ID),
            KEY user_id (user_id),
            KEY type (type),
            KEY ref_id (ref_id),
            KEY date_sent (date_sent),
            KEY date_expired (date_expired));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->students . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            user_id INT NULL,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            first_name VARCHAR(255) NULL,
            last_name VARCHAR(255) NULL,
            alias VARCHAR(255) NULL,
            dob DATETIME NULL,
            notes TEXT NULL,
            start_date DATETIME NULL,
            is_active TINYINT NULL,
            current_membership_id INT NULL,
            belt INT NULL,
            deleted_date DATETIME NULL,
            PRIMARY KEY (ID),
            KEY user_id (user_id),
            KEY is_active (is_active),
            KEY delete_date (deleted_date));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->memberships . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            start_date DATETIME NULL,
            next_due_date DATETIME NULL,
            contract_id INT NULL,
            status VARCHAR(255) NULL,
            last_update DATETIME,
            freeze_request_date DATETIME,
            freeze_end_date DATETIME,
            freeze_request_count INT NULL,
            cancel_request_date DATETIME,
            cancel_execute_date DATETIME,
            student_id INT NULL,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY start_date (start_date),
            KEY contract_id (contract_id),
            KEY freeze_end_date (freeze_end_date),
            KEY cancel_execute_date (cancel_execute_date),
            KEY student_id (student_id));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->membership_alerts . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            membership_id INT NULL,
            student_id INT NULL,
            alert VARCHAR(255) NULL,
            start_date DATETIME,
            cleared_date DATETIME,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY membership_id (membership_id),
            KEY student_id (student_id),
            KEY start_date (start_date),
            KEY cleared_date (cleared_date));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->programs . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            title VARCHAR(255) NULL,
            description TEXT NULL,
            min_age INT NULL,
            max_age INT NULL,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY min_age (min_age),
            KEY max_age (max_age));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->contracts . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT NULL,
            term_months INT NULL,
            terms_url VARCHAR(255) NULL,
            title VARCHAR(255) NULL,
            new_memberships_only TINYINT NULL,
            continuing_memberships_only TINYINT NULL,
            family_pricing TEXT NULL,
            cancellation_policy VARCHAR(255) NULL,
            cancellation_days INT NULL,
            notes TEXT NULL,
            PRIMARY KEY (ID),
            KEY is_active (is_active));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->contract_programs . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            contract_id INT NULL,
            program_id INT NULL,
            PRIMARY KEY (ID),
            KEY contract_id (contract_id),
            KEY program_id (program_id));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->documents . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            title VARCHAR(255) NULL,
            filename VARCHAR(255) NULL,
            PRIMARY KEY (ID));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->contract_documents . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            document_id INT NULL,
            contract_id INT NULL,
            PRIMARY KEY (ID),
            KEY document_id (document_id),
            KEY contract_id (contract_id));
        ' );

        $wpdb->query( '
            CREATE TABLE ' . $this->membership_contract_documents . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            membership_id INT NULL,
            contract_id INT NULL,
            document_id INT NULL,
            uploaded_file VARCHAR(255) NULL,
            PRIMARY KEY (ID),
            KEY membership_id (membership_id),
            KEY contract_id (contract_id),
            KEY document_id (document_id));
        ' );

        return true;
    }
}

