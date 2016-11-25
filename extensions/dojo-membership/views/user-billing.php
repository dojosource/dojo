<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$billing_day = $this->billing_day;

$this->enqueue_param( 'membership_url', $this->membership_url( '' ) );
$this->enqueue_ajax( 'save_billing_options' );
?>

<div class="dojo-user-billing">
	<div class="dojo-billing-options">
		<div class="dojo-field">
			<div class="dojo-name">Billing Day</div>
			<div class="dojo-value">
				<div>Select which day of the month to process recurring payments.</div>
				<select class="dojo-small-select" name="billing_day">
					<?php for ( $day = 1; $day <= 28; $day ++ ) : ?>
					<option value="<?php echo $day ?>" <?php selected( $day, $billing_day ) ?>>
						<?php echo $day . $this->date( 'S', strtotime( '1/' . $day . '/2000' ) ) ?>
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
</div>


