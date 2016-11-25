<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;

$this->enqueue_param( 'student_id', $student->ID );

$this->enqueue_ajax( 'record_payment_received' );
$this->enqueue_ajax( 'approve_application' );
?>

<div class="dojo-membership-student-application">
	<?php if ( Dojo_Membership::MEMBERSHIP_SUBMITTED == $student->status ) : ?>
		<div class="dojo-warn">Initial payment is due.</div>
		<div class="dojo-clear-space"></div>
		<button class="payment-received button button-large">Record Payment Received</button>
		<div class="dojo-clear-space"></div>
	<?php elseif ( Dojo_Membership::MEMBERSHIP_PAID == $student->status ) : ?>
		<div class="dojo-info">All paid up and good to go!</div>
		<div class="dojo-clear-space"></div>
		<button class="approve-membership button button-large">Approve Membership</button>
		<div class="dojo-clear-space"></div>
	<?php endif; ?>
</div>

