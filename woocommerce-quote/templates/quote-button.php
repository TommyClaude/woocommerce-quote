<?php
/**
 * Add-to-Quote button.
 *
 * Override by copying to your-theme/woocommerce-quote/quote-button.php
 *
 * @package WooCommerce_Quote
 *
 * @var WC_Product $product The product.
 * @var string     $label   Button label.
 * @var string     $context "loop" or "single".
 */

defined( 'ABSPATH' ) || exit;
?>
<button type="button"
	class="button wcq-add-to-quote wcq-button-<?php echo esc_attr( $context ); ?>"
	data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
	data-context="<?php echo esc_attr( $context ); ?>">
	<?php echo esc_html( $label ); ?>
</button>
