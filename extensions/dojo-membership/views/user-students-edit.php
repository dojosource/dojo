<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
wp_enqueue_style( 'member-signup', $this->url( 'css/member-signup.css' ) );

$student = null;
$membership = null;
$contract = null;
$contract_programs = null;
$is_submitted = false;
if ( isset( $this->current_student ) ) {
    $student = $this->current_student;
    $membership = $this->current_membership;
    $new = false;

    if ( isset( $this->current_contract ) ) {
        $contract = $this->current_contract;
        $contract_programs = $this->contract_programs;
    }
    $is_submitted = $this->is_status_submitted( $student->status );
} else {
    $new = true;
}
?>

<?php if ( ! $is_submitted ) : ?>

<?php if ( $new ) : ?>
<h2>Add New Student</h2>
<?php else : ?>
<h2>Edit Student</h2>
<?php endif; ?>

<form id="dojo-membership-student" action="<?= esc_url( $this->ajax( 'save_student' ) ) ?>" method="POST">
    <?php if ( ! $new ) : ?>
    <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->ID ) ?>">
    <?php endif; ?>
    
    <div class="dojo-membership-student-block">
        <div class="dojo-membership-field">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" placeholder="First Name"<?php echo $new ? '' : ' value="' . esc_attr( $student->first_name ) . '"' ?> required>
        </div>
        <div class="dojo-membership-field">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name"<?php echo $new ? '' : ' value="' . esc_attr( $student->last_name ) . '"' ?> required>
        </div>
        <div class="dojo-membership-field">
            <label for="alias">Name you go by</label>
            <input type="text" id="alias" name="alias"<?php echo $new ? '' : ' value="' . esc_attr( $student->alias ) . '"' ?> required>
        </div>
        <div class="dojo-membership-field">
            <label for="dob">Birth Date (mm/dd/yyyy)</label>
            <input class="dojo-membership-date" type="text" id="dob" name="dob" placeholder="DOB"<?php echo $new ? '' : ' value="' . date('m/d/Y', strtotime( $student->dob ) ) . '"' ?> required>
        </div>
    </div>
    
    <div class="dojo-error dojo-membership-student-error" style="display:none;"></div>

    <button type="submit" class="dojo-membership-save-student" style="margin-right:20px;">Save Student</button>
    <?php if ( ! $new ) : ?>
    <a class="dojo-red-link" href="<?php echo esc_attr( $this->membership_url( 'students/delete?student=' . $student->ID ) ) ?>"><span class="dashicons dashicons-trash" style="vertical-align:middle;"></span> delete student</a>
    <?php endif; ?>
</form>

<script>
jQuery(function($) {
    var today = new Date();
    $('.dojo-membership-date').datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '1930:' + today.getFullYear()
    });

    $('input[name=first_name]').change(function() {
        $('input[name=alias]').val($(this).val());
    });
});
</script>

<?php else : ?>

<h2><?php echo esc_html( $this->student_name( $student ) ) ?></h2>
<div class="dojo-student-dob">DOB: <?php echo date('m/d/Y', strtotime( $student->dob ) ) ?></div>

<div class="dojo-clear-space"></div>

<h3>Membership</h3>

<?php if ( $contract ) : ?>
<div class="dojo-field">
    <div class="dojo-name">Membership:</div>
    <div class="dojo-value"><?php echo esc_html( $contract->title ) ?></div>
</div>
<?php endif; ?>

<div class="dojo-field">
    <div class="dojo-name">Status:</div>
    <div class="dojo-value"><?php echo esc_html( $this->describe_status( $membership->status ) ) ?></div>
</div>

<?php if ( $contract ) : ?>
    <div class="dojo-field">
        <div class="dojo-name">Contract Term:</div>
        <div class="dojo-value">This is a <?php echo $contract->term_months ?> month membership.
            Started <?php echo date( 'm/d/Y', strtotime( $membership->start_date ) ) ?>.
        </div>
    </div>

    <div class="dojo-field">
        <div class="dojo-name">Cancellation Policy:</div>
        <div class="dojo-value"><?php echo $this->describe_cancellation_policy( $contract ) ?></div>
    </div>

    <?php if ( $this->is_status_active( $membership->status ) && ! $this->is_status_canceled( $membership->status ) ) : ?>
        <?php if (
            Dojo_Membership::CANCELLATION_ANYTIME   == $contract->cancellation_policy ||
            Dojo_Membership::CANCELLATION_DAYS      == $contract->cancellation_policy ) : ?>
            <div class="dojo-field dojo-cancel-contract">
                <button>Cancel Membership</button>
            </div>

            <div class="dojo-cancel-error-container" style="display:none;">
                <div class="dojo-clear-space"></div>
                <div class="dojo-cancel-error dojo-error"></div>
            </div>

            <div class="dojo-field dojo-confirm-cancel" style="display:none;">
                <div class="dojo-name">&nbsp;</div>
                <div class="dojo-value"><strong>Are you sure you want to cancel this membership?</strong> <a href="javascript:;" class="dojo-red-link">Yes cancel.</a></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="dojo-clear-space"></div>

    <h3>Programs</h3>
    <p>The following programs are included in this membership</p>
    <?php foreach( $contract_programs as $program ) : ?>
    <div class="dojo-block">
        <h4><?php echo esc_html( $program->title ) ?></h4>
        <p><?php echo esc_html( $program->description ) ?></p>
    </div>
    <div class="dojo-clear-space"></div>
    <?php endforeach; ?>

<script>
jQuery(function($) {
    $('.dojo-cancel-contract button').click(function() {
        $('.dojo-cancel-contract').hide();
        $('.dojo-confirm-cancel').show();
    });

    $('.dojo-confirm-cancel a').click(function() {
        var data = {
            'membership_id': '<?php echo $membership->ID ?>'
        }
        $.post('<?php echo $this->ajax( 'cancel_membership' ) ?>', data, function(response) {
            if (response == 'success') {
                window.location.reload();
            }
            else {
                $('.dojo-cancel-error').text(response);
                $('.dojo-cancel-error-container').show();
            }
        });
    });
});
</script>
<?php endif; ?>

<?php endif; ?>
