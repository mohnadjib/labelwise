<?php
/**
 * Remove plugin data on uninstall.
 *
 * @package Labelwise
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Keep the data when another copy of Labelwise (the Pro package) is still installed.
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
foreach ( array_keys( get_plugins() ) as $labelwise_installed ) {
	if ( WP_UNINSTALL_PLUGIN !== $labelwise_installed && 'labelwise.php' === basename( $labelwise_installed ) ) {
		return;
	}
}

delete_option( 'labelwise_settings' );

delete_post_meta_by_key( '_labelwise_status' );
delete_post_meta_by_key( '_labelwise_reviewer' );
delete_post_meta_by_key( '_labelwise_reviewed_at' );
