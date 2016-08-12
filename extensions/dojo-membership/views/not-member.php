<?php
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
wp_enqueue_style( 'member-signup', $this->url( 'css/member-signup.css' ) );
$s = 0;
?>

<form id="dojo-membership" action="<?= esc_url( $this->ajax( 'start-membership' ) ) ?>" method="POST">
    <div class="dojo-membership-configure-students dojo-membership">
        <h2 style="text-align: center;">Membership Sign Up</h2>
        <input type="hidden" id="action" name="action" value="dojo_purchase_membership">
        <div class="dojo-membership-field">
            <label for="dojo-membership-students">How many family members are participating?</label>
            <select id="dojo-membership-students" name="students">
                <option value="0">-- Please Select --</option>
                <option value="1" rel="95">1 Person $95 / Month</option>
                <option value="2" rel="150">2 People $150 / Month</option>
                <option value="3" rel="200">3 People $200 / Month</option>
                <option value="4" rel="200">4 People $200 / Month</option>
                <option value="5" rel="200">5 People $200 / Month</option>
            </select>
        </div>
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <div class="dojo-membership-student-block dojo-membership-student<?= $s ?>" style="display:none;">
            <h3>Student #<?= $s ?></h3>
            <div class="dojo-membership-field">
                <label for="student<?= $s ?>-first">First Name</label>
                <input type="text" id="student<?= $s ?>-first" name="student<?= $s ?>-first" placeholder="First Name">
            </div>
            <div class="dojo-membership-field">
                <label for="student<?= $s ?>-last">Last Name</label>
                <input type="text" id="student<?= $s ?>-last" name="student<?= $s ?>-last" placeholder="Last Name">
            </div>
            <div class="dojo-membership-field">
                <label for="student<?= $s ?>-alias">Name you go by</label>
                <input type="text" id="student<?= $s ?>-alias" name="student<?= $s ?>-alias">
            </div>
            <div class="dojo-membership-field">
                <label for="student<?= $s ?>-dob">Birth Date (mm/dd/yyyy)</label>
                <input class="dojo-membership-date" type="text" id="student<?= $s ?>-dob" name="student<?= $s ?>-dob" placeholder="DOB">
            </div>
        </div>
        <?php endfor; ?>
        <div class="dojo-error dojo-membership-student-error"></div>

        <div class="dojo-membership-center-button" style="display:none;">
            <button class="dojo-membership-checkout-button">Continue to Checkout</button>
        </div>
    </div>

    <div class="dojo-membership-checkout dojo-membership" style="display:none;">
        <h3><strong>Membership Plan:</strong> <span class="dojo-membership-plan"></span></h3>
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <div class="dojo-membership-student-summary dojo-membership-student<?= $s ?>">
            <div class="dojo-membership-summary-name"></div>
            <div class="dojo-membership-summary-dob"></div>
        </div>
        <?php endfor; ?>

        <div class="dojo-membership-center-button">
            <a href="javascript:;" class="dojo-membership-go-back-button">Go Back</a>
        </div>

        <div class="dojo-membership-summary">
            <div class="dojo-membership-summary-price">
                Total <strong><span class="dojo-membership-price"></span></strong> / Month for <strong>12 Months</strong>
            </div>
            <div class="dojo-membership-summary-policy">
                * 60 days notice required for early cancellation.
            </div>
            <div class="dojo-membership-summary-policy">
                * After 12 Months you can cancel at any time.
            </div>
        </div>

        <div class="dojo-membership-center-coupon">
            <input type="text" id="coupon" name="coupon" placeholder="Coupon Code">
            <button class="dojo-membership-apply-coupon clear">Apply</button>
        </div>
        <div class="dojo-membership-coupon-result"></div>

        <div class="dojo-membership-charge-day">
            <div>Please pick a day that you would like us to process your payment each month.</div>
            <select id="dojo-membership-charge-day" name="charge-day">
                <option value="0">-- Please Select --</option>
                <option value="1">1st of the month</option>
                <option value="7">7th of the month</option>
                <option value="15">15th of the month</option>
                <option value="22">22nd of the month</option>
            </select>
        </div>

        <div class="dojo-membership-first-charge-date"></div>

        <div class="dojo-spacer"></div>

        <table class="dojo-membership-checkout-items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="dojo-membership-plan"></td>
                    <td class="dojo-membership-original-price"></td>
                </tr>
                <tr class="dojo-membership-discounts" style="display:none;">
                    <td class="dojo-membership-discount-description"></td>
                    <td class="dojo-membership-discount-amount"></td>
                </tr>
                <tr class="dojo-membership-totals">
                    <td>Total</td>
                    <td class="dojo-membership-price"></td>
                </tr>
            </tbody>
        </table>

        <div class="dojo-membership-terms">
            <label for="terms">
                <input type="checkbox" id="terms" name="terms" value="1">
                I have read and agree with the <a target="_blank" href="/membership-terms">membership terms</a>
            </label>
        </div>

        <div class="dojo-error dojo-membership-purchase-error"></div>

        <div class="dojo-membership-center-button">
            <button class="dojo-membership-purchase-button">Purchase Membership</button>
        </div>

        <div class="dojo-membership-please-wait" style="display:none;">Processing, please wait....</div>
    </div>
