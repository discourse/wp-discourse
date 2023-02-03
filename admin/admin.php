<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 * @todo Review phpcs exclusions
 */

namespace WPDiscourse\Admin;

if ( is_admin() ) {
	require_once __DIR__ . '/admin-menu.php';
	require_once __DIR__ . '/comment-settings.php';
	require_once __DIR__ . '/configurable-text-settings.php';
	require_once __DIR__ . '/connection-settings.php';
	require_once __DIR__ . '/form-helper.php';
	require_once __DIR__ . '/network-options.php';
	require_once __DIR__ . '/options-page.php';
	require_once __DIR__ . '/publish-settings.php';
	require_once __DIR__ . '/settings-validator.php';
	require_once __DIR__ . '/sso-settings.php';
	require_once __DIR__ . '/webhook-settings.php';
	require_once __DIR__ . '/admin-notice.php';
	require_once __DIR__ . '/meta-box.php';
	require_once __DIR__ . '/user-profile.php';
	require_once __DIR__ . '/log-viewer.php';

	$form_helper  = FormHelper::get_instance();
	$options_page = OptionsPage::get_instance();
	new AdminMenu( $options_page, $form_helper );
	if ( is_multisite() ) {
		new NetworkOptions();
	}
	new ConnectionSettings( $form_helper );
	new PublishSettings( $form_helper );
	new CommentSettings( $form_helper );
	new ConfigurableTextSettings( $form_helper );
	new SSOSettings( $form_helper );
	new WebhookSettings( $form_helper );
	new SettingsValidator();
	new AdminNotice();
	new MetaBox();
	new UserProfile();
	new LogViewer( $form_helper );

	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts' );
	if ( is_multisite() ) {
		add_action( 'admin_print_scripts', __NAMESPACE__ . '\\enqueue_network_styles' );
	}
} // End if().


/**
 * Enqueue admin styles and scripts.
 */
function enqueue_admin_scripts() {
	$style_path = '/css/admin-styles.css';
	wp_register_style( 'admin_styles', plugins_url( $style_path, __FILE__ ), array(), filemtime( plugin_dir_path( __FILE__ ) . $style_path ) );
	wp_enqueue_style( 'admin_styles' );

	$script_path = '/js/admin.js';
	wp_register_script( 'admin_js', plugins_url( $script_path, __FILE__ ), array( 'jquery', 'tags-box' ), filemtime( plugin_dir_path( __FILE__ ) . $script_path ), true );
	wp_enqueue_script( 'admin_js' );
	$commenting_options = get_option( 'discourse-comment' );
	$max_tags           = ! isset( $commenting_options['max-tags'] ) ? 5 : $commenting_options['max-tags'];
	$data               = array(
		'maxTags' => $max_tags,
		'ajax'    => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'admin-ajax-nonce' ),
	);
	wp_localize_script( 'admin_js', 'wpdc', $data );
}

/**
 * Enqueue styles for network page.
 */
function enqueue_network_styles() {
	global $current_screen;
	if ( ! $current_screen || ! $current_screen->in_admin( 'network' ) ) {

		return;
	}

	wp_register_style( 'wp_discourse_network_admin', WPDISCOURSE_URL . '/admin/css/network-admin-styles.css' ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'wp_discourse_network_admin' );
}
