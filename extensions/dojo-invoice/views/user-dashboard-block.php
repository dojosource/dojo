<?php

if ( ! defined( 'ABSPATH' ) ) { die(); }

$invoices = $this->invoices;
$invoices_paid = $this->invoices_paid;
$invoices_not_paid = $this->invoices_not_paid;

$dojo_membership = Dojo_Membership::instance();
?>

<?php if ( 0 != count( $invoices ) ) : ?>

    <h3>Invoices</h3>
    <?php if ( 0 != count( $invoices_not_paid ) ) : ?>
        <div class="dojo-select-list dojo-invoices">
        <?php foreach ( $invoices_not_paid as $invoice ) : ?>
            <div class="dojo-select-list-item" data-invoice="<?php echo esc_attr( $invoice->ID ) ?>">
                <?php echo esc_html( $invoice->description ) ?>
                <div class="dojo-right">$<?php echo esc_html( $this->dollars( $invoice->amount_cents ) ) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="dojo-info">You are all paid up!</div>
        <div class="dojo-clear-space"></div>
    <?php endif; ?>

    <button class="dojo-invoice-view-all">My Invoices</button>

    <script>
        jQuery(function($) {
            $('.dojo-invoices .dojo-select-list-item').click(function() {
                window.location = '<?php echo $dojo_membership->membership_url( 'invoice?id=' ) ?>' + $(this).attr( 'data-invoice' );
            });

            $('.dojo-invoice-view-all').click(function() {
                window.location = '<?php echo $dojo_membership->membership_url( 'invoices' ) ?>';
            });
        });
    </script>
<?php endif; ?>


