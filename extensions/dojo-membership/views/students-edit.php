<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$student = $this->selected_student;
if ( null === $student ) {
    die( '<h2>Student Not Found</h2>' );
}

$student_name = Dojo_Membership::instance()->student_name( $student );

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
?>

<div class="wrap dojo">
    <h1><?php echo esc_html( $student_name ) ?></h1>

    <?php if ( Dojo_Membership::MEMBERSHIP_SUBMITTED == $student->status || Dojo_Membership::MEMBERSHIP_PAID == $student->status ) : ?>
    <div class="dojo-block">
        <h2>Membership Application</h2>
        <p>Applying for membership: <strong><?php echo esc_html( $student->contract->title ) ?></strong></p>
        <?php echo apply_filters( 'dojo_membership_admin_student_application', 'TODO - member application without invoice', $student ) ?>
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
                        <input type="text" id="dob" name="dob" value="<?php echo esc_attr( date( 'm/d/Y', strtotime( $student->dob ) ) ) ?>">
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary button-large save-student-button">Save Student</button>
        </p>
    </form>
</div>

<script>
jQuery(function($) {
    var today = new Date();
    $('input[name=dob]').datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '1930:' + today.getFullYear()
    });
});
</script>