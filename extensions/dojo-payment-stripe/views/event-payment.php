<?php
$sources = $this->sources;
$user = $this->current_user;
$customer = $this->current_customer;
$post_id = $this->current_post_id;
?>

<button class="dojo-pay-registration">Register</button>


<div class="dojo-payment-form" style="display:none;">
    <div class="dojo-no-sources"<?php echo 0 == count( $sources ) ? '' : ' style="display:none;"' ?>>
        <div class="dojo-warn">You have no saved payment methods.</div>
        <div class="dojo-clear-space"></div>
        <button class="dojo-add-source">Add Payment Method</button>
    </div>
    <div class="dojo-some-sources"<?php echo 0 == count( $sources ) ? ' style="display:none;"' : '' ?>>
        <?php echo $this->render( 'source-select' ) ?>
        <div class="dojo-error-container" style="display:none;">
            <div class="dojo-error dojo-danger"></div>
            <div class="dojo-clear-space"></div>
        </div>
        <button class="dojo-execute-payment">Register and Pay</button>
        <button class="dojo-add-source" style="display:none;">Add Payment Method</button>
    </div>

    <div class="dojo-please-wait" style="display:none;">Please wait...</div>
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
    $('.dojo-pay-registration').click(function() {
        $(this).hide();
        $('.dojo-payment-form').show();
    });

    $('.dojo-execute-payment').click(function() {
        $('.dojo-error-container').hide();
        var data = {
            post_id: '<?php echo esc_attr( $post_id ) ?>',
            source_id: $('input[name=source]:checked').val(),
            line_items: dojoCheckoutGetLineItems()
        };
        $.post('<?php echo $this->ajax( 'user_execute_event_payment' ) ?>', data, function(response) {
            if (response == 'success') {
                window.location.reload();
            }
            else {
                $('.dojo-error').text(response);
                $('.dojo-error-container').show();
            }
        });
    });

    function hookSourceSelect() {
        $('.dojo-source-select input[name=source]').change(function() {
            $('.dojo-error-container').hide();
            if ($(this).val() == 'new') {
                $('.dojo-add-source').show();
                $('.dojo-execute-payment').hide();
            }
            else {
                $('.dojo-some-sources .dojo-add-source').hide();
                $('.dojo-execute-payment').show();
            }
        });
        $('.dojo-delete-source').click(function() {
            $('.dojo-some-sources, .dojo-no-sources').hide();
            $('.dojo-please-wait').show();
            $.post('<?php echo $this->ajax( 'delete_source' ) ?>', { source_id: $(this).attr('data-source-id') }, function(response) {
                var data = eval('(' + response + ')');
                if (data.result == 'success') {
                    if (data.num_sources == 0) {
                        $('.dojo-some-sources').hide();
                        $('.dojo-no-sources').show();
                    }
                    else {
                        $('.dojo-no-sources').hide();
                        $('.dojo-some-sources').show();
                    }
                    $('.dojo-source-select').replaceWith($(data.render));
                    $('.dojo-error-container').hide();
                    $('.dojo-some-sources .dojo-add-source').hide();
                    $('.dojo-execute-payment').show();
                    hookSourceSelect();
                }
                $('.dojo-please-wait').hide();
            });
        });
    }
    hookSourceSelect();

    $('.dojo-add-source').click(function() {
        var email = '<?php echo esc_attr( $user->user_email ) ?>';
        var site_name = '<?php echo esc_attr( bloginfo( 'name' ) ) ?>';

        dojoPayment.open( site_name, 'Payment Method', email, 'Add Card' );
    });

    dojoPayment.init({ stripe_key: '<?php echo $this->get_public_key() ?>' });

    dojoPayment.registerListener(function(event, data) {
        $('.dojo-some-sources, .dojo-no-sources').hide();
        $('.dojo-please-wait').show();

        data.user_id = '<?php echo esc_attr( $user->ID ) ?>';
        $.post('<?php echo $this->ajax( 'save_source' ) ?>', data, function(response) {
            var data = eval('(' + response + ')');
            if (data.result == 'success') {
                if (data.num_sources == 0) {
                    $('.dojo-some-sources').hide();
                    $('.dojo-no-sources').show();
                }
                else {
                    $('.dojo-no-sources').hide();
                    $('.dojo-some-sources').show();
                }
                $('.dojo-source-select').replaceWith($(data.render));
                $('.dojo-error-container').hide();
                $('.dojo-some-sources .dojo-add-source').hide();
                $('.dojo-execute-payment').show();
                hookSourceSelect();
            }
            else {
                $('.dojo-error').text(data.result);
                $('.dojo-error-container').show();
                $('.dojo-add-source').show();
            }
            $('.dojo-please-wait').hide();
        });
    });
});
</script>

