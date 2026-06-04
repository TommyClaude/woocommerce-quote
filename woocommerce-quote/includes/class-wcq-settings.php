<?php
/**
 * Plugin settings: storage, defaults and sanitization.
 *
 * All behaviour (button mode, RFQ scope, access) is driven by these options,
 * not hardcoded — see PLAN.md §6. The Settings tab (React) and REST endpoint
 * read/write through this class.
 *
 * @package WooCommerce_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WCQ_Settings
 */
class WCQ_Settings {

	/**
	 * Option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION = 'wcq_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_defaults() {
		return array(
			'button_mode'              => 'both',     // both | replace.
			'rfq_scope'                => 'all',      // all | categories | products | hidden_price | roles.
			'scope_categories'         => array(),    // term IDs.
			'scope_products'           => array(),    // product IDs.
			'scope_roles'              => array(),    // role slugs.
			'access'                   => 'guests',   // guests | login.
			'button_label'             => '',         // empty = translated default "Add to Quote".
			'recipient_emails'         => get_option( 'admin_email' ),
			'quote_page_id'            => 0,
			'remove_data_on_uninstall' => false,
		);
	}

	/**
	 * The full, merged settings array (saved values over defaults).
	 *
	 * @return array<string,mixed>
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		/**
		 * Filter the resolved plugin settings.
		 *
		 * @param array $settings Merged settings.
		 */
		return apply_filters( 'wcq_settings', wp_parse_args( $saved, self::get_defaults() ) );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback if the key is absent.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Persist a (partial) set of settings after sanitizing.
	 *
	 * @param array $values Raw values to merge and save.
	 * @return array The full saved settings.
	 */
	public static function update( array $values ) {
		$merged = wp_parse_args( $values, self::get_all() );
		$clean  = self::sanitize( $merged );
		update_option( self::OPTION, $clean );
		return $clean;
	}

	/**
	 * Sanitize a full settings array.
	 *
	 * @param array $input Raw settings.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ) {
		$defaults = self::get_defaults();
		$out      = array();

		$button_modes      = array( 'both', 'replace' );
		$out['button_mode'] = ( isset( $input['button_mode'] ) && in_array( $input['button_mode'], $button_modes, true ) )
			? $input['button_mode'] : $defaults['button_mode'];

		$scopes            = array( 'all', 'categories', 'products', 'hidden_price', 'roles' );
		$out['rfq_scope']  = ( isset( $input['rfq_scope'] ) && in_array( $input['rfq_scope'], $scopes, true ) )
			? $input['rfq_scope'] : $defaults['rfq_scope'];

		$out['scope_categories'] = isset( $input['scope_categories'] ) ? array_values( array_unique( array_map( 'absint', (array) $input['scope_categories'] ) ) ) : array();
		$out['scope_products']   = isset( $input['scope_products'] ) ? array_values( array_unique( array_map( 'absint', (array) $input['scope_products'] ) ) ) : array();
		$out['scope_roles']      = isset( $input['scope_roles'] ) ? array_values( array_unique( array_map( 'sanitize_key', (array) $input['scope_roles'] ) ) ) : array();

		$access_modes  = array( 'guests', 'login' );
		$out['access'] = ( isset( $input['access'] ) && in_array( $input['access'], $access_modes, true ) )
			? $input['access'] : $defaults['access'];

		$out['button_label']  = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : '';
		$out['quote_page_id'] = isset( $input['quote_page_id'] ) ? absint( $input['quote_page_id'] ) : 0;

		// Recipient emails: comma-separated list of valid addresses.
		$emails = isset( $input['recipient_emails'] ) ? (string) $input['recipient_emails'] : '';
		$list   = array();
		foreach ( array_map( 'trim', explode( ',', $emails ) ) as $candidate ) {
			$candidate = sanitize_email( $candidate );
			if ( $candidate && is_email( $candidate ) ) {
				$list[] = $candidate;
			}
		}
		$out['recipient_emails'] = $list ? implode( ', ', $list ) : get_option( 'admin_email' );

		$out['remove_data_on_uninstall'] = ! empty( $input['remove_data_on_uninstall'] );

		/**
		 * Filter the sanitized settings before they are stored.
		 *
		 * @param array $out   Sanitized settings.
		 * @param array $input Raw input.
		 */
		return apply_filters( 'wcq_sanitize_settings', $out, $input );
	}
}
