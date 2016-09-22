<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$unpaid_invoices = $this->unpaid_invoices;
$student = $this->current_student;

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
wp_enqueue_style( 'dojo-invoice', $this->url( 'css/dojo-invoice.css' ) );
?>

<?php if ( 0 == count( $unpaid_invoices ) ) : ?>
    <div class="dojo-info">All paid up and good to go!</div>
    <div class="dojo-clear-space"></div>
    <div class="dojo-error dojo-danger" style="display:none;"></div>
    <button class="dojo-approve-membership button button-large">Approve Membership</button>
    <div class="dojo-clear-space"></div>

<script>
jQuery(function($) {
    $('.dojo-approve-membership').click(function() {
        var data = {
           'student': '<?php echo $student->ID ?>'
        }
        $.post('<?php echo $this->ajax( 'approve_application' ) ?>', data, function(response) {
            if (response == 'success') {
                window.location = '<?php echo admin_url( 'admin.php?page=dojo-admin' ) ?>';
            }
            else {
                $('.dojo-error').text(response).show();
            }
        });
    });
});
</script>


<?php else : ?>

    <div class="dojo-warn">There are payments due for this student's membership.</div>

    <?php foreach ( $unpaid_invoices as $invoice ) : ?>
    <div class="dojo-clear-space"></div>
    <div class="dojo-block dojo-invoice" data-invoice="<?php echo esc_attr( $invoice->ID ) ?>">
        <?php
        $line_items = $this->model()->get_invoice_line_items( $invoice->ID, ARRAY_A );
        $checkout_table = new Dojo_Checkout_Table( $line_items, array( 'amount_paid' => $invoice->amount_paid ) );
        ?>
        <strong><?php echo esc_html( $invoice->description ) ?></strong>
        <div><strong>Invoice:</strong> <?php echo esc_html( str_pad( $invoice->ID, 7, '0', STR_PAD_LEFT ) ) ?></div>
        <div><?php echo esc_html( date( 'm/d/Y', strtotime( $invoice->invoice_date ) ) ) ?></div>

        <div class="dojo-clear-space"></div>

        <div class="dojo-container">
            <?php $checkout_table->render() ?>
        </div>

        <div class="dojo-clear-space"></div>

        <button class="dojo-enter-payment">Enter Payment</button>

        <div class="dojo-payment-form dojo-block" style="display:none;">

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">Payment</th>
                        <td>
                            <input type="text" id="amount_dollars" name="amount_dollars" value="<?php echo esc_attr( $this->dollars( $invoice->amount_cents - $invoice->amount_paid ) ) ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Date</th>
                        <td>
                            <input type="text" id="payment_date" name="payment_date" value="<?php echo esc_attr( $this->date( 'm/d/Y' ) ) ?>">
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="dojo-error dojo-danger" style="display:none;"></div>
            <div class="dojo-clear-space"></div>
            <button class="dojo-apply-payment">Apply Payment</button>
            <div class="dojo-clear-space"></div>
        </div>
    </div>
    <div class="dojo-clear-sapce"></div>
    <?php endforeach; ?>

<script>
jQuery(function($) {
    $('.dojo-enter-payment').click(function() {
        var invoice = $(this).closest('.dojo-invoice');
        $(this).hide();
        invoice.find('.dojo-payment-form').show();
    });

    $('.dojo-apply-payment').click(function() {
        var invoice = $(this).closest('.dojo-invoice');
        var data = {
            invoice_id: invoice.attr('data-invoice')
        };
        invoice.find('input').each(function() {
            data[$(this).attr('name')] = $(this).val();
        });
        $.post('<?php echo $this->ajax( 'admin_apply_payment' ) ?>', data, function(response) {
            if (response == 'success') {
                window.location.reload();
            }
            else {
                console.log(response);
                invoice.find('.dojo-error').text(response).show();
            }
        });
    });

    $('input[name="payment_date"]').datepicker();
});
</script>
<?php endif; ?>