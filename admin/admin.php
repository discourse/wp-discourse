<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

if ( is_admin() ) {
	require_once( __DIR__ . '/form-helper.php' );
	require_once( __DIR__ . '/connection-settings.php' );
	require_once( __DIR__ . '/publish-settings.php' );
	require_once( __DIR__ . '/comment-settings.php' );
	require_once( __DIR__ . '/configurable-text-settings.php' );
	require_once( __DIR__ . '/sso-settings.php' );
	require_once( __DIR__ . '/admin-menu.php' );
	require_once( __DIR__ . '/options-page.php' );
	require_once( __DIR__ . '/settings-validator.php' );

	$form_helper = \WPDiscourse\Admin\FormHelper::get_instance();
	$options_page = \WPDiscourse\Admin\OptionsPage::get_instance();
	new \WPDiscourse\Admin\AdminMenu( $options_page );
	new \WPDiscourse\Admin\ConnectionSettings( $form_helper );
	new \WPDiscourse\Admin\PublishSettings( $form_helper );
	new \WPDiscourse\Admin\CommentSettings( $form_helper );
	new \WPDiscourse\Admin\ConfigurableTextSettings( $form_helper );
	new \WPDiscourse\Admin\SSOSettings( $form_helper );
	new \WPDiscourse\Admin\SettingsValidator();

	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );
}

function enqueue_admin_scripts() {
	wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/admin/css/admin-styles.css' );
	wp_enqueue_style( 'wp_discourse_admin' );
}
