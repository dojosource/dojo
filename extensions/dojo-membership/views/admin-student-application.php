<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;
?>

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

<script>
jQuery(function($) {
    var data = {
        student: '<?php echo $student->ID ?>'
    };

    $('.payment-received').click(function() {
        $.post('<?php echo $this->ajax( 'record_payment_received' ) ?>', data, function() {
            window.location.reload();
        });
    });

    $('.approve-membership').click(function() {
        $.post('<?php echo $this->ajax( 'approve_application' ) ?>', data, function() {
            window.location.reload();
        });
    });
});
</script>
