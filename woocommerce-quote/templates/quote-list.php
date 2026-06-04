<?php
/**
 * Quote list + request form (output of the [woocommerce_quote] shortcode).
 *
 * Override by copying to your-theme/woocommerce-quote/quote-list.php
 *
 * @package WooCommerce_Quote
 *
 * @var array $contents Resolved quote items.
 * @var array $totals   Totals snapshot.
 * @var int   $thankyou Quote ID when showing the thank-you view, else 0.
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'wc_print_notices' ) ) {
	wc_print_notices();
}

if ( ! empty( $thankyou ) ) {
	?>
	<div class="wcq-thankyou">
		<h2><?php esc_html_e( 'Request received', 'woocommerce-quote' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s: quote reference number. */
				esc_html__( 'Thanks for your request. Your reference number is #%s. We will email you a quote shortly.', 'woocommerce-quote' ),
				esc_html( $thankyou )
			);
			?>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Continue browsing', 'woocommerce-quote' ); ?>
			</a>
		</p>
	</div>
	<?php
	return;
}
?>

<div class="wcq-quote">
	<?php if ( empty( $contents ) ) : ?>

		<p class="wcq-empty"><?php esc_html_e( 'Your quote list is empty.', 'woocommerce-quote' ); ?></p>
		<a class="button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Browse products', 'woocommerce-quote' ); ?>
		</a>

	<?php else : ?>

		<table class="wcq-items shop_table">
			<thead>
				<tr>
					<th class="wcq-col-remove">&nbsp;</th>
					<th class="wcq-col-image">&nbsp;</th>
					<th class="wcq-col-name"><?php esc_html_e( 'Product', 'woocommerce-quote' ); ?></th>
					<th class="wcq-col-price"><?php esc_html_e( 'Price', 'woocommerce-quote' ); ?></th>
					<th class="wcq-col-qty"><?php esc_html_e( 'Quantity', 'woocommerce-quote' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $contents as $row ) : ?>
					<?php
					$product = $row['product'];
					$price   = $product->get_price();
					?>
					<tr class="wcq-item" data-key="<?php echo esc_attr( $row['key'] ); ?>">
						<td class="wcq-col-remove">
							<a href="#" class="wcq-remove" data-key="<?php echo esc_attr( $row['key'] ); ?>"
								aria-label="<?php esc_attr_e( 'Remove this item', 'woocommerce-quote' ); ?>">&times;</a>
						</td>
						<td class="wcq-col-image"><?php echo wp_kses_post( $product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></td>
						<td class="wcq-col-name">
							<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
							<?php if ( $product->get_sku() ) : ?>
								<span class="wcq-sku"><?php echo esc_html( $product->get_sku() ); ?></span>
							<?php endif; ?>
						</td>
						<td class="wcq-col-price">
							<?php echo ( '' === $price || null === $price ) ? '&mdash;' : wp_kses_post( wc_price( $price ) ); ?>
						</td>
						<td class="wcq-col-qty">
							<input type="number" class="wcq-qty" min="1" step="1"
								value="<?php echo esc_attr( $row['quantity'] ); ?>"
								data-key="<?php echo esc_attr( $row['key'] ); ?>"
								aria-label="<?php esc_attr_e( 'Quantity', 'woocommerce-quote' ); ?>" />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $totals['subtotal'] ) ) : ?>
			<p class="wcq-subtotal">
				<?php esc_html_e( 'Estimated subtotal:', 'woocommerce-quote' ); ?>
				<strong><?php echo wp_kses_post( wc_price( $totals['subtotal'] ) ); ?></strong>
			</p>
		<?php endif; ?>

		<?php wcq_get_template( 'quote-form.php' ); ?>

	<?php endif; ?>
</div>
