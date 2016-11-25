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
			'headings' => array( 'Description', 'Amount' ),
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
		Dojo::instance()->enqueue_param( 'line_items', $this->line_items );

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
		<div class="dojo-checkout-items"<?php echo 0 == count( $this->line_items ) ? ' style="display:none;"' : '' ?>>
			<table class="dojo-checkout-items">
				<thead>
					<tr>
						<th class="dojo-item-description" colspan="2"><?php echo esc_html( $this->options['headings'][0] ) ?></th>
						<th class="dojo-item-amount"><?php echo esc_html( $this->options['headings'][1] ) ?></th>
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
						<td class="dojo-empty-bl" style="width:40%"></td>
						<td><strong>Total</strong></td>
						<td><strong class="dojo-item-amount"><?php echo esc_html( $total ) ?></strong></td>
					</tr>
					<?php if ( ! $this->options['render_simple_total'] ) : ?>
					<tr class="dojo-checkout-amount-paid">
						<td class="dojo-empty-bl"></td>
						<td>Amount Paid</td>
						<td><?php echo esc_html( $amount_paid ) ?></td>
					</tr>
					<tr class="dojo-total-due">
						<td class="dojo-empty-bl"></td>
						<td><strong>Total Due</strong></td>
						<td><strong><?php echo esc_html( $total_due ) ?></strong></td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

