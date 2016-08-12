<?php
/**
 * Dojo invoice extension
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Dojo_Invoice extends Dojo_Extension {
    private static $instance;

    private $submitted_memberships = array();
   
    protected function __construct() {
        parent::__construct( 'Invoice' );

        $this->register_action_handlers( array (
            'dojo_membership_render_page',
            'dojo_membership_submit_application',
            'dojo_membership_add_user_dashboard_blocks',
        ) );

        $this->register_filters( array (
            'dojo_membership_page_title',
            'dojo_membership_admin_student_application',
        ), 10, 2 );

        $this->register_filters( array (
            'dojo_membership_submit_application_redirect',
        ) );
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**** Action Handlers ****/

    public function handle_dojo_membership_render_page( $path ) {
        $user_id = wp_get_current_user()->ID;

        switch ( $path ) {
            case '/invoice' :
                $this->line_items = array();
                $this->invoice = null;
                if ( isset( $_GET['id'] ) ) {
                    $this->invoice = $this->model()->get_invoice( $_GET['id'] );

                    // if invoice found and it belongs to current user
                    if ( null !== $this->invoice && $user_id == $this->invoice->user_id ) {
                        $this->line_items = $this->model()->get_invoice_line_items( $_GET['id'], ARRAY_A );
                    } else {
                        // user doesn't have access
                        $this->invoice = null;
                    }
                }
 
                echo $this->render( 'invoice' );
                break;

            case '/invoices' :
                $this->load_user_invoice_info( $user_id );

                echo $this->render( 'invoices' );
                break;
        }
    }

    public function handle_dojo_membership_submit_application( $membership ) {
        $this->submitted_memberships[ $membership->ID ] = $membership;
    }

    public function handle_dojo_membership_add_user_dashboard_blocks( $dojo_membership ) {
        // set up state for views rendered from render_user_dashboard_block
        $user_id = wp_get_current_user()->ID;
        $this->load_user_invoice_info( $user_id );

        // register block
        $dojo_membership->add_user_dashboard_block( 'invoices', Dojo_Membership::USER_DASHBOARD_RIGHT, $this );
    }


    /**** Filters ****/

    public function filter_dojo_membership_page_title( $title, $path ) {
        switch ( $path ) {
            case '/invoice' :
                return 'Invoice';

            case '/invoice/checkout' :
                return 'Checkout';

            case '/invoices' :
                return 'Invoices';
        }
        return $title;
    }

    public function filter_dojo_membership_admin_student_application( $content, $student ) {
        $invoices = $this->model()->get_student_membership_invoices( $student->ID, $student->current_membership_id );

        $this->current_student = $student;
        $this->unpaid_invoices = array();
        foreach ( $invoices as $invoice ) {
            if ( $invoice->amount_cents > $invoice->amount_paid ) {
                $this->unpaid_invoices[] = $invoice;
            }
        }

        return $this->render( 'admin-student-application' );
    }

    public function filter_dojo_membership_submit_application_redirect( $url ) {
        $user = wp_get_current_user();
        $membership = Dojo_Membership::instance();

        // create a new invoice
        $invoice_id = $this->model()->create_invoice( $user->ID, 'Membership Application' );

        // get new line items
        $line_items = $membership->get_new_contract_line_items( $user->ID );
        foreach ( $line_items as $line_item ) {

            // only use line items for memberships just submitted
            if ( ! isset( $this->submitted_memberships[ $line_item['membership_id'] ] ) ) {
                continue;
            }

            // only take line items in submitted state
            if ( Dojo_Membership::MEMBERSHIP_SUBMITTED != $line_item['membership_status'] ) {
                continue;
            }

            $meta = serialize( array(
                'is_membership_payment' => true,
                'is_application_payment' => true,
            ) );

            // create charge
            $charge_id = $this->model()->create_charge(
                $user->ID,
                $line_item['description'],
                $line_item['amount_cents'],
                array(
                    'student_id'    => $line_item['student_id'],
                    'membership_id' => $line_item['membership_id'],
                    'meta'          => $meta,
                )
            );

            // add to invoice
            $this->model()->add_invoice_charge( $invoice_id, $charge_id );
        }

        $this->model()->update_invoice_total( $invoice_id );

        // return url redirect to new invoice
        return $membership->membership_url( '/invoice?id=' . urlencode( $invoice_id ) );
    }


    /**** Render ****/

    public function render_user_dashboard_block( $block_name ) {
        if ( 'invoices' == $block_name ) {
            echo $this->render( 'user-dashboard-block' );
        }
    }


    /**** Ajax Handlers ****/

    public function api_admin_apply_payment( $is_admin ) {
        if ( ! $is_admin ) {
            return 'Access denied';
        }

        // get invoice
        $invoice = $this->model()->get_invoice( $_POST['invoice_id'] );
        if ( ! $invoice ) {
            return 'Invoice not found';
        }

        $amount_dollars = (float) $_POST['amount_dollars'];
        $amount_cents = (int) ( $amount_dollars * 100 );

        $this->model()->add_invoice_payment( $_POST['invoice_id'], $amount_cents, $_POST );

        $this->notify_invoice_paid( $invoice );

        return 'success';
    }

    /**
     * TODO - this should be handled by membership extension
     *
     * @param $is_admin
     * @return string
     */
    public function api_approve_application( $is_admin ) {
        if ( ! $is_admin ) {
            return 'Access denied';
        }

        Dojo_Membership::instance()->model()->update_membership( $_POST['membership_id'], array(
            'status' => Dojo_Membership::MEMBERSHIP_ACTIVE,
        ) );

        return 'success';
    }


    /**** Event Notifiers ****/

    public function notify_invoice_paid( $invoice ) {
        // make sure invoice is paid up
        $amount_paid = $this->model()->get_invoice_amount_paid( $invoice->ID );
        if ( $amount_paid >= $invoice->amount_cents ) {
            // generate notifications on each completed line item in the invoice
            $line_items = $this->model()->get_invoice_line_items( $invoice->ID );
            foreach ( $line_items as $line_item ) {
                do_action( 'dojo_invoice_line_item_paid', $line_item );
            }
        }
    }


    /**** Utilities ****/

    protected function load_user_invoice_info( $user_id ) {
        $this->invoices = $this->model()->get_invoices( $user_id );
        $this->invoices_paid = array();
        $this->invoices_not_paid = array();
        foreach ( $this->invoices as $invoice ) {
            if ( $invoice->amount_paid == $invoice->amount_cents ) {
                $this->invoices_paid[ $invoice->ID ] = $invoice;
            } else {
                $this->invoices_not_paid[ $invoice->ID ] = $invoice;
            }
        }
    }

    public function dollars( $cents ) {
        return (int)( $cents / 100 ) . '.' . str_pad( $cents % 100, 2, '0', STR_PAD_LEFT);
    }
}

