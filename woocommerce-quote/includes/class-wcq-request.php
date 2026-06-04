<?php
/**
 * Handle a quote-request submission: validate, store as a `wcq_quote` post,
 * clear the list, then redirect to a thank-you view (PRG pattern).
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Request
 */
class WCQ_Request {

	/**
	 * Constructor — listen for submissions before content renders.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_handle' ) );
	}

	/**
	 * Process the form when it is submitted.
	 *
	 * @return void
	 */
	public function maybe_handle() {
		if ( ! isset( $_POST['wcq_action'] ) || 'submit_quote' !== $_POST['wcq_action'] ) {
			return;
		}

		// Nonce.
		if ( ! isset( $_POST['wcq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcq_nonce'] ) ), 'wcq_submit_quote' ) ) {
			wc_add_notice( __( 'Security check failed. Please try again.', 'woocommerce-quote' ), 'error' );
			return;
		}

		// Honeypot: a real user never fills this hidden field.
		if ( ! empty( $_POST['wcq_website'] ) ) {
			return;
		}

		// Access gate.
		if ( ! wcq_user_can_request() ) {
			wc_add_notice( __( 'Please log in to submit a quote request.', 'woocommerce-quote' ), 'error' );
			return;
		}

		$name    = isset( $_POST['wcq_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_name'] ) ) : '';
		$email   = isset( $_POST['wcq_email'] ) ? sanitize_email( wp_unslash( $_POST['wcq_email'] ) ) : '';
		$phone   = isset( $_POST['wcq_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_phone'] ) ) : '';
		$company = isset( $_POST['wcq_company'] ) ? sanitize_text_field( wp_unslash( $_POST['wcq_company'] ) ) : '';
		$message = isset( $_POST['wcq_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wcq_message'] ) ) : '';

		$session  = wcq()->get( 'session' );
		$contents = $session ? $session->get_contents() : array();

		// Validate.
		$has_error = false;
		if ( '' === $name ) {
			wc_add_notice( __( 'Please enter your name.', 'woocommerce-quote' ), 'error' );
			$has_error = true;
		}
		if ( '' === $email || ! is_email( $email ) ) {
			wc_add_notice( __( 'Please enter a valid email address.', 'woocommerce-quote' ), 'error' );
			$has_error = true;
		}
		if ( empty( $contents ) ) {
			wc_add_notice( __( 'Your quote list is empty.', 'woocommerce-quote' ), 'error' );
			$has_error = true;
		}
		if ( $has_error ) {
			return;
		}

		// Snapshot line items + subtotal.
		$items    = array();
		$subtotal = 0.0;
		foreach ( $contents as $row ) {
			$product = $row['product'];
			$price   = $product->get_price();
			$price   = ( '' === $price || null === $price ) ? '' : (float) $price;
			if ( '' !== $price ) {
				$subtotal += $price * $row['quantity'];
			}
			$items[] = array(
				'product_id'   => $row['product_id'],
				'variation_id' => $row['variation_id'],
				'name'         => $product->get_name(),
				'sku'          => $product->get_sku(),
				'quantity'     => $row['quantity'],
				'price'        => $price,
			);
		}

		$customer = array(
			'name'    => $name,
			'email'   => $email,
			'phone'   => $phone,
			'company' => $company,
			'message' => $message,
			'user_id' => get_current_user_id(),
			'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		$post_id = $this->create_quote( $name, $customer, $items, $subtotal );
		if ( is_wp_error( $post_id ) ) {
			wc_add_notice( __( 'Sorry, we could not save your request. Please try again.', 'woocommerce-quote' ), 'error' );
			return;
		}

		if ( $session ) {
			$session->clear();
		}

		/**
		 * Fires after a quote request is stored — emails hook here.
		 *
		 * @param int   $post_id  The new wcq_quote post ID.
		 * @param array $customer Customer fields.
		 * @param array $items    Line items snapshot.
		 */
		do_action( 'wcq_quote_created', $post_id, $customer, $items );

		wc_add_notice( __( 'Thank you! Your quote request has been sent. We will get back to you soon.', 'woocommerce-quote' ), 'success' );

		$base     = wcq_get_quote_page_url();
		$base     = $base ? $base : home_url( '/' );
		$redirect = add_query_arg( 'wcq_thankyou', $post_id, $base );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Create the `wcq_quote` post and store its meta.
	 *
	 * @param string $name     Customer name (for the title).
	 * @param array  $customer Customer fields.
	 * @param array  $items    Line items.
	 * @param float  $subtotal Subtotal snapshot.
	 * @return int|WP_Error Post ID or error.
	 */
	protected function create_quote( $name, array $customer, array $items, $subtotal ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => WCQ_CPT::POST_TYPE,
				'post_status' => 'wcq-new',
				/* translators: %s: customer name. */
				'post_title'  => sprintf( __( 'Quote — %s', 'woocommerce-quote' ), $name ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Now that we have an ID, set the final numbered title.
		wp_update_post(
			array(
				'ID'         => $post_id,
				/* translators: 1: quote ID, 2: customer name. */
				'post_title' => sprintf( __( 'Quote #%1$d — %2$s', 'woocommerce-quote' ), $post_id, $name ),
			)
		);

		update_post_meta( $post_id, '_wcq_customer', $customer );
		update_post_meta( $post_id, '_wcq_items', $items );
		update_post_meta(
			$post_id,
			'_wcq_totals',
			array(
				'subtotal' => $subtotal,
				'currency' => get_woocommerce_currency(),
			)
		);

		return $post_id;
	}
}
