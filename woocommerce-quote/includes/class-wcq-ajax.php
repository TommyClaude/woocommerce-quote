<?php
/**
 * AJAX handlers for adding / updating / removing quote-list items.
 *
 * Every handler verifies the nonce, enforces the access setting, sanitizes
 * input and returns JSON.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Ajax
 */
class WCQ_Ajax {

	/**
	 * Constructor — register the AJAX actions (logged-in + guests).
	 */
	public function __construct() {
		$actions = array( 'add', 'update', 'remove' );
		foreach ( $actions as $action ) {
			add_action( "wp_ajax_wcq_{$action}_item", array( $this, "{$action}_item" ) );
			add_action( "wp_ajax_nopriv_wcq_{$action}_item", array( $this, "{$action}_item" ) );
		}
	}

	/**
	 * Shared guard: validate the nonce and the access setting.
	 *
	 * Sends a JSON error and exits on failure.
	 *
	 * @return void
	 */
	protected function guard() {
		if ( ! check_ajax_referer( 'wcq_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh and try again.', 'woocommerce-quote' ) ), 403 );
		}
		if ( ! wcq_user_can_request() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to request a quote.', 'woocommerce-quote' ) ), 403 );
		}
	}

	/**
	 * The quote session component.
	 *
	 * @return WCQ_Session
	 */
	protected function session() {
		return wcq()->get( 'session' );
	}

	/**
	 * Standard success payload.
	 *
	 * @param string $message User-facing message.
	 * @return array
	 */
	protected function payload( $message = '' ) {
		return array(
			'count'     => $this->session()->count(),
			'message'   => $message,
			'quote_url' => wcq_get_quote_page_url(),
		);
	}

	/**
	 * Add an item to the quote list.
	 *
	 * @return void
	 */
	public function add_item() {
		$this->guard();

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1;
		$quantity     = max( 1, $quantity );

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! ( $product instanceof WC_Product ) ) {
			wp_send_json_error( array( 'message' => __( 'Sorry, that product could not be found.', 'woocommerce-quote' ) ), 404 );
		}

		if ( ! wcq_is_product_quotable( $product ) ) {
			wp_send_json_error( array( 'message' => __( 'This product is not available for quote requests.', 'woocommerce-quote' ) ), 400 );
		}

		$this->session()->add( $product_id, $quantity, $variation_id );

		wp_send_json_success( $this->payload( __( 'Added to your quote list.', 'woocommerce-quote' ) ) );
	}

	/**
	 * Update an item's quantity.
	 *
	 * @return void
	 */
	public function update_item() {
		$this->guard();

		$key      = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$quantity = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 0;

		if ( '' === $key || ! $this->session()->update( $key, $quantity ) ) {
			wp_send_json_error( array( 'message' => __( 'That item is no longer in your quote list.', 'woocommerce-quote' ) ), 404 );
		}

		wp_send_json_success( $this->payload( __( 'Quote list updated.', 'woocommerce-quote' ) ) );
	}

	/**
	 * Remove an item.
	 *
	 * @return void
	 */
	public function remove_item() {
		$this->guard();

		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

		if ( '' === $key || ! $this->session()->remove( $key ) ) {
			wp_send_json_error( array( 'message' => __( 'That item is no longer in your quote list.', 'woocommerce-quote' ) ), 404 );
		}

		wp_send_json_success( $this->payload( __( 'Item removed.', 'woocommerce-quote' ) ) );
	}
}
