<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$pending_students = $this->pending_students;
$contracts = $this->contracts;
?>

<div class="dojo-container">
	<?php if ( 0 != count( $pending_students ) ) : ?>

	<form name="post" action="<?php echo esc_attr( $this->ajax( 'submit_application' ) ) ?>" method="post" id="post" autocomplete="off">
		<?php if ( 1 == count( $pending_students ) ) : ?>
		<p>Please review and complete the membership application for this student.</p>
		<?php else : ?>
		<p>Please review and complete the membership applications for these students.</p>
		<?php endif; ?>

		<?php foreach ( $pending_students as $student ) : ?>
		<input type="hidden" name="student_<?php echo esc_attr( $student->ID ) ?>" value="<?php echo esc_attr( $student->ID ) ?>">
		<?php $contract = $contracts[ $student->membership->contract_id ] ?>
		<div class="dojo-block">
			<h3><?php echo esc_html( $student->first_name . ' ' . $student->last_name ) ?></h3>
			<strong>Membership:</strong> <?php echo esc_html( $contract->title ) ?>

			<div class="dojo-clear-space"></div>

			<strong>Pricing:</strong>
			<br />
			<?php echo esc_html( $contract->pricing->describe( 'membership', 'per month' ) ) ?>
			<?php if ( $contract->pricing->family_pricing_enabled() ) : ?>
			<br />
			Pricing will be determined month-to-month based on active family members in each month.
			<?php endif; ?>

			<?php if ( ! empty( $contract->terms_url ) ) : ?>
			<div class="dojo-clear-space"></div>
			<strong>Terms and Conditions</strong>
			<br />
			<label>
				<input type="checkbox" class="terms-checkbox" name="terms-<?php echo esc_attr( $student->ID ) ?>">
				I have read and agree with the <a href="<?php echo esc_attr( $contract->terms_url ) ?>" target="_blank">Terms and Conditions</a> for this membership.
			</label>
			<?php endif; ?>

			<?php if ( ! empty( $contract->documents ) ) : ?>
			<div class="dojo-clear-space"></div>
			<strong>Download Forms</strong>
			<br />
			Please download and complete these forms.
			<table style="margin-bottom:20px;">
				<tbody>
					<?php foreach ( $contract->documents as $document ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $document->title ) ?></strong>
						</td>
						<td>
							<a href="<?php echo esc_attr( Dojo::instance()->url_of( 'docs/' . $document->ID . '/' . $document->filename ) ) ?>" download>download</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<div class="dojo-clear-space"></div>
		<?php endforeach; ?>

	</form>

	<div class="error-container" style="display:none;">
		<div class="error-message dojo-danger"></div>
		<div class="dojo-clear-space"></div>
	</div>

	<button class="submit-application">Submit Application</button>
	<div class="dojo-please-wait" style="display:none;">Please wait...</div>
	<?php else : ?>
	<h2>No Pending Applications</h2>
	<?php endif; ?>
</div>

<script>
jQuery(function($) {
	$('.submit-application').click(function() {
		var unchecked = $('.terms-checkbox').not(':checked');
		if (unchecked.length) {
			$('.error-message').text('Please indicate that you have read and agree with the terms and conditions for each membership that requires it.');
			$('.error-container').show();
		}
		else {
			$('.error-container').hide();
			$('.submit-application').hide();
			$('.dojo-please-wait').show();
			$('#post').submit();
		}
	});
});
</script>


