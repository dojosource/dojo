<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$students = $this->students;
$unenrolled_students = $this->unenrolled_students;
$active_students = array();
$contracts = $this->contracts;
$line_items = $this->line_items;

$checkout_table = new Dojo_Checkout_Table( $line_items, array( 'render_simple_total' => true ) );
?>

<div class="dojo-container">
    <div class="dojo-row">
        <div class="dojo-col-md-7">
            <?php if ( 0 != count( $unenrolled_students ) ) : ?>

            <h3>Select a Membership</h3>
            <?php if ( 1 == count( $unenrolled_students ) ) : ?>
            <p>Please select a membership for this student.</p>
            <?php else : ?>
            <p>Please select a membership for each student. </p>
            <?php endif; ?>

            <div class="dojo-hide-md">
                <div class="dojo-info">
                    Scroll down for more information about membership offerings.
                </div>
                <div class="dojo-clear-space"></div>
            </div>

            <form id="dojo-enroll" action="<?php echo esc_url( $this->ajax( 'enrollment_start' ) ) ?>" method="POST">
                <?php foreach ( $unenrolled_students as $student ) : ?>
                <div>
                    <label><?php echo esc_html( $student->first_name . ' ' . $student->last_name ) ?></label>
                    <select name="enrollment_<?php echo esc_attr( $student->ID ) ?>">
                        <option value="">Please Select</option>
                        <?php foreach ( $contracts as $contract ) : ?>
                        <option value="<?php echo esc_attr( $contract->ID ) ?>" <?php selected( $student->membership->contract_id, $contract->ID ) ?>><?php echo esc_html( $contract->title ) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="dojo-clear-space"></div>
                </div>
                <?php endforeach; ?>

                <div class="dojo-monthly-pricing">
                    <div class="dojo-clear-space"></div>

                    <h3>Your Monthly Pricing</h3>

                    <div class="dojo-checkout"<?php 0 == count( $line_items ) ? ' style="display:none;"' : '' ?>>
                    <?php $checkout_table->render() ?>
                    </div>

                    <button id="dojo-enroll">Apply for Membership</button>
                </div>

                <div class="dojo-clear-space"></div>

            </form>

            <div class="dojo-clear-space"></div>

            <?php endif; ?>

            <?php if ( 0 != count( $active_students ) ) : ?>
            <h3>Student Enrollment</h3>
            <div class="dojo-select-list">
                <?php foreach ( $active_students as $student ) : ?>
                <div class="dojo-select-list-item">
                    <strong><?php echo esc_html( $student->first_name . ' ' . $student->last_name ) ?></strong>
                    <br />
                    Membership: <?php echo esc_html( $contracts[ $student->membership->contract_id ]->title ) ?>
                    <br />
                    <?php echo $this->describe_status( $student->membership->status ) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>

        <div class="dojo-col-md-5">
            <div class="dojo-block">
                <h3>Membership Offerings</h3>
                <div class="dojo-select-list">
                    <?php foreach ( $contracts as $contract ) : ?>
                    <div class="dojo-select-list-item dojo-membership-details" data-id="<?php echo esc_attr( $contract->ID ) ?>">
                        <a style="float:right;margin-left:.2em" href="javascript:();">details</a>
                        <?php echo esc_html( $contract->title ) ?>

                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    $('#dojo-enroll select').change(function() {
        updateCheckout(true);
    });

    $('.dojo-membership-details').click(function() {
        window.location = '<?php echo $this->membership_url( 'enroll/details?contract=' ) ?>' + $(this).attr('data-id');
    });

    function updateCheckout(doPost) {
        var data = {};
        if (doPost) {
            $('#dojo-enroll select').each(function() {
                var selection = $(this).val();
                data[$(this).attr('name')] = $(this).val();
            });
        }
        else {
            data.refresh_only = true;
        }
        $.post('<?php echo $this->ajax('save_enrollment') ?>', data, function(response) {
            var lineItems = eval('(' + response + ')');
            dojoCheckoutSetLineItems(lineItems);
            if (0 == lineItems.length) {
                $('.dojo-monthly-pricing').hide();
            }
            else {
                $('.dojo-monthly-pricing').show();
            }
        });
    };

    // refresh to correct ajax state after browser back button
    updateCheckout(false);
});
</script>


