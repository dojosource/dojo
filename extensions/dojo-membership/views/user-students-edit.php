<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
wp_enqueue_style( 'member-signup', $this->url( 'css/member-signup.css' ) );

$student = null;
if ( isset( $this->current_student ) ) {
    $student = $this->current_student;
    $new = false;
} else {
    $new = true;
}
?>

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
    
    <div class="dojo-error dojo-membership-student-error"></div>

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


