<?php
/**
 * Admin email: new quote request (plain text).
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

echo esc_html__( 'A customer has submitted a new quote request.', 'woocommerce-quote' ) . "\n\n";

/* translators: %d: quote reference number. */
echo esc_html( sprintf( __( 'Reference: #%d', 'woocommerce-quote' ), $quote_id ) ) . "\n\n";

echo esc_html__( 'Customer', 'woocommerce-quote' ) . "\n";
echo '- ' . esc_html__( 'Name', 'woocommerce-quote' ) . ': ' . esc_html( isset( $customer['name'] ) ? $customer['name'] : '' ) . "\n";
echo '- ' . esc_html__( 'Email', 'woocommerce-quote' ) . ': ' . esc_html( isset( $customer['email'] ) ? $customer['email'] : '' ) . "\n";
if ( ! empty( $customer['phone'] ) ) {
	echo '- ' . esc_html__( 'Phone', 'woocommerce-quote' ) . ': ' . esc_html( $customer['phone'] ) . "\n";
}
if ( ! empty( $customer['company'] ) ) {
	echo '- ' . esc_html__( 'Company', 'woocommerce-quote' ) . ': ' . esc_html( $customer['company'] ) . "\n";
}
if ( ! empty( $customer['message'] ) ) {
	echo "\n" . esc_html__( 'Message', 'woocommerce-quote' ) . ":\n" . esc_html( $customer['message'] ) . "\n";
}

echo "\n" . esc_html__( 'Requested products', 'woocommerce-quote' ) . "\n";
foreach ( (array) $items as $wcq_item ) {
	$wcq_qty  = isset( $wcq_item['quantity'] ) ? (int) $wcq_item['quantity'] : 0;
	$wcq_name = isset( $wcq_item['name'] ) ? $wcq_item['name'] : '';
	echo '- ' . esc_html( $wcq_name ) . ' x ' . esc_html( $wcq_qty ) . "\n";
}

if ( ! empty( $totals['subtotal'] ) ) {
	echo "\n" . esc_html__( 'Estimated subtotal', 'woocommerce-quote' ) . ': ' . esc_html( wp_strip_all_tags( wc_price( (float) $totals['subtotal'] ) ) ) . "\n";
}
