<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;

$this->enqueue_param( 'student_id', $student->ID );
$this->enqueue_ajax( 'record_payment_received' );
?>

<div class="dojo-student-due">
	<?php if ( Dojo_Membership::MEMBERSHIP_CANCELED_DUE == $student->status ) : ?>
		<div class="dojo-warn">
			Membership is pending cancellation but payment is past due.<br />
			<strong>Current Due Date: <?php echo $this->date('m/d/Y', strtotime( $student->next_due_date ) ) ?></strong>
		</div>
	<?php else : ?>
		<div class="dojo-warn">
			Membership payment is past due.<br />
			<strong>Current Due Date: <?php echo $this->date('m/d/Y', strtotime( $student->next_due_date ) ) ?></strong>
		</div>
	<?php endif; ?>
	<div class="dojo-clear-space"></div>
	<button class="payment-received button button-large">Record Single Month Payment Received</button>
	<div class="dojo-clear-space"></div>
</div>

