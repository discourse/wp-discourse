<?php

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class NetworkOptions
 *
 * Saves site_options with matching keys to the blog_options to the 'wpdc_site_options' array.
 */
class NetworkOptions {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup' ) );
		add_action( 'network_admin_menu', array( $this, 'add_network_settings_page' ) );
		add_action( 'network_admin_edit_discourse_network_options', array( $this, 'save_network_settings' ) );

		add_action( 'network_admin_notices', array( $this, 'network_config_notices' ) );
	}


	public function setup() {

		add_settings_section( 'discourse_network_settings_section', __( 'Enable Settings for the Entire Network', 'wp-discourse' ), array(
			$this,
			'network_settings_details',
		), 'discourse_network_options' );

		add_settings_field( 'discourse_network_multisite_configuration', __( 'Multisite Configuration', 'wp-discourse' ), array(
			$this,
			'multisite_configuration_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_url', __( 'Discourse URL', 'wp-discourse' ), array(
			$this,
			'url_input',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_api_key', __( 'API Key', 'wp-discourse' ), array(
			$this,
			'api_key_input',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_publish_username', __( 'Publishing Username', 'wp-discourse' ), array(
			$this,
			'publish_username_input',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_use_discourse_webhook', __( 'Sync Comment Data', 'wp-discourse' ), array(
			$this,
			'use_discourse_webhook_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_webhook_match_old_topics', __( 'Match Old Topics', 'wp-discourse' ), array(
			$this,
			'webhook_match_old_topics_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_use_discourse_user_webhook', __( 'Update Userdata', 'wp-discourse' ), array(
			$this,
			'use_discourse_user_webhook_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_webhook_match_user_email', __( 'Match Users by Email Address', 'wp-discourse' ), array(
			$this,
			'webhook_match_user_email_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_webhook_secret', __( 'Webhook Secret Key', 'wp-discourse' ), array(
			$this,
			'webhook_secret_input',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_hide_name_field', __( 'Do Not Display Discourse Name Field', 'wp-discourse' ), array(
			$this,
			'hide_discourse_name_field_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_enable_sso', __( 'Enable SSO Provider', 'wp-discourse' ), array(
			$this,
			'enable_sso_provider_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_enable_discourse_sso', __( 'Enable SSO Client', 'wp-discourse' ), array(
			$this,
			'enable_sso_client_checkbox',
		), 'discourse_network_options', 'discourse_network_settings_section' );

		add_settings_field( 'discourse_network_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array(
			$this,
			'sso_secret_input',
		), 'discourse_network_options', 'discourse_network_settings_section' );
	}

	public function add_network_settings_page( $context ) {
		add_menu_page(
			__( 'Discourse', 'wp-discourse' ),
			__( 'Discourse', 'wp-discourse' ),
			'manage_network_options',
			'discourse_network_options',
			array( $this, 'network_options_page' ),
			'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTExIDc5LjE1ODMyNSwgMjAxNS8wOS8xMC0wMToxMDoyMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUYxNjlGNkY3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUYxNjlGNzA3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRjE2OUY2RDc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRjE2OUY2RTc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pq7th6IAAAP8SURBVHjalFbbT5tlHH56grKWnicw5ijdAjNGg5CxbPGIM7qx7cIlxihxd7oLr/wHlpgsu/FWjbdeeOWFwUQixmW6GGbKpDKcgqhj7AA90CM9t/j8Xr+vKZW08Gue0O/jfZ/n/R3fGg73+dHE2ogzxEvEU4SPMBJxYon4kZjUnnc0QxOBD4j3iX40txjxKfEhUdqNwJPE58SwWmAwoFQqoVQsolqtwmgyoa2tDWazGVtbW/X7FokJYrb+pbGB/DliTsiNRiPyuRw2YjEY+HF7fejqOQCHw4FcNotoOKyEZZ1mg0SQeKWe0Fz3PUBck3cGCepGDE6XG8dOnIQ/cBgutwcmnrpcLiEaieB2KIRfgj+jnd54fT5UKhWdZ1oTW2oUmFTkDEl8Y4OkAbx69jwOPn5IhaO76zH0dHfB7Xaj3WpFMpXGt1Pf4KOrV7Fy9x/4+wMUL+tcX2sitRxckkQJeTIRRy9J35h4B06nEz6vFyPDQ/D3HYKJ8W+0UGgeFyfexh93fqeIv94TKZCPdYH7RK/EVBJ54c23MHD0CXXiU2MvorPT3rSM7q7cw8ljo8xZFr79+xUH7SFxUDL0gpDLm81MBkcGj6qY2237cOrl1uRi4t3lK1dQYojy+Zz++gAxJgJj8iQlJyHo6e1VZTgy/Aw67a3JdXvt9GmcePZ5hjihSlszJTAg38QtIXY4nHC5nAgE+rEX28fED42MwGazq/LVS1cEOvQn8UKEfCy7Dmv7ngTyhbyKv4coFAr6a58IqNqShimyW1OpJOx2G/ZqaZatgRxWelKt1ippq9aGErdKpYzVlRWeprBngYeP1lApVxSMhhptZNuosDGpfy0t4vr31+rruaWtcWys3n9AL5LIpFOwWCz6v37bJmC1dnBBGqG5ORRK5V2RS1hv3gyqA/29/CcS8Q20tdfyN10/Kv5rdYZq9Pgoq6J1ktPpDG78NIP1cASRSBi/3rrFsWJRHKyYZS6Z2SZQZOzFi7Pj402Js9kcVu6tYmHhDuLJJDY3M5ia/Arh9Udwe7z6GL/cOOwQjUVxZvwchoaeRjyRxPztBczOztKzCr06rhovzW6PcYTH2VClYkkNuh++m8Yyc+fkINTIZ4gv/icgwy3HeXLp3fcQDM5ifW1dzReLxYwjA4Po4wiRNSazCSkKPFhdxfLiIkOVgsvjUZVIgRSpXq+/0b7k3wvyINlPcGMkGoHD3qlq2sLulubLMgxym0kIhahYLCgPbDabGt/ayRPabJvf6cJRLS4bBI2mSCgk1SKTRkaCsdOoiDVy+QFwUYZr443Wvat6JImcXC6f+tGinfYT4rOdtsnqKSkMfWS0MIOGtEZ8g7jebMO/AgwANr2XXAf8LaoAAAAASUVORK5CYII=',
			5
		);
	}


	/**
	 * Outputs markup for multisite-configuration-enabled-checkbox.
	 */
	public function multisite_configuration_checkbox() {
		$this->checkbox_input( 'multisite-configuration-enabled', __( 'Configure the plugin for a WordPress multisite setup.', 'wp-discourse' ),
			__( "Enabling this setting will create a wpdc_topic_blog database table so that Discourse topics can be associated
            with the blog they were posted from. When enabled, the remaining settings on this page will be
            used to configure their related settings on your network's subsites.", 'wp-discourse' ) );
		$this->next_setting_heading( __( 'Connection Settings', 'wp-discourse' ) );
	}

	/**
	 * *******************
	 *
	 * Connection Settings.
	 **********************/

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	public function url_input() {
		$this->input( 'url', __( 'The base URL of your forum, for example http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	public function api_key_input() {
		$url = $this->get_site_option( 'url' );
		if ( $url ) {
			$this->input( 'api-key', __( 'Found on your forum at ', 'wp-discourse' ) . '<a href="' . esc_url( $url ) .
			                         '/admin/api/keys" target="_blank">' . esc_url( $url ) . '/admin/api/keys</a>. ' .
			"If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse' );
		} else {
			$this->input( 'api-key', __( "Found on your forum at /admin/api/keys.
			If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->input( 'publish-username', __( 'The default Discourse username under which WordPress posts will be published on your forum.
		The Publishing Username is also used for making API calls to Discourse. It must be set to a Discourse admin username.', 'wp-discourse' ), null, null, null, 'system' );
		$this->next_setting_heading( __( 'Webhook Settings', 'wp-discourse' ) );
	}

	/**
	 * ****************
	 *
	 * Webhook Settings.
	 *******************/

	/**
	 * Outputs markup for use-discourse-webhook checkbox.
	 */
	public function use_discourse_webhook_checkbox() {
		$webhook_payload_url = network_site_url( '/wp-json/wp-discourse/v1/update-topic-content' );
		$discourse_url       = $this->get_site_option( 'url' );
		if ( ! empty( $discourse_url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $discourse_url ) . '/admin/api/web_hooks" target="_blank">' .
			                          esc_url( $discourse_url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__( 'Before enabling this setting, create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>. Make sure that the \'Post Event\' and the \'Active\' checkboxes are enabled.', 'wp-discourse' ), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->checkbox_input( 'use-discourse-webhook', __( 'Use a webhook
		to sync comment data between Discourse and WordPress.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for webhook-match-old-topics input.
	 */
	public function webhook_match_old_topics_checkbox() {
		$this->checkbox_input( 'webhook-match-old-topics', __( 'Match WordPress posts
	    published prior to WP Discourse version 1.4.0.', 'wp-discourse' ), __( "By default, posts
	    are matched to Discourse topics through their discourse_topic_id metadata. That value isn't available for posts
	    published through WP Discourse prior to version 1.4.0. Enabling this setting will match posts with the post_type
	    'post' to Discourse topics through their titles.", 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for use-discourse-user-webhook checkbox.
	 */
	public function use_discourse_user_webhook_checkbox() {
		$webhook_payload_url = network_site_url( '/wp-json/wp-discourse/v1/update-user' );
		$url                 = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $url ) . '/admin/api/web_hooks" target="_blank">' .
			                          esc_url( $url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__( 'This setting is primarily designed for when WordPress is enabled as the SSO Provider for Discourse. It
 supplies the Discourse username to WordPress when a user is created or updated on your forum. Before enabling this setting,
 create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>. Make sure that the \'User Event\' checkbox is enabled.', 'wp-discourse' ), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->checkbox_input( 'use-discourse-user-webhook', __( 'Use a webhook to sync user data with Discourse.', 'wp-discourse' ), $description );

	}

	public function webhook_match_user_email_checkbox() {
		$this->checkbox_input( 'webhook-match-user-email', __( 'Match users with Discourse
        through their email address.', 'wp-discourse' ), __( "If you are not using WordPress as the SSO Provider for Discourse
 it is possible to use the Update Userdata webhook by syncing users by their email address.<strong>Note: this should only be enabled if
 you are using an external service to authenticate users on both your WordPress site and Discourse forum and can guarantee the user's email
 addresses match between the two systems.</strong>", 'wp-discourse' ) );

	}

	/**
	 * Outputs markup for webhook-secret input.
	 */
	public function webhook_secret_input() {
		$url = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $url ) . '/admin/api/web_hooks" target="_blank">' .
			                          esc_url( $url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Webhook secret input. Placeholder: discourse_webhooks_url.
			__( 'The secret key used to verify Discourse webhook requests. Set it to a string of text, at least 12
		        characters long. It needs to match the key set at %1$s.', 'wp-discourse' ), $discourse_webhooks_url
		);

		$this->input( 'webhook-secret', $description );
		$this->next_setting_heading( __( 'Publishing Settings', 'wp-discourse' ) );
	}

	/*******************
	 *
     * Publish Settings.
     *
	 *******************/

	/**
	 * Outputs markup for hide-discourse-name-field checkbox.
	 */
	public function hide_discourse_name_field_checkbox() {
		$this->checkbox_input( 'hide-discourse-name-field', __( 'Removes the Discourse Name field
	    from the WordPress user profile page.', 'wp-discourse' ), __( "If you enable this setting and also enable the Update
	    Userdata webhook, new users created on Discourse will have the their Discourse Name automatically filled in and be
	    uneditable on WordPress.", 'wp-discourse' ) );
	    $this->next_setting_heading( __( 'SSO Settings', 'wp-discourse' ) );
	}

	/***************
	 *
	 * SSO Settings.
     *
	 ***************/


	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_provider_checkbox() {
		$sso_documentation_url  = get_admin_url( BLOG_ID_CURRENT_SITE, '/admin.php?page=wp_discourse_options&tab=sso_provider&parent_tab=sso_options' );
		$sso_documentation_link = '<a href="' . esc_url( $sso_documentation_url ) . '" target="_blank">' . __( 'SSO Provider tab', 'wp-discourse' ) . '</a>';
		$description            = __( 'Use this WordPress instance as the SSO provider for your Discourse forum.', 'wp-discourse' );
		$details                = sprintf(
			__( 'For details about using WordPress as the SSO Provider, please visit the %1s of the main site in your network.', 'wp-discourse' ), $sso_documentation_link
		);
		$this->checkbox_input( 'enable-sso', $description, $details );
	}

	/**
	 * Outputs markup for sso-client-enabled checkbox.
	 */
	public function enable_sso_client_checkbox() {
		$sso_documentation_url  = get_admin_url( BLOG_ID_CURRENT_SITE, '/admin.php?page=wp_discourse_options&tab=sso_client&parent_tab=sso_options' );
		$sso_documentation_link = '<a href="' . esc_url( $sso_documentation_url ) . '" target="_blank">' . __( 'SSO Client tab', 'wp-discourse' ) . '</a>';
		$description            = __( 'Allow your WordPress site to function as an SSO client to Discourse.', 'wp-discourse' );
		$details                = sprintf(
			__( 'For details about using WordPress as an SSO Client for Discourse, please visit the %1s of the main site in your network.', 'wp-discourse' ), $sso_documentation_link
		);
		$this->checkbox_input( 'sso-client-enabled', $description, $details );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		$url = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_sso_url = '<a href="' . esc_url( $url ) . '/admin/site_settings/category/all_results?filter=sso" target="_blank">' .
			                     esc_url( $url ) . '/admin/site_settings/category/all_results?filter=sso</a>';
		} else {
			$discourse_sso_url = 'http://forum.example.com/admin/site_settings/category/all_results?filter=sso';
		}

		$description = sprintf(
			// translators: SSO secret input. Placeholder: discourse_sso_url.
			__( 'The secret key used to verify Discourse SSO requests. Set it to a string of text, at least 10
		        characters long. It needs to match the key set at %1$s.', 'wp-discourse' ), $discourse_sso_url
		);
		$this->input( 'sso-secret', $description );
	}

	public function network_options_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {

			exit;
		}
		$action_url = add_query_arg(
			'action',
			'discourse_network_options',
			network_admin_url( 'edit.php' )
		)
		?>
		<div class="wrap discourse-options-page-wrap">
			<h2>
				<img
						src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTExIDc5LjE1ODMyNSwgMjAxNS8wOS8xMC0wMToxMDoyMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUYxNjlGNkY3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUYxNjlGNzA3NjAxMTFFNjkyRkZBRTlDQTMwREJDQzUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRjE2OUY2RDc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRjE2OUY2RTc2MDExMUU2OTJGRkFFOUNBMzBEQkNDNSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pq7th6IAAAP8SURBVHjalFbbT5tlHH56grKWnicw5ijdAjNGg5CxbPGIM7qx7cIlxihxd7oLr/wHlpgsu/FWjbdeeOWFwUQixmW6GGbKpDKcgqhj7AA90CM9t/j8Xr+vKZW08Gue0O/jfZ/n/R3fGg73+dHE2ogzxEvEU4SPMBJxYon4kZjUnnc0QxOBD4j3iX40txjxKfEhUdqNwJPE58SwWmAwoFQqoVQsolqtwmgyoa2tDWazGVtbW/X7FokJYrb+pbGB/DliTsiNRiPyuRw2YjEY+HF7fejqOQCHw4FcNotoOKyEZZ1mg0SQeKWe0Fz3PUBck3cGCepGDE6XG8dOnIQ/cBgutwcmnrpcLiEaieB2KIRfgj+jnd54fT5UKhWdZ1oTW2oUmFTkDEl8Y4OkAbx69jwOPn5IhaO76zH0dHfB7Xaj3WpFMpXGt1Pf4KOrV7Fy9x/4+wMUL+tcX2sitRxckkQJeTIRRy9J35h4B06nEz6vFyPDQ/D3HYKJ8W+0UGgeFyfexh93fqeIv94TKZCPdYH7RK/EVBJ54c23MHD0CXXiU2MvorPT3rSM7q7cw8ljo8xZFr79+xUH7SFxUDL0gpDLm81MBkcGj6qY2237cOrl1uRi4t3lK1dQYojy+Zz++gAxJgJj8iQlJyHo6e1VZTgy/Aw67a3JdXvt9GmcePZ5hjihSlszJTAg38QtIXY4nHC5nAgE+rEX28fED42MwGazq/LVS1cEOvQn8UKEfCy7Dmv7ngTyhbyKv4coFAr6a58IqNqShimyW1OpJOx2G/ZqaZatgRxWelKt1ippq9aGErdKpYzVlRWeprBngYeP1lApVxSMhhptZNuosDGpfy0t4vr31+rruaWtcWys3n9AL5LIpFOwWCz6v37bJmC1dnBBGqG5ORRK5V2RS1hv3gyqA/29/CcS8Q20tdfyN10/Kv5rdYZq9Pgoq6J1ktPpDG78NIP1cASRSBi/3rrFsWJRHKyYZS6Z2SZQZOzFi7Pj402Js9kcVu6tYmHhDuLJJDY3M5ia/Arh9Udwe7z6GL/cOOwQjUVxZvwchoaeRjyRxPztBczOztKzCr06rhovzW6PcYTH2VClYkkNuh++m8Yyc+fkINTIZ4gv/icgwy3HeXLp3fcQDM5ifW1dzReLxYwjA4Po4wiRNSazCSkKPFhdxfLiIkOVgsvjUZVIgRSpXq+/0b7k3wvyINlPcGMkGoHD3qlq2sLulubLMgxym0kIhahYLCgPbDabGt/ayRPabJvf6cJRLS4bBI2mSCgk1SKTRkaCsdOoiDVy+QFwUYZr443Wvat6JImcXC6f+tGinfYT4rOdtsnqKSkMfWS0MIOGtEZ8g7jebMO/AgwANr2XXAf8LaoAAAAASUVORK5CYII="
						alt="Discourse logo" class="discourse-logo">
				<?php esc_html_e( 'WP Discourse Network Settings', 'wp-discourse' ); ?>
			</h2>

			<!--			--><?php // settings_errors(); ?>
			<form class="wp-discourse-network-options-form" action="<?php echo esc_url( $action_url ); ?>"
				  method="post">
				<?php wp_nonce_field( 'update_discourse_network_options', 'update_discourse_network_options_nonce' ); ?>
				<?php
				settings_fields( 'discourse_network_options' );
				do_settings_sections( 'discourse_network_options' );
				submit_button( 'Save Options', 'primary', 'discourse_save_options', false );
				?>
			</form>
		</div>
		<?php
	}

	public function save_network_settings() {
		write_log( $_POST );
		if ( ! isset( $_POST['update_discourse_network_options_nonce'] ) || // Input var okay.
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['update_discourse_network_options_nonce'] ) ), 'update_discourse_network_options' )
		) { // Input var okay.

			return null;
		}

		if ( ! current_user_can( 'manage_network_options' ) ) {

			return null;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return null;
		}

		if ( isset( $_POST['wpdc_site_options'] ) ) { // Input var okay.
			$site_options = wp_unslash( $_POST['wpdc_site_options'] ); // Input var okay.
			$this->validate_site_options( $site_options );
		}

		wp_redirect( add_query_arg( array(
			'page'    => 'discourse_network_options',
			'updated' => 'true',
		), network_admin_url( 'admin.php' ) ) );

		exit();
	}

	public function network_settings_details() {
		?>
		<p>
			<em>
				<?php esc_html_e( "The Multisite Configuration option is for the case where one Discourse forum is connected
                to a network of WordPress sites. If it's enabled, the fields on this page will not be displayed on
                the WP Discourse options pages for your network's subsites. This allows you to use the plugin on a network without
                exposing your forum's API credentials to the subsite's administrators.", 'wp-discourse' ); ?>
			</em>
		</p>
		<h2><?php esc_html_e( 'Webhooks', 'wp-discourse' ); ?></h2>
		<p>
			<em>
				<?php esc_html_e( "Webhooks can be used to sync data between Discourse and WordPress. Their use is optional,
				but they're easy to setup. In a multisite environment the Sync Comment Data webhook will improve the efficiency of syncing Discourse
				topic data with WordPress. The Update User Data webhook is used to sync user data between WordPress and Discourse when
				a Discourse user is created or updated. When Multisite Configuration is enabled, all webhook options are set for your network's sites here.", 'wp-discourse' ); ?>
			</em>
		</p>
		<h2><?php esc_html_e( 'SSO', 'wp-discourse' ); ?></h2>
		<p>
			<em>
				<?php esc_html_e( 'When Multisite Configuration is enabled, SSO functionality to either use WordPress as the
                SSO provider for Discourse, or WordPress as an SSO client to Discourse is enabled on this page. The SSO Secret
                Key is also set here. In a multisite setup, the SSO Client functionality is only available when Multisite Configuration
                is enabled.', 'wp-discourse' ); ?>
			</em>
		</p>
		<div class="discourse-doc-section-end">
			<hr class="discourse-options-section-hr">
		</div>
		<?php
	}

	public function validate_site_options( $site_options ) {
		$updated_options = array();
		foreach ( $site_options as $key => $value ) {
			$filter = 'wpdc_validate_site_' . str_replace( '-', '_', $key );
			if ( ! has_filter( $filter ) ) {
				// It's safe to log errors here. This should never have to be called on a production site.
				error_log( 'Missing validation filter: ' . $filter );
			}
			$value                   = apply_filters( $filter, $value );
			$updated_options[ $key ] = $value;

			if ( 'multisite-configuration-enabled' === $key ) {
				update_site_option( 'wpdc_multisite_configuration', $value );
			}
		}

		update_site_option( 'wpdc_site_options', $updated_options );
	}

	public function network_config_notices() {
		$screen           = get_current_screen();
		$discourse_screen = ! empty( $screen->parent_base ) && 'discourse_network_options' === $screen->parent_base;
		if ( $discourse_screen && ! empty( $this->get_site_option( 'multisite-configuration-enabled' ) ) ) {
			$notices                    = '';
			$url                        = $this->get_site_option( 'url' );
			$api_key                    = $this->get_site_option( 'api-key' );
			$publish_username           = $this->get_site_option( 'publish-username' );
			$use_discourse_webhook      = $this->get_site_option( 'use-discourse-webhook' );
			$use_discourse_user_webhook = $this->get_site_option( 'use-discourse-user-webhook' );
			$webhook_secret             = $this->get_site_option( 'webhook-secret' );
			$sso_secret                 = $this->get_site_option( 'sso-secret' );
			$enable_sso                 = $this->get_site_option( 'enable-sso' );
			$sso_client_enabled         = $this->get_site_option( 'sso-client-enabled' );

			if ( ! ( $url && $api_key && $publish_username ) ) {
				$notices .= '<div class="notice notice-warning is-dismissible"><p>' .
				            __( 'To connect with Discourse, you need to supply the Discourse URL, API Key, and Publishing Username.', 'wp-discourse' ) .
				            '</p></div>';
			} elseif ( ! DiscourseUtilities::check_connection_status() ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'You are not connected to Discourse. Check that your connection settings are correct.', 'wp-discourse' ) .
				            '</p></div>';
			} else {
				$notices .= '<div class="notice notice-success is-dismissible"><p>' .
				            __( 'You are connected to Discourse!', 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ( ! empty( $use_discourse_webhook ) && empty( $webhook_secret ) ) ||
			     ( ! empty( $use_discourse_user_webhook ) && empty( $webhook_secret ) )
			) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'To use Discourse webhooks, you need to supply a webhook secret key.', 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ! empty( $webhook_secret ) && strlen( $webhook_secret ) < 12 ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'The Webhook Secret Key must be at least 12 characters long.', 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ! empty( $enable_sso ) && empty( $sso_secret )
			) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'To use WordPress as the SSO Provider, you need to supply an SSO Secret Key.', 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ! empty( $sso_client_enabled ) && empty( $sso_secret )
			) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'To use WordPress as the SSO Client, you need to supply an SSO Secret Key.', 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ! empty( $enable_sso ) && ! empty( $sso_client_enabled ) ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( "You can't enable both the SSO Client and SSO Provider functionality.", 'wp-discourse' ) .
				            '</p></div>';
			}

			if ( ! empty( $sso_secret ) && strlen( $sso_secret ) < 10 ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
				            __( 'The SSO Secret Key must be at least 10 characters long.', 'wp-discourse' ) .
				            '</p></div>';
			}
		}// End if().

		if ( ! empty( $notices ) ) {
			echo $notices;
		}
	}

	protected function get_site_option( $key ) {
		static $site_options = array();

		if ( empty( $site_options ) ) {
			$site_options = get_site_option( 'wpdc_site_options' );
		}

		$option = ! empty( $site_options[ $key ] ) ? $site_options[ $key ] : false;

		return $option;
	}

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string      $option The name of the option.
	 * @param string      $option_group The option group for the field to be saved to.
	 * @param string      $description The description of the settings field.
	 * @param null|string $type The type of input ('number', 'url', etc).
	 * @param null|int    $min The min value (applied to number inputs).
	 * @param null|int    $max The max value (applies to number inputs).
	 */
	protected function input( $option, $description, $type = null, $min = null, $max = null, $default = null ) {
		$value   = $this->get_site_option( $option );
		$value   = empty( $value ) && $default ? $default : $value;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		?>
		<input id='discourse-<?php echo esc_attr( $option ); ?>'
			   name='<?php echo 'wpdc_site_options[' . esc_attr( $option ) . ']'; ?>'
			   type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
			<?php if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
} ?>
			<?php if ( isset( $max ) ) {
				echo 'max="' . esc_attr( $max ) . '"';
} ?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the markup for a checkbox input.
	 *
	 * @param string $option The option name.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $label The text for the label.
	 * @param string $description The description of the settings field.
	 */
	protected function checkbox_input( $option, $label = '', $description = '' ) {
		$value   = $this->get_site_option( $option );
		$allowed = array(
			'a'      => array(
				'href'   => array(),
				'target' => array(),
			),
			'strong' => array(),
			'code'   => array(),
		);

		$checked = ! empty( $value ) ? 'checked="checked"' : '';

		?>
		<label>
			<input name='<?php echo 'wpdc_site_options[' . esc_attr( $option ) . ']'; ?>'
				   type='hidden'
				   value='0'/>
			<input id='discourse-<?php echo esc_attr( $option ); ?>'
				   name='<?php echo 'wpdc_site_options[' . esc_attr( $option ) . ']'; ?>'
				   type='checkbox'
				   value='1' <?php echo esc_attr( $checked ); ?> />
			<?php echo wp_kses( $label, $allowed ); ?>
		</label>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	protected function next_setting_heading( $title = null ) {
		?>
		<div class="discourse-options-section-end">
			<hr class="discourse-options-section-hr">
			<?php if ( $title ) : ?>
				<h2><?php esc_html_e( $title ); ?></h2>
			<?php endif; ?>
		</div>
		<?php
	}
}
