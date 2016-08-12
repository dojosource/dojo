<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$students = $this->students;
?>

<div class="dojo-select-list">
<?php foreach ( $students as $student ) : ?>
    <div class="dojo-select-list-item" data-id="<?php echo esc_attr( $student->ID ) ?>">
        <h3 style="width:80%;float:left;">
            <?php echo esc_html( $student->first_name . ' ' . $student->last_name . ( '' != $student->alias && $student->first_name != $student->alias ? ' (' . $student->alias . ')' : '' ) ) ?>
        </h3>
        <div style="float:left;">
            DOB: <?php echo date( 'm/d/Y', strtotime( $student->dob ) ) ?>
        </div>
        <div class="dojo-clear"></div>
        <?php if ( null !== $student->current_membership_id ) : ?>
        <p>Todo - info about current membership</p>
        <?php else : ?>
        <div>Not enrolled in a membership.</div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<button class="dojo-add-student" style="float:left;margin-right:20px;margin-bottom:20px;">Add Student</button>
<button class="dojo-enroll" style="float:left;">Enroll Students</button>

<div class="dojo-clear"></div>

<script>
jQuery(function($) {
    $('.dojo-add-student').click(function() {
        window.location = '<?php echo $this->membership_url( 'students/edit' ) ?>';
    });

    $('.dojo-select-list-item').click(function() {
        var id = $(this).attr('data-id');
        window.location = '<?php echo $this->membership_url( 'students/edit' ) ?>?student=' + id;
    });

    $('.dojo-enroll').click(function() {
        window.location = '<?php echo $this->membership_url( 'enroll' ) ?>';
    });
});
</script>

