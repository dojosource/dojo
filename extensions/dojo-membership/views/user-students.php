<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$students = $this->students;

$this->enqueue_param( 'students_edit_url', $this->membership_url( 'students/edit' ) );
$this->enqueue_param( 'enroll_url', $this->membership_url( 'enroll' ) );
?>

<div class="dojo-user-students">
	<div class="dojo-select-list">
	<?php foreach ( $students as $student ) : ?>
		<div class="dojo-select-list-item" data-id="<?php echo esc_attr( $student->ID ) ?>">
			<h3 style="width:80%;float:left;">
				<?php echo esc_html( $student->first_name . ' ' . $student->last_name . ( '' != $student->alias && $student->first_name != $student->alias ? ' (' . $student->alias . ')' : '' ) ) ?>
			</h3>
			<div style="float:left;">
				DOB: <?php echo $this->date( 'm/d/Y', strtotime( $student->dob ) ) ?>
			</div>
			<div class="dojo-clear"></div>
			<?php if ( null !== $student->current_membership_id ) : ?>
				<?php echo esc_html( $this->describe_status( $student->status ) ) ?>
			<?php else : ?>
			<div>Not enrolled in a membership.</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	</div>
	<button class="dojo-add-student" style="float:left;margin-right:20px;margin-bottom:20px;">Add Student</button>
	<button class="dojo-enroll" style="float:left;">Enroll Students</button>

	<div class="dojo-clear"></div>
</div>


