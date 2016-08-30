<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$day = $this->billing_day;

// add english suffix to day
$billing_day = $day . date( 'S', strtotime( '1/' . $day . '/2000' ) );
?>

<a href="<?php echo esc_attr( Dojo_Membership::instance()->membership_url( 'billing' ) ) ?>" class="dojo-right dashicons-before dashicons-edit">edit</a>
<h3>Billing</h3>
<div class="dojo-field">
    Automatic payments occur on the <strong><?php echo $billing_day ?></strong> of every month.
</div>
<?php echo apply_filters( 'dojo_membership_user_dashboard_billing', '' ) ?>

