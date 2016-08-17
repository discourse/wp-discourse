<?php
/**
 * Uninstall the plugin.
 *
 * @package WPDiscourse
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'discourse_version' );
delete_option( 'discourse' );
delete_transient( 'discourse_settings_categories_cache' );
