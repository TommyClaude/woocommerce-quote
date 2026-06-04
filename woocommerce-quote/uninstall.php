<?php
/**
 * Uninstall routine.
 *
 * Runs when the user deletes the plugin from wp-admin. Removes plugin options;
 * customer quote records are only purged when the store owner has explicitly
 * opted in via the "remove all data on uninstall" setting, to avoid surprise
 * data loss.
 *
 * @package WooCommerce_Quote
 */

// Exit if not called by WordPress during uninstall.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$wcq_settings = get_option( 'wcq_settings', array() );
$wcq_purge    = is_array( $wcq_settings ) && ! empty( $wcq_settings['remove_data_on_uninstall'] );

if ( $wcq_purge ) {
	global $wpdb;

	// Delete every quote-request post and its meta.
	$wcq_post_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'wcq_quote' )
	);

	foreach ( (array) $wcq_post_ids as $wcq_post_id ) {
		wp_delete_post( (int) $wcq_post_id, true );
	}
}

// Always remove plugin configuration.
delete_option( 'wcq_version' );
delete_option( 'wcq_settings' );
