<?php
/**
 * Invoice extension model
 */

class Dojo_Invoice_Model extends Dojo_Model_Base {
    private static $instance;

    // table names
    private $invoices;
    private $charges;
    private $invoice_charges;
    private $payments;

    protected function __construct() {
        global $wpdb;

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

    /**
     * Create a new charge. Charge is associated with a user. It may optionally be associated
     * with a student and/or membership
     *
     * @param int $user_id
     * @param string $description
     * @param int $amount_cents
     * @param array ( key => value ) $params Optional additional parameters to set
     *
     * @return int Charge id
     */
    public function create_charge( $user_id, $description, $amount_cents, $params = array() ) {
        global $wpdb;
        
        // filter optional parameters
        $insert_params = $this->filter_params( $params, array(
            'charge_date',
            'student_id',
            'membership_id',
            'notes',
            'meta',
        ) );

        // set required parameters
        $insert_params['user_id']       = $user_id;
        $insert_params['description']   = $description;
        $insert_params['amount_cents']  = $amount_cents;

        if ( ! isset( $insert_param['charge_date'] ) ) {
            $insert_params['charge_date'] = current_time( 'mysql' );
        }

        $wpdb->insert( $this->charges, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Create a new empty invoice for a user
     *
     * @param int $user_id
     * @param string $description
     *
     * @return int Invoice id
     */
    public function create_invoice( $user_id, $description ) {
        global $wpdb;

        $insert_params = array(
            'invoice_date'      => current_time( 'mysql' ),
            'user_id'           => $user_id,
            'description'       => $description,
        );

        $wpdb->insert( $this->invoices, $insert_params );
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

        $sql = $wpdb->prepare( "SELECT i.*, SUM(p.amount_cents) as amount_paid FROM $this->invoices i
            LEFT JOIN $this->payments p ON i.ID = p.invoice_id
            WHERE i.ID = %d", $invoice_id );
        return $wpdb->get_row( $sql );
    }

    /**
     * Get all invoices for a given user
     *
     * @param int $user_id
     * @param $output_type output type predefined constant
     */
    public function get_invoices( $user_id, $output_type = OBJECT ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT i.*, SUM(p.amount_cents) as amount_paid FROM $this->invoices i
            LEFT JOIN $this->payments p ON i.ID = p.invoice_id
            WHERE i.user_id = %d
            GROUP BY i.ID
            ORDER BY invoice_date DESC", $user_id );

        return $wpdb->get_results( $sql, $output_type );
    }

    /**
     * @param int $invoice_id
     *
     * @return int Amount paid in cents
     */
    public function get_invoice_amount_paid( $invoice_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT SUM(amount_cents) FROM $this->payments WHERE invoice_id = %d", $invoice_id );
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Add a charge to an invoice
     *
     * @param int $invoice_id
     * @param int $charge_id
     *
     * @return int insert id
     */
    public function add_invoice_charge( $invoice_id, $charge_id ) {
        global $wpdb;

        $invoice = $this->get_invoice( $invoice_id );
        if ( null !== $invoice ) {
            $insert_params = array(
                'invoice_id'    => $invoice_id,
                'charge_id'     => $charge_id,
            );

            $wpdb->insert( $this->invoice_charges, $insert_params );
            $charge_id = $wpdb->insert_id;
            $this->update_invoice_total( $invoice_id );

            return $charge_id;
        }

        return null;
    }

     /**
     * Apply a payment to an invoice
     *
     * @param int $invoice_id
     * @param int $amount_cents
     * @param array $params Optional additional parameters
     *
     * @return int insert id
     */
    public function add_invoice_payment( $invoice_id, $amount_cents, $params = array() ) {
        global $wpdb;

        // filter optional parameters
        $insert_params = $this->filter_params( $params, array(
            'payment_date',
            'method',
            'notes',
        ) );

        $insert_params['invoice_id']    = $invoice_id;
        $insert_params['amount_cents']  = $amount_cents;

        $this->fix_dates( $insert_params, array( 'payment_date' ) );

        $wpdb->insert( $this->payments, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Update the total cents on the given invoice record as sum of charges.
     *
     * @param int $invoice_id
     *
     * @return void
     */
    public function update_invoice_total( $invoice_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT SUM(amount_cents) FROM $this->invoice_charges ic
            INNER JOIN $this->charges c ON ic.charge_id = c.ID
            WHERE invoice_id = %d", $invoice_id );
        $total = $wpdb->get_var( $sql );
        $wpdb->update(
            $this->invoices,
            array( 'amount_cents' => $total ),
            array( 'ID' => $invoice_id )
        );
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

        $sql = $wpdb->prepare( "SELECT c.* FROM $this->invoice_charges ic
            INNER JOIN $this->charges c ON ic.charge_id = c.ID
            WHERE ic.invoice_id = %d", $invoice_id );

        return $wpdb->get_results( $sql, $output_type );
    }

    /**
     * Get all the invoices containing charges for a specific student on a specific membership.
     * Invoices are returned ordered by most recent first. Includes aggregate amount_paid
     *
     * @param $student_id
     * @param $membership_id
     * @param $output_type output type predefined constant
     *
     * @return array
     */
    public function get_student_membership_invoices( $student_id, $membership_id, $output_type = OBJECT ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT i.*, SUM(p.amount_cents) as amount_paid FROM $this->invoices i
            INNER JOIN $this->invoice_charges ic on i.ID = ic.invoice_id
            INNER JOIN $this->charges c ON ic.charge_id = c.ID
            LEFT JOIN $this->payments p ON i.ID = p.invoice_id
            WHERE c.student_id = %d AND c.membership_id = %d
            GROUP BY i.ID
            ORDER BY i.timestamp DESC",
            $student_id, $membership_id
        );
        return $wpdb->get_results( $sql, $output_type );
    }
}

