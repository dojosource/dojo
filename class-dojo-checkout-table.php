<?php
/**
 * Dojo utility class for rendering checkout tables with totaled line items
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Checkout_Table extends Dojo_WP_Base {
    protected $line_items = array();
    
    /**
     * Create instance of a checkout table
     * Line item structure:
     * array(
     *   array(
     *     'description' => string
     *     'amount_cents' => int
     *     'amount' => optional string
     *   )
     * )
     *
     * @param array $line_items
     * @param array $options
     */
    public function __construct( $line_items, $options = array()) {
        // options defaults
        $this->options = array(
            'amount_paid' => 0,
            'render_simple_total' => false,
        );
        $this->options = array_merge( $this->options, $options );

        $this->line_items = array();
        foreach ( $line_items as $line_item ) {
            if ( ! isset( $line_item['amount_cents'] ) ) {
                $line_item['amount_cents'] = 0;
            }
            if ( ! isset( $line_item['amount'] ) ) {
                $cents = (int) $line_item['amount_cents'];
                $line_item['amount'] = '$' . (int)( $cents / 100 ) . '.' . str_pad( $cents % 100, 2, '0', STR_PAD_LEFT);
            }
            $this->line_items[] = $line_item;
        }
    }

    public function render() {
        $total = 0;
        foreach ( $this->line_items as $index => $line_item ) {
            $total += $line_item['amount_cents'];
        }
        $amount_paid = $this->options['amount_paid'];
        $total_due = $total - $amount_paid;
        $total = '$' . (int)( $total / 100 ) . '.' . str_pad( $total % 100, 2, '0', STR_PAD_LEFT);
        $amount_paid = '$' . (int)( $amount_paid / 100 ) . '.' . str_pad( $amount_paid % 100, 2, '0', STR_PAD_LEFT);
        $total_due = '$' . (int)( $total_due / 100 ) . '.' . str_pad( $total_due % 100, 2, '0', STR_PAD_LEFT);
        ?>
        <div class="dojo-checkout-items">
            <table class="dojo-checkout-items">
                <thead>
                    <tr>
                        <th class="dojo-item-description" colspan="2">Description</th>
                        <th class="dojo-item-amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="dojo-template dojo-checkout-item" style="display:none;">
                        <td class="dojo-item-description" colspan="2"></td>
                        <td class="dojo-item-amount"></td>
                    </tr>
                    <?php foreach ( $this->line_items as $line_item ) : ?>
                    <tr class="dojo-checkout-item" data-amount="<?php echo esc_attr( $line_item['amount_cents'] ) ?>">
                        <td class="dojo-item-description" colspan="2"><?php echo esc_html( $line_item['description'] ) ?></td>
                        <td class="dojo-item-amount"><?php echo esc_html( $line_item['amount'] ) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="dojo-totals">
                        <td style="width:40%"></td>
                        <td><strong>Total</strong></td>
                        <td><strong class="dojo-item-amount"><?php echo esc_html( $total ) ?></strong></td>
                    </tr>
                    <?php if ( ! $this->options['render_simple_total'] ) : ?>
                    <tr class="dojo-checkout-amount-paid">
                        <td></td>
                        <td>Amount Paid</td>
                        <td><?php echo esc_html( $amount_paid ) ?></td>
                    </tr>
                    <tr class="dojo-total-due">
                        <td></td>
                        <td><strong>Total Due</strong></td>
                        <td><strong><?php echo esc_html( $total_due ) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
            var $dojoLineItemTemplate;
            
            function dojoSetLineItems(lineItems) {
                var $ = jQuery;
                var total = 0;
                $('.dojo-checkout-item').not('.dojo-template').remove();

                if (0 == lineItems.length) {
                    $('.dojo-checkout-items').hide();
                }
                else {
                    $('.dojo-checkout-items').show();
                    for (var index in lineItems) {
                        var lineItem = lineItems[index];
                        var price = parseInt(lineItem.amount_cents) / 100;
                        total += price;
                        var $item = $('.dojo-template.dojo-checkout-item').clone();
                        $item.removeClass('dojo-template');
                        $item.find('.dojo-item-description').text(lineItem.description);
                        $item.find('.dojo-item-amount').text('$' + price.toFixed(2));
                        $item.attr('data-id', lineItem.id);
                        $item.data('line-item', lineItem);
                        $('.dojo-checkout-items .dojo-totals').before($item);
                        $item.show();
                    }
                    $('.dojo-totals .dojo-item-amount').text('$' + total.toFixed(2));
                }
            }

            jQuery(function($) {
                $dojoLineItemTemplate = $('.dojo-template.dojo-checkout-item');
            });
        </script>
        <?php
    }
}

