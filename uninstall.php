<?php
/**
 * Uninstall the plugin.
 *
 * @package WPDiscourse
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$discourse_options = get_option( 'discourse_option_groups' );
$discourse_options[] = 'discourse_configurable_text_backup';
$discourse_options[] = 'discourse_version';
$discourse_options[] = 'discourse_option_groups';

foreach ( $discourse_options as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}discourse_nonce");

delete_transient( 'discourse_settings_categories_cache' );
