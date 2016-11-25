<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;
$contracts = $this->contracts;
$membership = $this->model()->get_student_membership( $student->ID );

// check if current contract is in the list (could have been disabled for new signups)
$is_active_contract = false;
foreach ( $contracts as $contract ) {
	if ( $membership->contract_id == $contract->ID ) {
		$is_active_contract = true;
		break;
	}
}
if ( ! $is_active_contract ) {
	$contracts[ $membership->contract_id ] = $this->model()->get_contract($student->membership->contract_id);
}

$this->enqueue_param( 'student_id', $student->ID );
$this->enqueue_ajax( 'change_student_contract' );
?>

<div class="dojo-student-contract">
	<h2>Membership Contract</h2>
	<p>Contract:
		<span class="dojo-current-membership">
			<strong><?php echo esc_html( $student->contract->title ) ?></strong>
			<a href="javascript:;" class="dojo-change-contract" style="margin-left:5px;">(change)</a>
		</span>
		<span class="dojo-change-membership" style="display:none;">
			<select name="contract">
				<?php foreach ( $contracts as $contract ) : ?>
					<?php if ( ! $this->is_valid_student_contract( $student, $membership, $contract ) ) { continue; } ?>
					<option value="<?php echo esc_attr( $contract->ID ) ?>" <?php selected( $membership->contract_id, $contract->ID ) ?>><?php echo esc_html( $contract->title ) ?></option>
				<?php endforeach; ?>
			</select>
			<a href="javascript:;" class="dojo-apply-change-contract" style="margin-left:5px;display:none;">Save Contract Change</a>
		</span>
	</p>

	<p>Status: <strong><?php echo esc_html( $this->describe_status( $student->status ) ) ?></strong></p>
	<?php if ( Dojo_Membership::MEMBERSHIP_DUE == $student->status || Dojo_Membership::MEMBERSHIP_CANCELED_DUE == $student->status ) : ?>
		<?php echo apply_filters( 'dojo_membership_admin_student_due', $this->render( 'admin-student-due' ), $student ) ?>
		<div class="dojo-clear-space"></div>
	<?php endif; ?>
</div>

