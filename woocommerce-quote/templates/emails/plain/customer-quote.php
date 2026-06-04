<?php
/**
 * Customer email: your quote (plain text).
 *
 * @package WooCommerce_Quote
 *
 * @var int    $quote_id
 * @var array  $customer
 * @var array  $items
 * @var array  $quote
 * @var string $accept_url
 * @var string $email_heading
 */

defined( 'ABSPATH' ) || exit;

$wcq_prices = isset( $quote['prices'] ) && is_array( $quote['prices'] ) ? $quote['prices'] : array();
$wcq_total  = isset( $quote['total'] ) ? (float) $quote['total'] : 0;
$wcq_note   = isset( $quote['note'] ) ? $quote['note'] : '';

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

/* translators: %s: customer name. */
echo esc_html( sprintf( __( 'Hi %s,', 'woocommerce-quote' ), isset( $customer['name'] ) ? $customer['name'] : '' ) ) . "\n\n";
echo esc_html__( 'Thank you for your interest. Here is your quote:', 'woocommerce-quote' ) . "\n\n";

foreach ( (array) $items as $i => $item ) {
	$wcq_qty  = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
	$wcq_unit = ( isset( $wcq_prices[ $i ] ) && '' !== $wcq_prices[ $i ] ) ? (float) $wcq_prices[ $i ] : (float) ( isset( $item['price'] ) ? $item['price'] : 0 );
	echo '- ' . esc_html( isset( $item['name'] ) ? $item['name'] : '' ) . ' x ' . esc_html( $wcq_qty )
		. ' @ ' . esc_html( wp_strip_all_tags( wc_price( $wcq_unit ) ) ) . "\n";
}

echo "\n" . esc_html__( 'Total', 'woocommerce-quote' ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $wcq_total ) ) ) . "\n";

if ( '' !== $wcq_note ) {
	echo "\n" . esc_html__( 'Note', 'woocommerce-quote' ) . ":\n" . esc_html( $wcq_note ) . "\n";
}

if ( $accept_url ) {
	echo "\n" . esc_html__( 'Accept your quote and check out:', 'woocommerce-quote' ) . "\n" . esc_url_raw( $accept_url ) . "\n";
}
