<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

if ( is_admin() ) {
	require_once __DIR__ . '/admin-menu.php';
	require_once __DIR__ . '/comment-settings.php';
	require_once __DIR__ . '/configurable-text-settings.php';
	require_once __DIR__ . '/connection-settings.php';
	require_once __DIR__ . '/form-helper.php';
	require_once __DIR__ . '/options-page.php';
	require_once __DIR__ . '/publish-settings.php';
	require_once __DIR__ . '/settings-validator.php';
	require_once __DIR__ . '/webhook-settings.php';

	$form_helper  = FormHelper::get_instance();
	$options_page = OptionsPage::get_instance();
	new AdminMenu( $options_page, $form_helper );
	new ConnectionSettings( $form_helper );
	new PublishSettings( $form_helper );
	new CommentSettings( $form_helper );
	new ConfigurableTextSettings( $form_helper );
	new WebhookSettings( $form_helper );
	new SettingsValidator();

	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	if ( is_multisite() ) {
		add_action( 'admin_print_scripts', __NAMESPACE__ . '\\enqueue_network_styles' );
	}
} // End if().

/**
 * Enqueue admin styles and scripts.
 */
function enqueue_admin_scripts() {
	wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/admin/css/admin-styles.css' );
	wp_enqueue_style( 'wp_discourse_admin' );

	wp_register_script( 'admin_js', plugins_url( '../admin/js/admin.js', __FILE__ ), array( 'jquery' ), WPDISCOURSE_VERSION, true );
	wp_enqueue_script( 'admin_js' );
	$commenting_options = get_option( 'discourse-comment' );
	$max_tags           = ! isset( $commenting_options['max-tags'] ) ? 5 : $commenting_options['max-tags'];
	$data               = array(
		'maxTags' => $max_tags,
	);
	wp_localize_script( 'admin_js', 'wpdc', $data );
}
