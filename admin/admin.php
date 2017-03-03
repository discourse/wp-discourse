<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );

/**
 * Enqueue admin styles and scripts.
 */
function enqueue_admin_scripts() {
	wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/admin/css/admin-styles.css' );
	wp_enqueue_style( 'wp_discourse_admin' );
}
