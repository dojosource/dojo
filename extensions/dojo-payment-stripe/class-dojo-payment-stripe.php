<?php
/**
 * Dojo stripe payment extension
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Payment_Stripe extends Dojo_Extension {
    private static $instance;

    protected function __construct() {
        parent::__construct( 'Stripe Payments' );

        // include vendor library
        require_once $this->path() . 'stripe-php/init.php';

        $this->register_action_handlers( array (
            'dojo_register_settings',
        ) );

        $this->register_filters( array (
            'dojo_invoice_payment',
        ), 10, 2 );
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
        $this->model()->log_event( $input, $event_processed );

        // send response back to stripe
        if ( $event_processed ) {
            return 'processed';
        }
        return 'ignored';
    }

    public function api_save_source( $is_admin ) {
        $user = wp_get_current_user();

        if ( $_POST['user_id'] != $user->ID ) {
            return 'Access denied';
        }

        $token = $_POST['token'];
        Stripe\Stripe::setApiKey( $this->get_private_key() );



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

        $settings->register_option( 'dojo_stripe_section', 'stripe_enable_live_mode', 'Enable Live Mode', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_test_secret_key', 'Test Secret Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_test_public_key', 'Test Publishable Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_live_secret_key', 'Live Secret Key', $this );
        $settings->register_option( 'dojo_stripe_section', 'stripe_live_public_key', 'Live Publishable Key', $this );
    }


    /**** Filters ****/

    public function filter_dojo_invoice_payment( $render, $invoice ) {
        $this->current_invoice = $invoice;
        $this->sources = array();
        $this->current_user = wp_get_current_user();

        return $this->render( 'invoice-payment' );
    }


    /**** Render Options ****/

    public function render_option_stripe_enable_live_mode() {
        $this->render_option_checkbox( 'stripe_enable_live_mode', 'Ready to go live with real payments.' );
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
        return '1' == $this->get_setting( 'stripe_enable_live_mode' );
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

