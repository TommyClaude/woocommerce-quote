<?php
/**
 * Shared HTML items table for quote emails.
 *
 * @package WooCommerce_Quote
 *
 * @var array $items  Line items.
 * @var array $totals Totals snapshot.
 */

defined( 'ABSPATH' ) || exit;
?>
<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border-collapse:collapse;margin:0 0 16px;">
	<thead>
		<tr>
			<th style="text-align:left;"><?php esc_html_e( 'Product', 'woocommerce-quote' ); ?></th>
			<th style="text-align:left;"><?php esc_html_e( 'SKU', 'woocommerce-quote' ); ?></th>
			<th style="text-align:center;"><?php esc_html_e( 'Qty', 'woocommerce-quote' ); ?></th>
			<th style="text-align:right;"><?php esc_html_e( 'Price', 'woocommerce-quote' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( (array) $items as $item ) : ?>
			<?php $price = isset( $item['price'] ) ? $item['price'] : ''; ?>
			<tr>
				<td><?php echo esc_html( isset( $item['name'] ) ? $item['name'] : '' ); ?></td>
				<td><?php echo esc_html( ! empty( $item['sku'] ) ? $item['sku'] : '—' ); ?></td>
				<td style="text-align:center;"><?php echo esc_html( isset( $item['quantity'] ) ? (int) $item['quantity'] : 0 ); ?></td>
				<td style="text-align:right;">
					<?php echo ( '' === $price || null === $price ) ? '&mdash;' : wp_kses_post( wc_price( $price ) ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<?php if ( ! empty( $totals['subtotal'] ) ) : ?>
		<tfoot>
			<tr>
				<th colspan="3" style="text-align:right;"><?php esc_html_e( 'Estimated subtotal', 'woocommerce-quote' ); ?></th>
				<td style="text-align:right;"><?php echo wp_kses_post( wc_price( (float) $totals['subtotal'] ) ); ?></td>
			</tr>
		</tfoot>
	<?php endif; ?>
</table>
