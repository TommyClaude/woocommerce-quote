<?php
/**
 * Main plugin loader.
 *
 * Wires the plugin together: loads dependencies, guards against a missing
 * WooCommerce install, loads the text domain and boots each component.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Plugin
 *
 * Singleton bootstrap for the whole plugin.
 */
final class WCQ_Plugin {

	/**
	 * Single shared instance.
	 *
	 * @var WCQ_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Component instances, keyed by short name.
	 *
	 * @var array<string,object>
	 */
	protected $components = array();

	/**
	 * Retrieve (and lazily create) the shared instance.
	 *
	 * @return WCQ_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor — load includes and wire hooks.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load class files.
	 *
	 * @return void
	 */
	private function includes() {
		require_once WCQ_PLUGIN_DIR . 'includes/wcq-core-functions.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-settings.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-cpt.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-session.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-ajax.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-frontend.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-request.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-admin.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-rest.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-emails.php';
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-accept.php';
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Text domain loads regardless of WooCommerce so notices are translatable.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Everything else depends on WooCommerce being active.
		if ( ! wcq_is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		add_action( 'plugins_loaded', array( $this, 'init_components' ), 20 );

		/**
		 * Fires after the plugin has booted and WooCommerce is confirmed active.
		 *
		 * @param WCQ_Plugin $plugin The plugin instance.
		 */
		do_action( 'wcq_loaded', $this );
	}

	/**
	 * Instantiate the plugin components.
	 *
	 * @return void
	 */
	public function init_components() {
		$this->components['cpt']      = new WCQ_CPT();
		$this->components['session']  = new WCQ_Session();
		$this->components['ajax']     = new WCQ_Ajax();
		$this->components['frontend'] = new WCQ_Frontend();
		$this->components['request']  = new WCQ_Request();
		$this->components['admin']    = new WCQ_Admin();
		$this->components['rest']     = new WCQ_REST();
		$this->components['emails']   = new WCQ_Emails();
		$this->components['accept']   = new WCQ_Accept();

		/**
		 * Fires once every component has been instantiated.
		 *
		 * @param WCQ_Plugin $plugin The plugin instance.
		 */
		do_action( 'wcq_components_loaded', $this );
	}

	/**
	 * Access a loaded component by key.
	 *
	 * @param string $key Component key (e.g. "cpt").
	 * @return object|null
	 */
	public function get( $key ) {
		return isset( $this->components[ $key ] ) ? $this->components[ $key ] : null;
	}

	/**
	 * Load the plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'woocommerce-quote',
			false,
			dirname( WCQ_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Request a Quote for WooCommerce', 'woocommerce-quote' ); ?></strong>
				&mdash;
				<?php esc_html_e( 'WooCommerce must be installed and active for this plugin to work.', 'woocommerce-quote' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Activation routine.
	 *
	 * Registers the post type so rewrite rules are correct, flushes them, and
	 * records the installed version.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once WCQ_PLUGIN_DIR . 'includes/class-wcq-cpt.php';

		WCQ_CPT::register_post_statuses();
		WCQ_CPT::register_post_type();

		flush_rewrite_rules();

		if ( ! get_option( 'wcq_version' ) ) {
			add_option( 'wcq_version', WCQ_VERSION );
		} else {
			update_option( 'wcq_version', WCQ_VERSION );
		}
	}

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Prevent cloning of the singleton.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the singleton.
	 *
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Unserializing WCQ_Plugin is not allowed.' );
	}
}
