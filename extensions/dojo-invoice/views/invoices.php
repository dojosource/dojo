<?php
$invoices = $this->invoices;
$invoices_paid = $this->invoices_paid;
$invoices_not_paid = $this->invoices_not_paid;
?>

<div class="dojo-container">
    <div class="dojo-select-list">
        <?php foreach ( $invoices as $invoice ) : ?>
        <div class="dojo-select-list-item" data-invoice="<?php echo esc_attr( $invoice->ID ) ?>">
            <div class="dojo-left-space">
                <span style="font-size:1.2em"><strong><?php echo esc_html( $invoice->description ) ?></strong></span>
                <br />
                Invoice <?php echo esc_html( str_pad( $invoice->ID, 7, '0', STR_PAD_LEFT ) ) ?>
                <br />
                <?php echo esc_html( $this->date( 'm/d/Y', strtotime( $invoice->invoice_date ) ) ) ?>
            </div>
            <div class="dojo-right">
                <div style="text-align:right">
                    <strong>$<?php echo esc_html( $this->dollars( $invoice->amount_cents ) ) ?></strong>
                </div>
                <?php if ( $invoice->amount_paid < $invoice->amount_cents ) : ?>
                <div class="dojo-red dojo-text-right"><strong>Payment Due</strong></div>
                <?php else : ?>
                <div class="dojo-green dojo-text-right">Paid</div>
                <?php endif; ?>
            </div>
            <div class="dojo-clear"></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
jQuery(function($) {
    $('.dojo-select-list-item').click(function() {
        var invoice_id = $(this).attr('data-invoice');
        window.location = '<?php echo Dojo_Membership::instance()->membership_url( '/invoice?id=' ) ?>' + invoice_id;
    });
});
</script>

