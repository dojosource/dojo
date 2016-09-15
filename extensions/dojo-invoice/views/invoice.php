<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$invoice = $this->invoice;
$line_items = $this->line_items;
$checkout_table = new Dojo_Checkout_Table( $line_items, array( 'amount_paid' => $invoice->amount_paid ) );
?>

<?php if ( null === $invoice ) : ?>
<h2>Invoice not found</h2>
<?php else : ?>
<div class="dojo-right" style="text-align:right;">
    <div><strong>Invoice:</strong> <?php echo esc_html( str_pad( $invoice->ID, 7, '0', STR_PAD_LEFT ) ) ?></div>
    <div><?php echo esc_html( $this->date( 'm/d/Y', strtotime( $this->invoice->invoice_date ) ) ) ?></div>
</div>

<h2><?php echo esc_html( $invoice->description ) ?></h2>

<div class="dojo-clear-space"></div>
<div class="dojo-clear-space"></div>

<div class="dojo-container">
<?php $checkout_table->render() ?>
</div>

<?php if ( $invoice->amount_cents > $invoice->amount_paid ) : ?>
<?php echo apply_filters( 'dojo_invoice_payment', '', $invoice ) ?>
<?php else: ?>
<div class="dojo-info">Thank you for your payment!</div>
<?php endif; ?>

<?php endif; ?>