</form>

<?php /*
<script src="https://checkout.stripe.com/checkout.js"></script>
*/
?>

<script>
jQuery(function($) {
    $('input[name=coupon]').keyup(function(e) {
        e.preventDefault();
        if (e.keyCode == 13) {
            $('.dojo-membership-apply-coupon').click();
        }
    });

    $('.dojo-membership-go-back-button').click(function() {
        $('.dojo-membership-checkout').hide();
        $('.dojo-membership-configure-students').show();
        $('html, body').animate({
            scrollTop: $('#dojo-membership').offset().top - 100 
        }, 0);
    });

    $('.dojo-membership-apply-coupon').click(function(e) {
        e.preventDefault();
        var data = {
            coupon: $('input[name=coupon]').val()
            };

        $.post('<?= $this->ajax( 'check_coupon' ) ?>', data, function(response) {
            var info = eval('(' + response + ')');
            var originalPrice = $('select[name=students] option:selected').attr('rel');

            if (info.result != 'success') {
                $('.dojo-membership-coupon-result').text('Coupon not valid.');
                $('.dojo-membership-price').text('$' + originalPrice);
                $('.dojo-membership-summary-price .dojo-membership-price').removeClass('dojo-green');
                $('.dojo-membership-price').attr('rel', originalPrice + '00');
                $('.dojo-membership-discounts').hide();
            }
            else {
                $('.dojo-membership-center-coupon').hide();
                $('.dojo-membership-coupon-result').text('Applying ' + info.description);
                var discountPrice = parseFloat(originalPrice) * info.multiplier;
                $('.dojo-membership-price').text('$' + discountPrice.toFixed(2));
                $('.dojo-membership-summary-price .dojo-membership-price').addClass('dojo-green');
                $('.dojo-membership-price').attr('rel', ('' + discountPrice.toFixed(2)).replace('.', ''));

                // update final checkout table
                var discountAmount = parseFloat(originalPrice) * (1 - info.multiplier);
                $('.dojo-membership-discount-description').text(info.description);
                $('.dojo-membership-discount-amount').text('- $' + discountAmount.toFixed(2));
                $('.dojo-membership-discounts').show();
            }
        });
    });

    $('.dojo-membership-checkout-button').click(function(e) {
        e.preventDefault();
        var numStudents = parseInt($('select[name=students]').val());
        $('.dojo-membership-student-error').hide();
        for (var s = 1; s <= numStudents; s++) {
            var first = $('input[name=student' + s + '-first]').val();
            var last = $('input[name=student' + s + '-last]').val();
            var dob = $('input[name=student' + s + '-dob]').val();

            if (first == '' || last == '' || dob == '') {
                $('.dojo-membership-student-error').text('Please provide full name and birth date for each student.');
                $('.dojo-membership-student-error').show();
                return;
            }
            var name = first + ' ' + last;
            var alias = $('input[name=student' + s + '-alias]').val();
            if (alias != '' && alias != first) {
                name += ' (' + alias + ')';
            }

            $('.dojo-membership-student' + s + ' .dojo-membership-summary-name').text(name);
            $('.dojo-membership-student' + s + ' .dojo-membership-summary-dob').text('DOB: ' + dob);
        }

        var price = $('select[name=students] option:selected').attr('rel');
        $('.dojo-membership-plan').text($('select[name=students] option:selected').text());
        $('.dojo-membership-price').text('$' + price);
        $('.dojo-membership-price').attr('rel', price + '00');
        $('.dojo-membership-original-price').text('$' + price);

        $('.dojo-membership-checkout').show();
        $('.dojo-membership-configure-students').hide();

        $('html, body').animate({
            scrollTop: $('#dojo-membership').offset().top - 100 
        }, 0);

        if ($('input[name=coupon]').val() != '') {
            $('.dojo-membership-apply-coupon').click();
        }
    });

    var today = new Date();
    $('.dojo-membership-date').datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: '1930:' + today.getFullYear()
    });

    $('#dojo-membership-students').change(function() {
        var count = parseInt($(this).val());
        for (var s = 1; s <= 5; s++) {
            if (s <= count) {
                $('.dojo-membership-student' + s).show();
            }
            else {
                $('.dojo-membership-student' + s).hide();
            }
        }

        if (count > 0) {
            $('.dojo-membership-center-button').show();
        }
        else {
            $('.dojo-membership-center-button').hide();
        }
    });

    $('#dojo-membership-charge-day').change(function() {
        var day = parseInt($(this).val());
        var data = {
            'charge-day': day
        };
        $.post('<?= $this->ajax( 'get_charge_date' ) ?>', data, function(response) {
            $('.dojo-membership-first-charge-date').html(response);
        });
 
    });

    function copyToAlias(s) {
        $('input[name=student' + s + '-first]').change(function() {
            $('input[name=student' + s + '-alias]').val($(this).val());
        });
    }
    for (var s = 1; s <= 5; s++) {
        copyToAlias(s);
    }

