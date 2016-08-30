<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$billing_day = $this->billing_day;
?>
<div class="dojo-billing-options">
    <div class="dojo-field">
        <div class="dojo-name">Billing Day</div>
        <div class="dojo-value">
            <div>Select which day of the month to process recurring payments.</div>
            <select class="dojo-small-select" name="billing_day">
                <?php for ( $day = 1; $day <= 28; $day ++ ) : ?>
                <option value="<?php echo $day ?>" <?php selected( $day, $billing_day ) ?>>
                    <?php echo $day . date( 'S', strtotime( '1/' . $day . '/2000' ) ) ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <?php echo apply_filters( 'dojo_membership_user_billing', '' ) ?>
</div>

<div class="dojo-clear-space"></div>
<div class="dojo-billing-error" style="display:none;">
    <div class="dojo-error"></div>
    <div class="dojo-clear-space"></div>
</div>

<div class="dojo-field">
    <button class="dojo-save-billing">Save Settings</button>
</div>

<script>
jQuery(function($) {
    $('.dojo-save-billing').click(function() {
        var data = {};
        $('.dojo-billing-options input, .dojo-billing-options select').each(function() {
            if ($(this).attr('type') == 'radio') {
                if ($(this).is(':checked')) {
                    data[$(this).attr('name')] = $(this).val();
                }
            }
            else {
                data[$(this).attr('name')] = $(this).val();
            }
        });
        $.post('<?php echo $this->ajax( 'save_billing_options' ) ?>', data, function(response) {
            if (response == 'success') {
                window.location = '<?php echo $this->membership_url('') ?>';
            }
            else {
                $('.dojo-billing-error .dojo-error').text(response);
                $('.dojo-billing-error').show();
            }
        });
    });
});
</script>

