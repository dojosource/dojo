<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$sources = $this->sources;
$user = $this->current_user;
$customer = $this->current_customer;

$this->supress_source_select_title = true;
$this->supress_source_select_add = true;
?>

<div class="dojo-clear-space"></div>

<div class="dojo-field">
    <div class="dojo-name">Payment Method</div>
    <div class="dojo-value">
        <div>Select a default payment method</div>
        <div class="dojo-payment-form">
            <div class="dojo-no-sources"<?php echo 0 == count( $sources ) ? '' : ' style="display:none;"' ?>>
                <div class="dojo-warn">You have no saved payment methods.</div>
                <div class="dojo-clear-space"></div>
            </div>
            <div class="dojo-some-sources"<?php echo 0 == count( $sources ) ? ' style="display:none;"' : '' ?>>
                <?php echo $this->render( 'source-select' ) ?>
                <div class="dojo-error-container" style="display:none;">
                    <div class="dojo-error dojo-danger"></div>
                    <div class="dojo-clear-space"></div>
                </div>
            </div>

            <a href="javascript:;" class="dojo-add-source">Add Payment Method</a>
            <div class="dojo-please-wait" style="display:none;">Please wait...</div>
            <div class="dojo-clear-space"></div>
            <div class="dojo-security-statement">
                <span class="dashicons-before dashicons-lock dojo-dashicons-middle">Secure Payments</span> <a href="https://stripe.com" target="_blank"><img src="<?php echo esc_attr( $this->url( '/images/stripe.png' ) ) ?>" width="119" height="26" style="margin-left:10px;vertical-align:middle;"></a>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.stripe.com/checkout.js"></script>
<script src="<?php echo esc_attr( $this->url( 'js/dojo-payment-stripe.js' ) ) ?>"></script>

<script>
dojoPayment = new DojoPaymentStripe();
jQuery(function($) {

    function hookSourceSelect() {
        $('.dojo-delete-source').click(function() {
            $('.dojo-some-sources, .dojo-no-sources').hide();
            $('.dojo-please-wait').show();
            $('.dojo-add-source').hide();
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
                    $('.dojo-source-select-title').remove();
                    $('.dojo-add-payment-method-option').remove();
                    $('.dojo-error-container').hide();
                    hookSourceSelect();
                }
                $('.dojo-please-wait').hide();
                $('.dojo-add-source').show();
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
        $('.dojo-add-source').hide();
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
                $('.dojo-source-select-title').remove();
                $('.dojo-add-payment-method-option').remove();
                $('.dojo-error-container').hide();
                hookSourceSelect();
            }
            else {
                $('.dojo-error').text(data.result);
                $('.dojo-error-container').show();
            }
            $('.dojo-please-wait').hide();
            $('.dojo-add-source').show();
        });
    });
});
</script>