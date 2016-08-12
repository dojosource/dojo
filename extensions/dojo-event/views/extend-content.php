<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$enable_registration = $this->get_meta( 'registration_enable' );
$enable_guest        = $this->get_meta( 'registration_enable_guest' );
$enable_limit        = $this->get_meta( 'registration_enable_limit' );
$limit               = $this->get_meta( 'registration_limit' );
$enable_payment      = $this->get_meta( 'registration_enable_payment' );

$price = array();
$price_count = array();
for ( $rule = 1; $rule <= 5; $rule ++ ) {
    $price[ $rule ]       = $this->get_meta( 'registration_price' . $rule );
    $price_count[ $rule ] = $this->get_meta( 'registration_price_count' . $rule );
}

// todo
$current_registration = 5;
$is_full = $enable_limit && $current_registration >= $limit;

$is_member = false;
$user = null;
$students = array();
if ( is_user_logged_in() ) {
    $subscription = SKCModel::getCurrentSubscription();
    if ( null !== $subscription && 1 == $subscription->isActive ) {
        $is_member = true;
        $user = wp_get_current_user();
        $students = SKCModel::getMemberStudents($user->ID);
    }
}

$students_by_id = array();
foreach ( $students as $s ) {
    $students_by_id [ $s->ID ] = $s;
}

$client_params = array(
    'is_member'     => $is_member,
    'num_students'  => count( $students ),
    'students'      => $students_by_id,
    'price'         => $price,
    'price_count'   => $price_count,
    );
?>

<?php if ( $enable_registration ) : ?>
<div class="dojo-register">
    <h3>Register</h3>

    <?php if ( $enable_limit ) : ?>
    <p class="dojo-limited-registration">
        <?php if ( $is_full ) : ?>
        <div class="dojo-danger dojo-registration-full">Registration is full</div>
        <?php else : ?>
        <div class="dojo-warn">
            Registration is limited with <?php echo $limit - $current_registration ?> spaces remaining.
        </div>
        <?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if ( ! $is_full ) : ?>
        <p><?php echo $this->get_event_cost_description() ?></p>
        <?php if ( $is_member ) : ?>
            <div class="dojo-container">
            <?php if ( 1 == count( $students ) ) : ?>
            <div class="dojo-pre-register-click">
                <p>
                    Would you like to register <?php echo esc_html($students[ 0 ]->firstName . ' ' . $students[ 0 ]->lastName) ?> for this <?php echo $this->singular ?>?
                </p>
            </div>
            <button id="dojo-register">Register <?php echo esc_html($students[ 0 ]->firstName . ' ' . $students[ 0 ]->lastName) ?></button>
            <?php else : ?>
                <div class="dojo-row">
                    <div class="dojo-col-md-6">
                        Please select the students you would like to register.
                    </div>
                    <div class="dojo-col-md-6">    
                        <?php foreach ($students as $student) : ?>
                        <div class="dojo-student-item">
                            <label>
                                <input type="checkbox" id="student-<?php echo $student->ID ?>" name="student-<?php echo $student->ID ?>" value="1" data-student="<?php echo $student->ID ?>">
                                <?php echo esc_html($student->firstName . ' ' . $student->lastName) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            </div>
            <div class="dojo-clear-space"></div>
        <?php elseif ( $enable_guest ) : ?>
            Guest coming soon
        <?php else : ?>
            <div class="dojo-danger dojo-members-only">This is for memberes only. <a href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login">Log in as member</a> to register.</div>
        <?php endif; ?>

        <div class="dojo-checkout-items" style="display:none;">
            <table class="dojo-checkout-items">
                <thead>
                    <tr>
                        <th class="dojo-item-description">Description</th>
                        <th class="dojo-item-amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="dojo-template dojo-checkout-item" style="display:none;">
                        <td class="dojo-item-description"></td>
                        <td class="dojo-item-amount"></td>
                    </tr>
                    <tr class="dojo-totals">
                        <td class="dojo-item-description">Total</td>
                        <td class="dojo-item-amount"></td>
                    </tr>
                </tbody>
            </table>
            <button id="dojo-register-pay">Register &amp; Pay</button>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script type="text/javascript">
jQuery('.dojo-register').data('params', <?php echo json_encode( $client_params ) ?>);
</script>