/*
    var stripeHandler = StripeCheckout.configure({
        key: '<?= $data['stripeKey'] ?>',
        locale: 'auto',
        token: function(token) {
            $('.dojo-membership-purchase-button').hide();
            $('.dojo-membership-please-wait').show();
            token.action = 'skc_complete_purchase';
            token.students = $('select[name=students]').val();
            $.post('<?= admin_url('admin-ajax.php') ?>', token, function(response) {
                if (response != 'success') {
                    $('.dojo-error').html(response);
                    $('.dojo-error').show();
                    $('.dojo-membership-please-wait').hide();
                    $('.dojo-membership-purchase-button').show();
                }
                else {
                    // purchase completed, refresh the page to get member dashboard
                    window.location.reload();
                }
            });
        }
    });

    // Close Checkout on page navigation:
    $(window).on('popstate', function() {
        stripeHandler.close();
    });
*/

    $('.dojo-membership-purchase-button').click(function(e) {
        e.preventDefault();
        $('.dojo-membership-purchase-error').hide();
    
        if ($('select[name=charge-day]').val() == '0') {
            $('.dojo-membership-purchase-error').text('Please select a day you would like us to process your payment each month.');
            $('.dojo-membership-purchase-error').show();
            return;
        }

        if (!$('input[name=terms]').is(':checked')) {
            $('.dojo-membership-purchase-error').text('Please indicate that you have read and agree to the membership terms.');
            $('.dojo-membership-purchase-error').show();
            return;
        }

        var data = {};
        $('#dojo-membership').find('input,select').each(function() {
            var name = $(this).attr('name');
            var val = $(this).val();
            data[name] = val;
        });

        console.log('data', data);

        $.post('<?= admin_url('admin-ajax.php') ?>', data, function(response) {
            if (response != 'success') {
                alert('response', response);
                $('.dojo-error').html(response);
                $('.dojo-error').show();
            }
            else {
                var people = $('select[name=students] option:selected').val();
                stripeHandler.open({
                    name: 'SKC Martial Arts',
                    description: people + ' Person Membership',
                    amount: $('dojo-membership-price').attr('rel'),
                    email: '<?php /* str_replace(array('\\', '\''), array('', ''), $data['email']) */  ?>'
                });
            }
        });

    });
});
</script>


