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
delete_option( 'discourse_connection' );
delete_option( 'discourse_publish' );
delete_option( 'discourse_comment' );
delete_option( 'discourse_sso' );
delete_option( 'discourse_configurable_text' );
delete_option( 'discourse_configurable_text_backup' );
// Delete the old `discourse` options array if it is present.
delete_option( 'discourse' );

delete_transient( 'discourse_settings_categories_cache' );
