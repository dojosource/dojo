<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }
?>
<p>Copy this url to your Stripe Account Settings -> Webhooks:<br>
<input id="stripe_webhook_url" type="text" class="regular-text name="webhook_url" value="<?php echo esc_attr( $this->ajax( 'stripe_webhook' ) ) ?>" readonly>
</p>
<script>jQuery("#stripe_webhook_url").click(function() { jQuery(this).select(); } );</script>

