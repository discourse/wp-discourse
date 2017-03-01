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

	$form_helper  = FormHelper::get_instance();
	$options_page = OptionsPage::get_instance();
	new AdminMenu( $options_page, $form_helper );
	new ConnectionSettings( $form_helper );
	new PublishSettings( $form_helper );
	new CommentSettings( $form_helper );
	new ConfigurableTextSettings( $form_helper );
	new SSOSettings( $form_helper );
	new SettingsValidator();

	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );
}

/**
 * Enqueue admin styles and scripts.
 */
function enqueue_admin_scripts() {
	wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/admin/css/admin-styles.css' );
	wp_enqueue_style( 'wp_discourse_admin' );
}
