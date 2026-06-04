<?php
/**
 * Front-end: Add-to-Quote buttons, assets, and the quote-page shortcodes.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Frontend
 */
class WCQ_Frontend {

	/**
	 * Constructor — wire front-end hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Shop loop button (per-product, so scope is respected item by item).
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'loop_button' ), 20, 2 );

		// Single product button.
		add_action( 'woocommerce_single_product_summary', array( $this, 'single_button_setup' ), 1 );

		// Phase 2: hide price + block Add to Cart for quotable products when enabled.
		add_filter( 'woocommerce_get_price_html', array( $this, 'maybe_hide_price' ), 99, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'maybe_block_purchase' ), 99, 2 );

		// Shortcodes.
		add_shortcode( 'woocommerce_quote', array( $this, 'shortcode_quote' ) );
		add_shortcode( 'woocommerce_quote_count', array( $this, 'shortcode_count' ) );
	}

	/**
	 * Enqueue front-end CSS/JS and expose config to the script.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'wcq-frontend',
			WCQ_PLUGIN_URL . 'assets/css/wcq-frontend.css',
			array(),
			WCQ_VERSION
		);

		wp_enqueue_script(
			'wcq-frontend',
			WCQ_PLUGIN_URL . 'assets/js/wcq-frontend.js',
			array(),
			WCQ_VERSION,
			true
		);

		$session = wcq()->get( 'session' );

		wp_localize_script(
			'wcq-frontend',
			'wcqData',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wcq_ajax' ),
				'count'    => $session ? $session->count() : 0,
				'quoteUrl' => wcq_get_quote_page_url(),
				'i18n'     => array(
					'adding' => __( 'Adding…', 'woocommerce-quote' ),
					'added'  => __( 'Added ✓', 'woocommerce-quote' ),
					'error'  => __( 'Something went wrong. Please try again.', 'woocommerce-quote' ),
				),
			)
		);
	}

	/**
	 * Build the button markup for a product.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $context "loop" or "single".
	 * @return string Escaped HTML.
	 */
	protected function get_button_html( $product, $context ) {
		ob_start();
		wcq_get_template(
			'quote-button.php',
			array(
				'product' => $product,
				'label'   => wcq_get_button_label(),
				'context' => $context,
			)
		);
		return ob_get_clean();
	}

	/**
	 * Add or replace the shop-loop add-to-cart link with the quote button.
	 *
	 * @param string     $html    Existing add-to-cart link markup.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public function loop_button( $html, $product ) {
		if ( ! wcq_show_quote_button( $product ) ) {
			return $html;
		}

		$button = $this->get_button_html( $product, 'loop' );

		if ( 'replace' === WCQ_Settings::get( 'button_mode' ) || wcq_is_price_hidden( $product ) ) {
			return $button;
		}

		return $html . ' ' . $button;
	}

	/**
	 * Replace the price HTML with the "price on request" label when hidden.
	 *
	 * @param string     $price_html Price markup.
	 * @param WC_Product $product    Product.
	 * @return string
	 */
	public function maybe_hide_price( $price_html, $product ) {
		if ( wcq_is_price_hidden( $product ) ) {
			return '<span class="wcq-price-hidden">' . esc_html( wcq_get_hidden_price_label() ) . '</span>';
		}
		return $price_html;
	}

	/**
	 * Make quotable products non-purchasable when their price is hidden.
	 *
	 * @param bool       $purchasable Whether the product is purchasable.
	 * @param WC_Product $product     Product.
	 * @return bool
	 */
	public function maybe_block_purchase( $purchasable, $product ) {
		if ( wcq_is_price_hidden( $product ) ) {
			return false;
		}
		return $purchasable;
	}

	/**
	 * Decide how to render the button on the single product page.
	 *
	 * @return void
	 */
	public function single_button_setup() {
		global $product;

		if ( ! ( $product instanceof WC_Product ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! ( $product instanceof WC_Product ) || ! wcq_show_quote_button( $product ) ) {
			return;
		}

		if ( 'replace' === WCQ_Settings::get( 'button_mode' ) || wcq_is_price_hidden( $product ) ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_button' ), 30 );
		} else {
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_single_button' ), 15 );
		}
	}

	/**
	 * Echo the single-product quote button.
	 *
	 * @return void
	 */
	public function render_single_button() {
		global $product;
		if ( ! ( $product instanceof WC_Product ) ) {
			return;
		}
		// Markup is escaped inside the template.
		echo $this->get_button_html( $product, 'single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * `[woocommerce_quote]` — render the quote list + request form.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function shortcode_quote( $atts = array() ) {
		$session  = wcq()->get( 'session' );
		$thankyou = isset( $_GET['wcq_thankyou'] ) ? absint( wp_unslash( $_GET['wcq_thankyou'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.

		ob_start();
		wcq_get_template(
			'quote-list.php',
			array(
				'contents' => $session ? $session->get_contents() : array(),
				'totals'   => $session ? $session->get_totals() : array( 'subtotal' => 0 ),
				'thankyou' => $thankyou,
			)
		);
		return ob_get_clean();
	}

	/**
	 * `[woocommerce_quote_count]` — a live-updating item counter.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function shortcode_count( $atts = array() ) {
		$session = wcq()->get( 'session' );
		$count   = $session ? $session->count() : 0;
		return '<span class="wcq-count">' . esc_html( $count ) . '</span>';
	}
}
