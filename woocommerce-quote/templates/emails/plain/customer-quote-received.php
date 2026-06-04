<?php
/**
 * Customer email: quote request received (plain text).
 *
 * @package WooCommerce_Quote
 *
 * @var int    $quote_id
 * @var array  $customer
 * @var array  $items
 * @var array  $totals
 * @var string $email_heading
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

/* translators: %s: customer name. */
echo esc_html( sprintf( __( 'Hi %s,', 'woocommerce-quote' ), isset( $customer['name'] ) ? $customer['name'] : '' ) ) . "\n\n";

echo esc_html__( 'Thank you for your quote request. We have received it and will get back to you with a price soon.', 'woocommerce-quote' ) . "\n\n";

/* translators: %d: quote reference number. */
echo esc_html( sprintf( __( 'Your reference number is #%d.', 'woocommerce-quote' ), $quote_id ) ) . "\n\n";

echo esc_html__( 'Your requested products', 'woocommerce-quote' ) . "\n";
foreach ( (array) $items as $wcq_item ) {
	$wcq_qty  = isset( $wcq_item['quantity'] ) ? (int) $wcq_item['quantity'] : 0;
	$wcq_name = isset( $wcq_item['name'] ) ? $wcq_item['name'] : '';
	echo '- ' . esc_html( $wcq_name ) . ' x ' . esc_html( $wcq_qty ) . "\n";
}
