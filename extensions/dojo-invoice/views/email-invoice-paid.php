<?php
if ( ! defined( 'ABSPATH' ) ) { die(); }

$invoice = $this->invoice;
$line_items = $this->line_items;
$checkout_table = new Dojo_Checkout_Table( $line_items, array( 'amount_paid' => $invoice->amount_paid ) );
?>

<?php if ( null === $invoice ) : ?>
<h2>Error: Invoice not found</h2>
<?php else : ?>
<div style="float:right;text-align:right;">
    <div><strong>Invoice:</strong> <?php echo esc_html( str_pad( $invoice->ID, 7, '0', STR_PAD_LEFT ) ) ?></div>
    <div><?php echo esc_html( $this->date( 'm/d/Y', strtotime( $this->invoice->invoice_date ) ) ) ?></div>
</div>

<h2><?php echo esc_html( $invoice->description ) ?></h2>
<br />
<br />
<br />
<?php $checkout_table->render() ?>

<?php endif; ?>
