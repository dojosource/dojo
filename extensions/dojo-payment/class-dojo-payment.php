<?php
/**
 * Dojo payment extension
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Payment extends Dojo_Extension {
    private static $instance;

    /**** Supported Gateways ****/

    const GATEWAY_STRIPE = 'stripe';


    protected function __construct() {
        parent::__construct( 'Online Payments' );

        // include vendor library
        require_once $this->path() . 'stripe-php/init.php';

        $this->register_action_handlers( array (
            'dojo_register_settings',
            'dojo_membership_save_user_billing_options',
        ) );

        $this->register_filters( array (
            'dojo_membership_user_billing',
            'dojo_membership_user_dashboard_billing',
            array( 'dojo_event_register_button', 10, 2 ),
            array( 'dojo_invoice_payment', 10, 2 ),
        ) );
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**** Ajax Endpoints ****/

    public function api_stripe_webhook() {
        global $wpdb;

        // read input stream
        $input = @file_get_contents( 'php://input' );

        // parse json to event object
        $event = json_decode( $input );

        $this->debug( 'Received event: ' . $event->type );

        // if we are in live mode and this is coming from test mode then just ignore
        if ( $this->is_live() && ! $event->livemode ) {
            $this->debug( 'Ignoring test mode event' );
            return;
        }

        // request event back from stripe as security measure
        $secret_key = $this->get_secret_key();
        Stripe\Stripe::setApiKey( $secret_key );
        $id = $event->id;
        try {
            $event = Stripe\Event::retrieve( $id );
        } catch ( Exception $ex ) {
            $this->debug( $ex->getMessage() );
            $this->debug( '*** INVALID EVENT ***' );
            return;
        }

        // verify id checks out in verified event received from stripe
        if ( $id == $event->id ) {
            $this->debug( 'Verified valid event' );
        } else {
            $this->debug( '*** EVENT ID DOES NOT MATCH ***' );
            return;
        } 

        // route to webhook handler method if one exists
        // Note: handler method for charge.succeeded would be webhook_handle_charge_succeeded
        $webhook_handler = 'webhook_handle_' . str_replace( '.', '_', $event->type );
        $event_processed = false;
        if ( method_exists( $this, $webhook_handler ) ) {
            $event_processed = call_user_func( array( $this, $webhook_handler ), $event->data->object );
        }

        // save event to database log
        $this->model()->log_event( self::GATEWAY_STRIPE, $input, $event_processed );

        // send response back to stripe
        if ( $event_processed ) {
            return 'processed';
        }
        return 'ignored';
    }

    public function api_save_source() {
        $user = wp_get_current_user();

        if ( $_POST['user_id'] != $user->ID ) {
            return 'Access denied';
        }

        $is_live = $this->is_live();
        $token = $_POST['token'];
        Stripe\Stripe::setApiKey( $this->get_secret_key() );

        $customer = $this->model()->get_user_customer( $user->ID, $is_live );
        if ( ! $customer ) {
            // create new customer object for this user
            $stripe_customer = Stripe\Customer::create( array(
                'source' => $token['id'],
                'description' => $user->ID . ' ' . $user->first_name . ' ' . $user->last_name,
                'email' => $user->user_email
            ) );
            $source = $stripe_customer->sources->data[0];

            $this->model()->create_customer( $user->ID, $stripe_customer );
            $this->model()->create_source( $source );
        } else {
            $stripe_customer = \Stripe\Customer::retrieve( $customer->customer_id );
            $source = $stripe_customer->sources->create( array(
                'source' => $token['id'],
            ) );
            $this->model()->create_source( $source );

            // set new source as default
            $stripe_customer->default_source = $source->id;
            $stripe_customer->save();

            // save customer info
            $this->model()->update_customer( $stripe_customer );
        }

        $this->current_customer = $this->model()->get_user_customer( $user->ID, $is_live );
        $this->sources = $this->model()->get_customer_payment_methods( $this->current_customer->customer_id );

        $response = array(
            'result'        => 'success',
            'num_sources'   => count( $this->sources ),
            'render'        => $this->render( 'source-select' ),
        );

        return $response;
    }

    public function api_delete_source() {
        $user = wp_get_current_user();

        // validate source
        $source = $this->model()->get_source( $_POST['source_id'] );
        if ( ! $source || $user->ID != $source->user_id ) {
            return 'Invalid payment method';
        }

        $is_live = $this->is_live();
        $token = $_POST['token'];
        Stripe\Stripe::setApiKey( $this->get_secret_key() );

        $customer = $this->model()->get_user_customer( $user->ID, $is_live );
        if ( ! $customer ) {
            return 'Customer does not exist';
        }

        $stripe_customer = Stripe\Customer::retrieve( $customer->customer_id );
        $stripe_customer->sources->retrieve( $source->source_id )->delete();

        // get updated customer
        $stripe_customer = Stripe\Customer::retrieve( $customer->customer_id );

        // delete source from database
        $this->model()->delete_source( $source->source_id );

        // update customer in database
        $this->model()->update_customer( $stripe_customer );

        $this->current_customer = $this->model()->get_user_customer( $user->ID, $is_live );
        $this->sources = $this->model()->get_customer_payment_methods( $this->current_customer->customer_id );

        $response = array(
            'result'        => 'success',
            'num_sources'   => count( $this->sources ),
            'render'        => $this->render( 'source-select' )
        );

        return $response;
    }

    public function api_user_execute_payment() {
        $invoice = Dojo_Invoice::instance()->model()->get_invoice( $_POST['invoice_id'] );
        $user = wp_get_current_user();

        // validate invoice
        if ( ! $invoice || $user->ID != $invoice->user_id ) {
            return 'Invalid invoice';
        }

        // validate source
        $source = $this->model()->get_source( $_POST['source_id'] );
        if ( ! $source || $user->ID != $source->user_id ) {
            return 'Invalid payment method';
        }

        $amount_due = $invoice->amount_cents - $invoice->amount_paid;
        if ( $amount_due < 0 ) {
            return 'Nothing due on this invoice';
        }

        // create and execute charge
        Stripe\Stripe::setApiKey( $this->get_secret_key() );
        try {
            $charge = Stripe\Charge::create(array(
                'amount'        => $amount_due,
                'currency'      => 'usd',
                'customer'      => $source->customer_id,
                'source'        => $source->source_id,
                'description'   => $invoice->description,
                'metadata'      => array( 'invoice_id' => $invoice->ID ),
            ));
        } catch( Exception $ex ) {
            $this->debug( 'Error executing charge ' . $ex->getMessage() );
            return $ex->getMessage();
        }

        // record payment against invoice
        $invoice_payment_id = Dojo_Invoice::instance()->model()->add_invoice_payment( $invoice->ID, $amount_due, array(
            'payment_date'  => $this->date( 'Y-m-d H:i:s', $charge->created ),
            'method'        => 'Online Charge',
        ) );

        // record payment association to stripe charge
        $this->model()->create_charge( $charge, $invoice_payment_id, $this->is_live() );

        // send notifications of invoice paid
        Dojo_Invoice::instance()->notify_invoice_paid( $invoice );

        return 'success';
    }

    public function api_user_execute_event_payment() {
        // set post context
        $_GLOBAL['post'] = $post = get_post( $_POST['post_id'] );
        if ( ! $post || 'dojo_event' != $post->post_type ) {
            return 'Invalid event id';
        }

        // get dojo event instance
        $dojo_event = Dojo_Event::instance();

        // get current user
        $user = wp_get_current_user();

        // validate source
        $source = $this->model()->get_source( $_POST['source_id'] );
        if ( ! $source || $user->ID != $source->user_id ) {
            return 'Invalid payment method';
        }

        // extract student ids from line items
        $students = array();
        foreach ( $_POST['line_items'] as $line_item ) {
            $students[] = $line_item['id'];
        }

        // validate pricing by using the event line item ajax endpoint directly
        $_POST['students'] = $students;
        $line_items = $dojo_event->api_get_line_items( false );

        // verify line items are consistent
        foreach ( $_POST['line_items'] as $index => $line_item ) {
            if ( $line_item['amount_cents'] != $line_items[$index]['amount_cents'] ) {
                debug( 'Line items do not match' );
                return 'Invalid request';
            }
            $line_items[$index]['meta'] = array(
                'is_event_registration' => true,
                'event_id' => $_POST['post_id'],
                'student_id' => $line_item['id'],
            );
        }

        // create a new invoice
        $invoice_id = Dojo_Invoice::instance()->create_invoice( $user->ID, $post->post_title, $line_items );
        $invoice = Dojo_Invoice::instance()->model()->get_invoice( $invoice_id );

        $amount_due = $invoice->amount_cents;

        // create and execute charge
        Stripe\Stripe::setApiKey( $this->get_secret_key() );
        try {
            $charge = Stripe\Charge::create(array(
                'amount'        => $amount_due,
                'currency'      => 'usd',
                'customer'      => $source->customer_id,
                'source'        => $source->source_id,
                'description'   => $invoice->description,
                'metadata'      => array( 'invoice_id' => $invoice->ID ),
            ));
        } catch( Exception $ex ) {
            // delete the invoice
            Dojo_Invoice::instance()->model()->delete_invoice( $invoice_id );
            $this->debug( 'Error executing charge ' . $ex->getMessage() );
            return $ex->getMessage();
        }

        // record payment against invoice
        $invoice_payment_id = Dojo_Invoice::instance()->model()->add_invoice_payment( $invoice->ID, $amount_due, array(
            'payment_date'  => $this->date( 'Y-m-d H:i:s', $charge->created ),
            'method'        => 'Online Charge',
        ) );

        // record payment association to stripe charge
        $this->model()->create_charge( $charge, $invoice_payment_id, $this->is_live() );

        // send notifications of invoice paid which will be picked up by Dojo_Event to register the students
        Dojo_Invoice::instance()->notify_invoice_paid( $invoice );

        return 'success';
    }


    /**** Webhook Handlers ****/

    private function webhook_handle_charge_succeeded( $event ) {
        return true;
    }


    /**** Action Handlers ****/

    public function handle_dojo_register_settings( $settings ) {
        $webhook_instructions = $this->render( 'webhook-instructions' );

        $settings->register_section(
            'dojo_stripe_section',          // section id
            'Stripe Payments',              // section title
            $webhook_instructions           // section subtitle
        );

        $settings->register_option( 'dojo_stripe_section', 'payment_enable_live_mode', 'Enable Live Mode', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_test_secret_key', 'Test Secret Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_test_public_key', 'Test Publishable Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_live_secret_key', 'Live Secret Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_live_public_key', 'Live Publishable Key', $this );
    }

    public function handle_dojo_membership_save_user_billing_options( $user ) {
        if ( isset( $_POST['source'] ) ) {

            $is_live = $this->is_live();
            Stripe\Stripe::setApiKey( $this->get_secret_key() );

            $customer = $this->model()->get_user_customer( $user->ID, $is_live );
            if ( $customer ) {
                $stripe_customer = \Stripe\Customer::retrieve( $customer->customer_id );
                $stripe_customer->default_source = $_POST['source'];
                $stripe_customer->save();

                // save customer info in our database
                $this->model()->update_customer( $stripe_customer );
            }
        }
    }


    /**** Filters ****/

    public function filter_dojo_membership_user_billing( $render ) {
        $user = wp_get_current_user();
        $this->current_user = $user;
        $this->current_customer = $this->model()->get_user_customer( $user->ID, $this->is_live() );
        $this->sources = $this->model()->get_customer_payment_methods( $this->current_customer->customer_id );

        return $render . $this->render( 'manage-payment-methods' );
    }

    public function filter_dojo_membership_user_dashboard_billing( $render ) {
        $user = wp_get_current_user();
        $customer = $this->model()->get_user_customer( $user->ID, $this->is_live() );
        $this->default_source = $this->model()->get_source( $customer->default_source );

        return $render . $this->render( 'default-payment-method' );
    }

    public function filter_dojo_event_register_button( $render, $post_id ) {
        $user = wp_get_current_user();
        $this->current_post_id = $post_id;
        $this->current_user = $user;
        $this->current_customer = $this->model()->get_user_customer( $user->ID, $this->is_live() );
        $this->sources = $this->model()->get_customer_payment_methods( $this->current_customer->customer_id );

        return $this->render( 'event-payment' );
    }

    public function filter_dojo_invoice_payment( $render, $invoice ) {
        $user = wp_get_current_user();
        $this->current_invoice = $invoice;
        $this->current_user = $user;
        $this->current_customer = $this->model()->get_user_customer( $user->ID, $this->is_live() );
        if ( $this->current_customer ) {
            $this->sources = $this->model()->get_customer_payment_methods( $this->current_customer->customer_id );
        } else {
            $this->sources = array();
        }

        return $this->render( 'invoice-payment' );
    }


    /**** Render Options ****/

    public function render_option_payment_enable_live_mode() {
        $this->render_option_checkbox( 'payment_enable_live_mode', 'Ready to go live with real payments.' );
    }

    public function render_option_stripe_test_secret_key() {
        $this->render_option_regular_text( 'stripe_test_secret_key' );
    }

    public function render_option_stripe_test_public_key() {
        $this->render_option_regular_text( 'stripe_test_public_key' );
    }

    public function render_option_stripe_live_secret_key() {
        $this->render_option_regular_text( 'stripe_live_secret_key' );
    }

    public function render_option_stripe_live_public_key() {
        $this->render_option_regular_text( 'stripe_live_public_key' );
    }


    /**** Utility ****/

    public function is_live() {
        return '1' == $this->get_setting( 'payment_enable_live_mode' );
    }

    public function get_secret_key() {
        if ( $this->is_live() ) {
            return $this->get_setting( 'stripe_live_secret_key' );
        }
        return $this->get_setting( 'stripe_test_secret_key' );
    }

    public function get_public_key() {
        if ( $this->is_live() ) {
            return $this->get_setting( 'stripe_live_public_key' );
        }
        return $this->get_setting( 'stripe_test_public_key' );
    }
}

