<div class="dojo-stripe-purchase-button"><?php echo esc_html($this->purchase_button_text) ?></div>

<script>
jQuery('.dojo-stripe-purchase-button').data('params', <?php echo json_encode( $this->client_params ) ?>);
</script>
<script src="https://checkout.stripe.com/checkout.js"></script>
<script src="<?php echo esc_attr( $this->url( 'js/dojo-payment-stripe.js' ) ) ?>"></script>

<script>
dojoPayment = new DojoPaymentStripe();
jQuery(function($) {
    dojoPayment.init();
});
</script>

