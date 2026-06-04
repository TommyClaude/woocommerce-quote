<?php
/**
 * Core template + logic helpers, available globally for templates and classes.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Locate and load a plugin template, allowing theme overrides.
 *
 * Themes can override by copying the file into
 * `your-theme/woocommerce-quote/<name>`.
 *
 * @param string $name Template file name (e.g. "quote-list.php").
 * @param array  $args Variables to expose to the template.
 * @return void
 */
function wcq_get_template( $name, $args = array() ) {
	if ( ! function_exists( 'wc_get_template' ) ) {
		return;
	}
	wc_get_template( $name, $args, 'woocommerce-quote/', WCQ_PLUGIN_DIR . 'templates/' );
}

/**
 * Whether the current visitor is allowed to request quotes.
 *
 * Driven by the "access" setting (guests vs login required).
 *
 * @return bool
 */
function wcq_user_can_request() {
	$allowed = true;

	if ( 'login' === WCQ_Settings::get( 'access' ) && ! is_user_logged_in() ) {
		$allowed = false;
	}

	/**
	 * Filter whether the current user may request a quote.
	 *
	 * @param bool $allowed Whether requests are allowed.
	 */
	return (bool) apply_filters( 'wcq_user_can_request', $allowed );
}

/**
 * Whether a given product is eligible for Request-a-Quote, per the RFQ scope.
 *
 * @param WC_Product|int $product Product object or ID.
 * @return bool
 */
function wcq_is_product_quotable( $product ) {
	if ( ! ( $product instanceof WC_Product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! ( $product instanceof WC_Product ) ) {
		return false;
	}

	// For variations, category/price checks should consider the parent product.
	$parent = $product;
	if ( $product->is_type( 'variation' ) ) {
		$maybe_parent = wc_get_product( $product->get_parent_id() );
		if ( $maybe_parent instanceof WC_Product ) {
			$parent = $maybe_parent;
		}
	}

	$scope = WCQ_Settings::get( 'rfq_scope' );
	$ok    = false;

	switch ( $scope ) {
		case 'all':
			$ok = true;
			break;

		case 'categories':
			$cats = array_map( 'intval', (array) WCQ_Settings::get( 'scope_categories' ) );
			$ok   = ! empty( $cats ) && has_term( $cats, 'product_cat', $parent->get_id() );
			break;

		case 'products':
			$ids = array_map( 'intval', (array) WCQ_Settings::get( 'scope_products' ) );
			$ok  = in_array( $product->get_id(), $ids, true ) || in_array( $parent->get_id(), $ids, true );
			break;

		case 'hidden_price':
			$price = $parent->get_price();
			$ok    = ( '' === $price || null === $price );
			break;

		case 'roles':
			$roles = (array) WCQ_Settings::get( 'scope_roles' );
			$user  = wp_get_current_user();
			$ok    = ( $user && $user->exists() && array_intersect( $roles, (array) $user->roles ) );
			break;
	}

	/**
	 * Filter whether a product is quotable.
	 *
	 * @param bool       $ok      Eligibility result.
	 * @param WC_Product $product The product being checked.
	 */
	return (bool) apply_filters( 'wcq_is_product_quotable', $ok, $product );
}

/**
 * Whether the Add-to-Quote button should be shown for a product to this visitor.
 *
 * @param WC_Product|int $product Product object or ID.
 * @return bool
 */
function wcq_show_quote_button( $product ) {
	return wcq_user_can_request() && wcq_is_product_quotable( $product );
}

/**
 * URL of the page that renders the quote list, or empty string if unset.
 *
 * @return string
 */
function wcq_get_quote_page_url() {
	$page_id = (int) WCQ_Settings::get( 'quote_page_id' );

	if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
		return (string) get_permalink( $page_id );
	}

	return '';
}

/**
 * The effective Add-to-Quote button label (translated default when unset).
 *
 * @return string
 */
function wcq_get_button_label() {
	$label = (string) WCQ_Settings::get( 'button_label' );
	return '' !== $label ? $label : __( 'Add to Quote', 'woocommerce-quote' );
}
