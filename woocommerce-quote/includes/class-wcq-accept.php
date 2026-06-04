<?php
/**
 * Accept-a-quote handler (Phase 2).
 *
 * A tokenized link in the "Your quote" email lets the customer accept; that
 * creates a pending WooCommerce order from the quoted line prices and sends the
 * customer to the standard payment page.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Accept
 */
class WCQ_Accept {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_accept' ) );
	}

	/**
	 * Handle an accept link.
	 *
	 * Authenticated by the per-quote secret token (these are email links, so a
	 * WordPress nonce is not applicable).
	 *
	 * @return void
	 */
	public function maybe_accept() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- token-authenticated email link.
		if ( empty( $_GET['wcq_accept'] ) || empty( $_GET['wcq_token'] ) ) {
			return;
		}
		$quote_id = absint( wp_unslash( $_GET['wcq_accept'] ) );
		$token    = sanitize_text_field( wp_unslash( $_GET['wcq_token'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $quote_id ) {
			return;
		}

		$post   = get_post( $quote_id );
		$stored = get_post_meta( $quote_id, '_wcq_accept_token', true );

		if ( ! $post || WCQ_CPT::POST_TYPE !== $post->post_type || ! $stored || ! hash_equals( (string) $stored, (string) $token ) ) {
			wc_add_notice( __( 'This quote link is invalid or has expired.', 'woocommerce-quote' ), 'error' );
			return;
		}

		// Already accepted: send the customer to the existing order's payment page.
		$existing = (int) get_post_meta( $quote_id, '_wcq_order_id', true );
		if ( $existing ) {
			$order = wc_get_order( $existing );
			if ( $order ) {
				wp_safe_redirect( $order->get_checkout_payment_url() );
				exit;
			}
		}

		if ( 'wcq-quoted' !== get_post_status( $quote_id ) ) {
			wc_add_notice( __( 'This quote can no longer be accepted.', 'woocommerce-quote' ), 'error' );
			return;
		}

		$order = $this->create_order( $quote_id );
		if ( is_wp_error( $order ) || ! $order ) {
			wc_add_notice( __( 'Sorry, we could not create your order. Please contact us.', 'woocommerce-quote' ), 'error' );
			return;
		}

		update_post_meta( $quote_id, '_wcq_order_id', $order->get_id() );
		$this->set_status( $quote_id, 'wcq-accepted' );

		/**
		 * Fires after a quote is accepted and its order created.
		 *
		 * @param int $quote_id Quote ID.
		 * @param int $order_id Order ID.
		 */
		do_action( 'wcq_quote_accepted', $quote_id, $order->get_id() );

		wp_safe_redirect( $order->get_checkout_payment_url( true ) );
		exit;
	}

	/**
	 * Create a pending order from a quote's items at the quoted prices.
	 *
	 * @param int $quote_id Quote ID.
	 * @return WC_Order|WP_Error|false
	 */
	protected function create_order( $quote_id ) {
		$customer = (array) get_post_meta( $quote_id, '_wcq_customer', true );
		$items    = (array) get_post_meta( $quote_id, '_wcq_items', true );
		$quote    = (array) get_post_meta( $quote_id, '_wcq_quote', true );
		$prices   = isset( $quote['prices'] ) && is_array( $quote['prices'] ) ? $quote['prices'] : array();

		$order = wc_create_order( array( 'status' => 'pending' ) );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		foreach ( $items as $i => $item ) {
			$product_id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : ( isset( $item['product_id'] ) ? $item['product_id'] : 0 );
			$product    = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$qty  = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
			$unit = ( isset( $prices[ $i ] ) && '' !== $prices[ $i ] )
				? (float) $prices[ $i ]
				: (float) ( isset( $item['price'] ) ? $item['price'] : 0 );
			$line = $unit * $qty;

			$order->add_product(
				$product,
				$qty,
				array(
					'subtotal' => $line,
					'total'    => $line,
				)
			);
		}

		if ( ! empty( $customer['email'] ) ) {
			$order->set_billing_email( $customer['email'] );
		}
		if ( ! empty( $customer['phone'] ) ) {
			$order->set_billing_phone( $customer['phone'] );
		}
		if ( ! empty( $customer['company'] ) ) {
			$order->set_billing_company( $customer['company'] );
		}
		if ( ! empty( $customer['name'] ) ) {
			$parts = preg_split( '/\s+/', trim( $customer['name'] ), 2 );
			$order->set_billing_first_name( $parts[0] );
			if ( isset( $parts[1] ) ) {
				$order->set_billing_last_name( $parts[1] );
			}
		}
		if ( ! empty( $customer['user_id'] ) ) {
			$order->set_customer_id( (int) $customer['user_id'] );
		}

		$order->calculate_totals();
		/* translators: %d: quote ID. */
		$order->add_order_note( sprintf( __( 'Created from accepted quote #%d.', 'woocommerce-quote' ), $quote_id ) );
		$order->update_meta_data( '_wcq_quote_id', $quote_id );
		$order->save();

		return $order;
	}

	/**
	 * Force a quote status without the Publish-box interfering.
	 *
	 * @param int    $quote_id Quote ID.
	 * @param string $new      New status.
	 * @return void
	 */
	protected function set_status( $quote_id, $new ) {
		$old = get_post_status( $quote_id );
		if ( $old === $new ) {
			return;
		}
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_status' => $new ), array( 'ID' => $quote_id ) );
		clean_post_cache( $quote_id );
		do_action( 'wcq_quote_status_changed', $quote_id, $new, $old );
	}
}
