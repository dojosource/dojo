<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$submitted_memberships = $this->submitted_memberships;
$paid_memberships = $this->paid_memberships;
$due_memberships = $this->due_memberships;
?>

<?php if ( ! empty( $paid_memberships ) ) : ?>
	<?php foreach ( $paid_memberships as $membership ) : ?>
	<div class="dojo-block">
		<div class="dojo-right">
			<a class="button button-large" href="<?php echo esc_attr( admin_url( 'admin.php?page=dojo-students&action=edit&student=' . $membership->student_id . '&return_url=' . urlencode( admin_url( 'admin.php?page=dojo-admin' ) ) ) ) ?>">Open</a>
		</div>
		<h3>Member Application - <span class="dojo-green">Paid</span></h3>
		<strong>
			<?php echo esc_html( $membership->first_name . ' ' . $membership->last_name . ( $membership->alias ? ' (' . $membership->alias . ') ' : '' ) ) ?>
		</strong>
	</div>
	<?php endforeach; ?>

	<div class="dojo-clear-space"></div>
<?php endif; ?>


<?php if ( ! empty( $submitted_memberships ) ) : ?>
	<?php foreach ( $submitted_memberships as $membership ) : ?>
	<div class="dojo-block">
		<a class="button button-large" href="<?php echo esc_attr( admin_url( 'admin.php?page=dojo-students&action=edit&student=' . $membership->student_id . '&return_url=' . urlencode( admin_url( 'admin.php?page=dojo-admin' ) ) ) ) ?>" style="float:right;">Open</a>
		<h3>Member Application - <span class="dojo-red">Not Paid</span></h3>
		<strong>
			<?php echo esc_html( $membership->first_name . ' ' . $membership->last_name . ( $membership->alias ? ' (' . $membership->alias . ') ' : '' ) ) ?>
		</strong>
	</div>
	<?php endforeach; ?>

	<div class="dojo-clear-space"></div>
<?php endif; ?>

<?php if ( ! empty( $due_memberships ) ) : ?>
	<?php foreach ( $due_memberships as $membership ) : ?>
	<div class="dojo-block">
		<a class="button button-large" href="<?php echo esc_attr( admin_url( 'admin.php?page=dojo-students&action=edit&student=' . $membership->student_id . '&return_url=' . urlencode( admin_url( 'admin.php?page=dojo-admin' ) ) ) ) ?>" style="float:right;">Open</a>
		<h3>Membership Alert - <span class="dojo-red">Payment Due</span></h3>
		<strong>
			<?php echo esc_html( $membership->first_name . ' ' . $membership->last_name . ( $membership->alias ? ' (' . $membership->alias . ') ' : '' ) ) ?>
		</strong>
	</div>
	<?php endforeach; ?>

	<div class="dojo-clear-space"></div>
<?php endif; ?>

<?php if ( empty( $paid_memberships ) && empty( $submitted_memberships ) && empty( $due_memberships ) ) : ?>
	<div class="dojo-info">No alerts to report at this time. Check back here for membership alerts.</div>
<?php endif; ?>
