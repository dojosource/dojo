<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;
if ( null === $student ) {
	die( '<h2>Student Not Found</h2>' );
}

$student_name = Dojo_Membership::instance()->student_name( $student );

$rank_types = $this->rank_types;

wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
?>

<div class="wrap dojo dojo-students-edit">
	<h1><?php echo esc_html( $student_name ) ?></h1>

	<?php if ( Dojo_Membership::MEMBERSHIP_SUBMITTED == $student->status || Dojo_Membership::MEMBERSHIP_PAID == $student->status ) : ?>
	<div class="dojo-block">
		<h2>Membership Application</h2>
		<p>Applying for membership: <strong><?php echo esc_html( $student->contract->title ) ?></strong></p>
		<?php echo apply_filters( 'dojo_membership_admin_student_application', $this->render( 'admin-student-application' ), $student ) ?>
	</div>
	<?php elseif ( $this->is_status_active( $student->status ) ) : ?>
	<div class="dojo-block">
		<?php echo apply_filters( 'dojo_membership_admin_student_contract', $this->render( 'admin-student-contract' ), $student ) ?>
	</div>
	<?php endif; ?>

	<form name="post" action="<?php echo esc_attr( $this->ajax( 'save_student' ) ) ?>" method="post" id="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" id="student_id" name="student_id" value="<?php echo esc_attr( $student->ID ) ?>">
		<input type="hidden" name="is_admin" value="1">

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">First Name</th>
					<td>
						<input type="text" id="first_name" name="first_name" class="regular-text" value="<?php echo esc_attr( $student->first_name ) ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Last Name</th>
					<td>
						<input type="text" id="last_name" name="last_name" class="regular-text" value="<?php echo esc_attr( $student->last_name ) ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Goes By</th>
					<td>
						<input type="text" id="alias" name="alias" class="regular-text" value="<?php echo esc_attr( $student->alias ) ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Birth Date</th>
					<td>
						<input type="text" id="dob" name="dob" value="<?php echo esc_attr( $this->date( 'm/d/Y', strtotime( $student->dob ) ) ) ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Rank</th>
					<td>
						<?php if ( 0 == count( $rank_types ) ) : ?>
						<div class="dojo-info">No rank types have been created yet.</div>
						<?php else : ?>
							<?php foreach ( $rank_types as $rank_type ) : ?>
							<label for="rank_type_<?php echo $rank_type->ID ?>" style="display:block;margin-top:5px;"><?php echo esc_html( $rank_type->title ) ?>:</label>
							<select id="rank_type_<?php echo $rank_type->ID ?>" name="rank_type_<?php echo $rank_type->ID ?>">
								<?php foreach ( $rank_type->ranks as $rank ) : ?>
								<option value="<?php echo $rank->ID ?>" <?php selected( $rank->ID, $rank_type->student_rank->ID ) ?>><?php echo esc_html( $rank->title ) ?></option>
								<?php endforeach; ?>
							</select>
							<?php endforeach; ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary button-large save-student-button">Save Student</button>
		</p>
	</form>
</div>

