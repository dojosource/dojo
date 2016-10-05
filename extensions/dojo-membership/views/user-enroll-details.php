<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$contract = $this->selected_contract;
$price_plan = $this->contract_price_plan;
$contract_programs = $this->contract_programs;

?>

<div class="dojo-container">
	<h2>Contract Term</h2>
	<p>This is a <?php echo $contract->term_months ?> month membership<?php echo $contract->new_memberships_only ? ' for new members only' : '' ?>.</p>

	<h3>Pricing</h3>
	<p><?php echo $price_plan->describe( 'membership' ) ?></p>

	<h3>Cancellation Policy</h3>
	<p><?php echo $this->describe_cancellation_policy( $contract ) ?></p>

	<h3>Programs</h3>
	<p>The following programs are included in this membership</p>
	<?php foreach( $contract_programs as $program ) : ?>
	<div class="dojo-block">
		<h4><?php echo esc_html( $program->title ) ?></h4>
		<p><?php echo esc_html( $program->description ) ?></p>
	</div>
	<div class="dojo-clear-space"></div>
	<?php endforeach; ?>
</div>

