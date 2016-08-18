<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$enable_registration = $this->get_meta( 'registration_enable' );
$enable_guest        = $this->get_meta( 'registration_enable_guest' );
$enable_limit        = $this->get_meta( 'registration_enable_limit' );
$limit               = $this->get_meta( 'registration_limit' );
$enable_payment      = $this->get_meta( 'registration_enable_payment' );
$price               = $this->get_meta( 'registration_price' );
$post                = $this->post;
$registrants         = $this->registrants;
$students            = $this->students;

// index registrants by student_id
// todo - handle guests
$user_registrants = array();
foreach ( $this->user_registrants as $registrant ) {
    $user_registrants[ $registrant->student_id ] = $registrant;
}

$active_students = array();
$registered_students = array();
$unregistered_students = array();
foreach ( $students as $student ) {
    if ( Dojo_Membership::instance()->is_status_active( $student->status ) ) {
        $active_students[ $student->ID ] = $student;

        // create registered and unregistered student lists
        if ( isset( $user_registrants[ $student->ID ] ) ) {
            $registered_students[ $student->ID ] = $student;
        } else {
            $unregistered_students[ $student->ID ] = $student;
        }
    }
}

$current_registration = count( $registrants );
$is_full = $enable_limit && $current_registration >= $limit;

$line_items = array();
$checkout_table = new Dojo_Checkout_Table( $line_items, array(
    'render_simple_total'   => true,
    'headings'              => array( 'Registrant', 'Price' ),
) );

$price_plan = new Dojo_Price_Plan( $price );
$dojo_membership = Dojo_Membership::instance();

$client_params = array(
    'post_id'               => $post->ID,
    'num_students'          => count( $students ),
    'students'              => $active_students,
    'remaining_spots'       => $limit - $current_registration,
    'get_line_items_url'    => $this->ajax( 'get_line_items' ),
    'registration_url'      => $this->ajax( 'register' ),
    );

// flag to determine if it's worth giving warning messages
if ( 0 != count( $registered_students ) &&
    0 == count( $unregistered_students ) &&
    ! $enable_guest ) {
    $could_register = false;
} else {
    $could_register = true;
}

?>

<?php if ( $enable_registration ) : ?>
<div class="dojo-register">
    <?php if ( $this->is_manager ) : ?>
    <h3>Registration Info <br /><span style="font-size:.6em">(Admin View Only)</span></h3>
        <?php if ( 0 == count( $registrants ) ) : ?>
        <div class="dojo-block">
            No students registered.
        </div>
        <?php else : ?>
        <div class="dojo-block">
            <strong>Registered Students:</strong>
            <br />
            <?php foreach ( $registrants as $reg ) : ?>
                <?php echo esc_html( $dojo_membership->student_name( $reg ) ) ?>
                <br />
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <div class="dojo-clear-space"></div>
    <?php endif; ?>

    <h3>Register</h3>

    <?php if ( $enable_limit && $could_register ) : ?>
    <p class="dojo-limited-registration">
        <?php if ( $is_full ) : ?>
        <div class="dojo-danger dojo-registration-full">Registration is full</div>
        <?php else : ?>
        <div class="dojo-warn">
            Registration is limited with <?php echo $limit - $current_registration ?> space<?php echo 1 == $limit - $current_registration ? '' : 's' ?> remaining.
        </div>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if ( 0 != count( $registered_students ) ) : ?>
        <div class="dojo-info">
            <strong>Thank you for registering:</strong>
            <br />
            <?php foreach ( $registered_students as $student ) : ?>
            <div class="dojo-student-item">
                <?php echo esc_html( $student->first_name . ' ' . $student->last_name ) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="dojo-clear-space"></div>
    <?php endif; ?>

    <?php if ( ! $is_full ) : ?>
        <?php if ( 0 == count( $registered_students ) || 0 != count( $unregistered_students ) || $enable_guest ) : ?>
            <p><?php echo $price_plan->describe( $this->singular ) ?></p>
        <?php endif; ?>
        <?php if ( 0 != count( $active_students ) ) : ?>
            <?php if ( 0 != count( $unregistered_students ) ) : ?>
            <div class="dojo-container">
                <div class="dojo-row">
                    <div class="dojo-col-md-6">
                        <?php if ( 0 == count( $registered_students ) ) : ?>
                        Please select the students you would like to register.
                        <?php else : ?>
                        Please select additional students you would like to register.
                        <?php endif; ?>
                    </div>
                    <div class="dojo-col-md-6">
                        <div class="dojo-block">
                            <strong>Students Attending</strong>
                            <br />
                            <?php foreach ($unregistered_students as $student) : ?>
                            <div class="dojo-student-item">
                                <label>
                                    <input type="checkbox" id="student-<?php echo $student->ID ?>" name="student-<?php echo $student->ID ?>" value="1" data-student="<?php echo $student->ID ?>">
                                    <?php echo esc_html($student->first_name . ' ' . $student->last_name) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <div class="dojo-reg-limit-reached" style="display:none;">
                                <div class="dojo-clear-space"></div>
                                <div class="dojo-danger">Registration limit reached!</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dojo-clear-space"></div>
            <?php endif; ?>
        <?php elseif ( $enable_guest ) : ?>
            Guest registration coming soon
        <?php elseif ( ! $this->is_manager ) : ?>
            <div class="dojo-danger dojo-members-only">This event is for members only. Please <a href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login">log in as a member</a> to register.</div>
        <?php endif; ?>

        <div class="dojo-checkout-container" style="display:none;">
            <?php $checkout_table->render() ?>
            <?php echo apply_filters( 'dojo_event_register_button', $this->render( 'register-button' ), $post->ID ) ?>
        </div>

    <?php endif; ?>
</div>
<?php endif; ?>

<script type="text/javascript">
jQuery('.dojo-register').data('params', <?php echo json_encode( $client_params ) ?>);
</script>

