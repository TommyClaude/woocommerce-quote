<?php
/**
 * Plugin Name:       Request a Quote for WooCommerce
 * Plugin URI:        https://github.com/TommyClaude/woocommerce-quote
 * Description:       Let customers collect products into a quote list and submit a request for a price instead of buying immediately. Store owners manage the requests in wp-admin.
 * Version:           0.6.1
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            TommyClaude
 * Author URI:        https://github.com/TommyClaude
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocommerce-quote
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants.
// ---------------------------------------------------------------------------

/** Plugin version. Bump on every build so the user can track it. */
define( 'WCQ_VERSION', '0.6.1' );

/** Absolute path to the main plugin file. */
define( 'WCQ_PLUGIN_FILE', __FILE__ );

/** Absolute path to the plugin directory (with trailing slash). */
define( 'WCQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/** URL to the plugin directory (with trailing slash). */
define( 'WCQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** Plugin basename, e.g. "woocommerce-quote/woocommerce-quote.php". */
define( 'WCQ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ---------------------------------------------------------------------------
// Bootstrap.
// ---------------------------------------------------------------------------

require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-plugin.php';

/**
 * Whether WooCommerce is active.
 *
 * Checked against the active-plugins option so the result does not depend on
 * plugin load order, and falls back to the class check for edge cases.
 *
 * @return bool
 */
function wcq_is_woocommerce_active() {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}

	$active = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	return in_array( 'woocommerce/woocommerce.php', $active, true );
}

/**
 * Return the main plugin instance.
 *
 * @return WCQ_Plugin
 */
function wcq() {
	return WCQ_Plugin::instance();
}

// Boot the plugin once all plugins (including WooCommerce) are loaded.
add_action( 'plugins_loaded', 'wcq' );

// Declare HPOS (High-Performance Order Storage) compatibility.
add_action( 'before_woocommerce_init', 'wcq_declare_hpos_compatibility' );

/**
 * Declare compatibility with WooCommerce custom order tables (HPOS).
 *
 * @return void
 */
function wcq_declare_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			WCQ_PLUGIN_FILE,
			true
		);
	}
}

// Activation / deactivation.
register_activation_hook( WCQ_PLUGIN_FILE, array( 'WCQ_Plugin', 'activate' ) );
register_deactivation_hook( WCQ_PLUGIN_FILE, array( 'WCQ_Plugin', 'deactivate' ) );
