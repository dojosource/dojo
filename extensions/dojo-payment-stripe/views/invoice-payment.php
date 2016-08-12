<?php
$invoice = $this->current_invoice;
$sources = $this->sources;
$user = $this->current_user;
?>

<button class="dojo-pay-invoice">Pay Invoice</button>

<div class="dojo-payment-form" style="display:none;">
    <?php if ( 0 == count( $sources ) ) : ?>
    <div class="dojo-warn">You have no saved payment methods.</div>
    <div class="dojo-clear-space"></div>
    <?php else : ?>
    TODO - display payment methods here
    <?php endif; ?>

    <button class="dojo-add-source">Add Payment Method</button>
    <div class="dojo-please_wait" style="display:none;">Please wait...</div>
    <div class="dojo-clear-space"></div>
    <div class="dojo-security-statement">
        <span class="dashicons-before dashicons-lock dojo-dashicons-middle">Secure Payments</span> <a href="https://stripe.com" target="_blank"><img src="<?php echo esc_attr( $this->url( '/images/stripe.png' ) ) ?>" width="119" height="26" style="margin-left:10px;vertical-align:middle;"></a>
    </div>
</div>

<script src="https://checkout.stripe.com/checkout.js"></script>
<script src="<?php echo esc_attr( $this->url( 'js/dojo-payment-stripe.js' ) ) ?>"></script>

<script>
dojoPayment = new DojoPaymentStripe();
jQuery(function($) {
    $('.dojo-pay-invoice').click(function() {
        $(this).hide();
        $('.dojo-payment-form').show();
    });

    dojoPayment.init({ stripe_key: '<?php echo $this->get_public_key() ?>' });

    dojoPayment.registerListener(function(event, data) {
        console.log('event', event);
        console.log('data', data);

        $('.dojo-add-source').hide();
        $('.dojo-please-wait').show();


        // token.user_id = $invoice->user_id;
        // $.post('<?php echo $this->ajax( 'save_source' ) ?>', token, function(response) {
        // });
    });

    $('.dojo-add-source').click(function() {
        var email = '<?php echo esc_attr( $user->user_email ) ?>';
        var invoice = '<?php echo esc_attr( $invoice->description ) ?>';
        var site_name = '<?php echo esc_attr( bloginfo( 'name' ) ) ?>';

        dojoPayment.open( site_name, 'Payment Method', email, 'Add Card' );
    });
});
</script>
