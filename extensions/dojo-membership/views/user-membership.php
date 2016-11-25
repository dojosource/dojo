<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$is_new = $this->is_new;
$ready_to_enroll = $this->ready_to_enroll;
$students = $this->students;
$notifications = $this->notifications;

$this->enqueue_param( 'students_edit_url', $this->membership_url( 'students/edit' ) );
$this->enqueue_param( 'enroll_url', $this->membership_url( 'enroll' ) );
?>

<?php if ( $is_new ) : ?>
<h2>How to get started</h2>
<ol>
	<li>Add all the students in your household</li>
	<li>Select a membership for each student</li>
</ol>
<?php endif; ?>

<?php if ( isset( $_GET['application_submitted'] ) ) : ?>
<div class="dojo-info">
	Thank you for submitting your membership application!
</div>
<div class="dojo-clear-space"></div>
<?php endif; ?>

<div class="dojo-container dojo-user-membership">
	<div class="dojo-row">
		<div class="dojo-col-md-6">
			<h3>Students</h3>
			<?php if ( 0 == count( $students ) ) : ?>
			<p>Get started by adding students here.</p>
			<?php endif; ?>
			<div class="dojo-select-list dojo-students">
			<?php foreach ( $students as $student ) : ?>
				<div class="dojo-select-list-item" data-id="<?php echo esc_attr( $student->ID ) ?>">
					<a style="float:right;margin-left:.2em" class="dashicons-before dashicons-edit" href="javascript:();">edit</a>
					<strong>
						<?php echo esc_html( $student->first_name . ' ' . $student->last_name . ( '' != $student->alias && $student->first_name != $student->alias ? ' (' . $student->alias . ')' : '' ) ) ?>
					</strong>
					<br />
					<?php echo esc_html( $this->describe_status( $student->membership->status ) ) ?>
				</div>
			<?php endforeach; ?>
			</div>
			<button class="dojo-add-student">Add Student</button>
			<?php if ( $ready_to_enroll ) : ?>
			<button class="dojo-enroll" style="margin-left:20px;">Enroll</button>
			<?php endif; ?>
			<div class="dojo-clear-space"></div>
			<div class="dojo-clear-space"></div>
			<?php foreach ( $this->user_dashboard_blocks[ Dojo_Membership::USER_DASHBOARD_LEFT ] as $block_name => $owner ) : ?>
			<div class="dojo-user-dashboard-block">
				<?php $owner->render_user_dashboard_block( $block_name ) ?>
				<div class="dojo-clear-space"></div>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="dojo-col-md-6">
			<h3>Notifications</h3>
			<?php if ( 0 == count( $notifications ) ) : ?>
			<div class="dojo-info">No notifications to report at this time.</div>
			<?php else : ?>
				<?php foreach ( $notifications as $notification_html ) : ?>
					<div class="dojo-warn"><?php echo $notification_html ?></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<div class="dojo-clear-space"></div>

			<?php if ( $this->has_active_membership ) : ?>
				<?php echo $this->render( 'user-dashboard-billing' ) ?>
				<div class="dojo-clear-space"></div>
			<?php endif; ?>

			<?php foreach ( $this->user_dashboard_blocks[ Dojo_Membership::USER_DASHBOARD_RIGHT ] as $block_name => $owner ) : ?>
			<div class="dojo-user-dashboard-block">
				<?php $owner->render_user_dashboard_block( $block_name ) ?>
				<div class="dojo-clear-space"></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

