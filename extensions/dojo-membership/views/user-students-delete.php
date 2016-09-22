<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

wp_enqueue_style( 'member-signup', $this->url( 'css/member-signup.css' ) );

if ( ! isset( $this->current_student ) ) {
    echo '<h2>Student Not Found</h2>';
    die();
}

$student = $this->current_student;
?>

<h2>Are you sure you want to delete this student?</h2>

<form id="dojo-delete-student" action="<?php echo esc_url( $this->ajax( 'delete_student' ) ) ?>" method="POST">
    <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->ID ) ?>">
    <div class="dojo-membership-student-block">
        <h3 style="width:80%;float:left;">
            <?php echo esc_html( $student->first_name . ' ' . $student->last_name . ( '' != $student->alias && $student->first_name != $student->alias ? ' (' . $student->alias . ')' : '' ) ) ?>
        </h3>
        <div style="float:left;">
            DOB: <?php echo $this->date( 'm/d/Y', strtotime( $student->dob ) ) ?>
        </div>
        <div class="dojo-clear"></div>
        <?php if ( null !== $student->current_membership_id ) : ?>
        <p><?php echo esc_html( $this->describe_status( $student->status ) ) ?></p>
        <?php else : ?>
        <div>Not enrolled in any programs.</div>
        <?php endif; ?>
    </div>

    <button type="submit">Delete Student</button>
</form>

