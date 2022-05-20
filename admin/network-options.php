<?php
/**
 * Network Options.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class NetworkOptions
 *
 * Saves site_options with matching keys to the blog_options to the 'wpdc_site_options' array.
 */
class NetworkOptions {
	use PluginUtilities;

	/**
	 * NetworkOptions constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup' ) );
		add_action( 'network_admin_menu', array( $this, 'add_network_settings_page' ) );
		add_action( 'network_admin_edit_discourse_network_options', array( $this, 'save_network_settings' ) );

		add_action( 'network_admin_notices', array( $this, 'network_config_notices' ) );
	}

	/**
	 * Adds settings section and settings fields.
	 */
	public function setup() {

		add_settings_section(
			'discourse_network_settings_section',
			__( 'Multisite Configuration', 'wp-discourse' ),
			array(
				$this,
				'network_settings_details',
			),
			'discourse_network_options'
		);

		add_settings_field(
			'discourse_network_multisite_configuration',
			__( 'Enable Multisite Configuration', 'wp-discourse' ),
			array(
				$this,
				'multisite_configuration_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_url',
			__( 'Discourse URL', 'wp-discourse' ),
			array(
				$this,
				'url_input',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_api_key',
			__( 'API Key', 'wp-discourse' ),
			array(
				$this,
				'api_key_input',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_publish_username',
			__( 'Publishing Username', 'wp-discourse' ),
			array(
				$this,
				'publish_username_input',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_connection_logs',
			__( 'Connection Logs', 'wp-discourse' ),
			array(
				$this,
				'connection_logs',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_use_discourse_webhook',
			__( 'Sync Comment Data', 'wp-discourse' ),
			array(
				$this,
				'use_discourse_webhook_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_webhook_match_old_topics',
			__( 'Match Old Topics', 'wp-discourse' ),
			array(
				$this,
				'webhook_match_old_topics_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_use_discourse_user_webhook',
			__( 'Update Userdata', 'wp-discourse' ),
			array(
				$this,
				'use_discourse_user_webhook_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_webhook_match_user_email',
			__( 'Match Users by Email Address', 'wp-discourse' ),
			array(
				$this,
				'webhook_match_user_email_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_webhook_secret',
			__( 'Webhook Secret Key', 'wp-discourse' ),
			array(
				$this,
				'webhook_secret_input',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_verbose_webhook_logs',
			__( 'Verbose Webhook Logs', 'wp-discourse' ),
			array(
				$this,
				'verbose_webhook_logs',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_hide_name_field',
			__( 'Do Not Display Discourse Name Field', 'wp-discourse' ),
			array(
				$this,
				'hide_discourse_name_field_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_verbose_publication_logs',
			__( 'Verbose Publication Logs', 'wp-discourse' ),
			array(
				$this,
				'verbose_publication_logs',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_verbose_comment_logs',
			__( 'Verbose Comment Logs', 'wp-discourse' ),
			array(
				$this,
				'verbose_comment_logs',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_enable_sso',
			__( 'Enable DiscourseConnect Provider', 'wp-discourse' ),
			array(
				$this,
				'enable_sso_provider_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_enable_discourse_sso',
			__( 'Enable DiscourseConnect Client', 'wp-discourse' ),
			array(
				$this,
				'enable_sso_client_checkbox',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_sso_secret',
			__( 'DiscourseConnect Secret Key', 'wp-discourse' ),
			array(
				$this,
				'sso_secret_input',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);

		add_settings_field(
			'discourse_network_verbose_sso_logs',
			__( 'Verbose DiscourseConnect Logs', 'wp-discourse' ),
			array(
				$this,
				'verbose_sso_logs',
			),
			'discourse_network_options',
			'discourse_network_settings_section'
		);
	}

	/**
	 * Adds the network menu page.
	 */
	public function add_network_settings_page() {
		add_menu_page(
			__( 'Discourse', 'wp-discourse' ),
			__( 'Discourse', 'wp-discourse' ),
			'manage_network_options',
			'discourse_network_options',
			array( $this, 'network_options_page' ),
			WPDISCOURSE_LOGO,
			5
		);
	}

	/**
	 * ******************************
	 *
	 * Multisite Configuration Fields.
	 *********************************/

	/**
	 * Outputs markup for multisite-configuration-enabled-checkbox.
	 */
	public function multisite_configuration_checkbox() {
		$this->checkbox_input(
			'multisite-configuration-enabled',
			__( 'Configure the plugin for a WordPress multisite setup.', 'wp-discourse' ),
			__(
				"Enabling this setting will create a wpdc_topic_blog database table so that Discourse topics can be associated
            with the blog they were posted from. This makes it possible to sync topic data between Discourse and WordPress with a single webhook.
            When Multisite Configuration is enabled, the settings on this page will be used to configure their related settings on your network's subsites.",
				'wp-discourse'
			)
		);
		$this->next_setting_heading( __( 'Connection Settings', 'wp-discourse' ) );
	}

	/**
	 * ***************************
	 *
	 * Connection Settings Fields.
	 *****************************/

	/**
	 * Outputs markup for the url input.
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
			$this->input(
				'api-key',
				__( 'Found on your forum at ', 'wp-discourse' ) . '<a href="' . esc_url( $url ) .
									 '/admin/api/keys" target="_blank" rel="noreferrer noopener">' . esc_url( $url ) . '/admin/api/keys</a>. ' .
				"If you haven't yet created an API key, Click 'New API Key', set User Level to 'Single User', set 'User' to an admin account, select 'Global Key' and click 'Save'. Copy and paste the API key here.",
				'wp-discourse'
			);
		} else {
			$this->input(
				'api-key',
				__(
					"Found on your forum at /admin/api/keys.
			If you haven't yet created an API key, Click 'New API Key', set User Level to 'Single User', set 'User' to an admin account, select 'Global Key' and click 'Save'. Copy and paste the API key here.",
					'wp-discourse'
				)
			);
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->input(
			'publish-username',
			__(
				'The default Discourse username under which WordPress posts will be published on your forum.
		The Publishing Username is also used for making API calls to Discourse. It must be set to a Discourse admin username.',
				'wp-discourse'
			),
			null,
			null,
			null,
			'system'
		);
	}

	/**
	 * Outputs markup for the discourse_connection_logs checkbox.
	 */
	public function connection_logs() {
		$this->checkbox_input(
			'connection-logs',
			__(
				'Enable connection logs.',
				'wp-discourse'
			),
			__( 'Log attempts to check the connection with Discourse.', 'wp-discourse' )
		);
		$this->next_setting_heading( __( 'Webhook Settings', 'wp-discourse' ) );
	}

	/**
	 * ************************
	 *
	 * Webhook Settings Fields.
	 **************************/

	/**
	 * Outputs markup for use-discourse-webhook checkbox.
	 */
	public function use_discourse_webhook_checkbox() {
		$webhook_payload_url = network_site_url( '/wp-json/wp-discourse/v1/update-topic-content' );
		$discourse_url       = $this->get_site_option( 'url' );
		if ( ! empty( $discourse_url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $discourse_url ) . '/admin/api/web_hooks" target="_blank" rel="noreferrer noopener">' .
									  esc_url( $discourse_url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
				'Before enabling this setting, create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>. Make sure that the \'Post Event\' and the \'Active\' checkboxes are enabled.',
				'wp-discourse'
			),
			$discourse_webhooks_url,
			$webhook_payload_url
		);

		$this->checkbox_input(
			'use-discourse-webhook',
			__(
				'Use a webhook
		to sync comment data between Discourse and WordPress.',
				'wp-discourse'
			),
			$description
		);
	}

	/**
	 * Outputs markup for webhook-match-old-topics input.
	 */
	public function webhook_match_old_topics_checkbox() {
		$this->checkbox_input(
			'webhook-match-old-topics',
			__(
				'Match WordPress posts
	    published prior to WP Discourse version 1.4.0.',
				'wp-discourse'
			),
			__(
				"By default, posts
	    are matched to Discourse topics through their discourse_topic_id metadata. That value isn't available for posts
	    published through WP Discourse prior to version 1.4.0. Enabling this setting will match posts with the post_type
	    'post' to Discourse topics through their titles.",
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for use-discourse-user-webhook checkbox.
	 */
	public function use_discourse_user_webhook_checkbox() {
		$webhook_payload_url = network_site_url( '/wp-json/wp-discourse/v1/update-user' );
		$url                 = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $url ) . '/admin/api/web_hooks" target="_blank" rel="noreferrer noopener">' .
									  esc_url( $url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
				'This webhook is only active when WordPress is enabled as the DiscourseConnect Provider for Discourse (this can be overridden by
hooking into the \'wpdc_use_discourse_user_webhook\' filter.) It supplies the Discourse username to WordPress when a user is created or updated on your forum.
Before enabling this setting, create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
URL <code>%2$s</code>. Make sure that only the \'User Event\' checkbox is enabled.',
				'wp-discourse'
			),
			$discourse_webhooks_url,
			$webhook_payload_url
		);

		$this->checkbox_input( 'use-discourse-user-webhook', __( 'Use a webhook to sync user data with Discourse.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the webhook-match-user-email-checkbox.
	 */
	public function webhook_match_user_email_checkbox() {
		$this->checkbox_input(
			'webhook-match-user-email',
			__(
				'Match users with Discourse
        through their email address.',
				'wp-discourse'
			),
			__(
				'Used for syncing accounts that were created before enabling the
        Update Userdata webhook. Existing accounts are synced when the user updates and saves their profile on Discourse.
        <strong>Note: only enable this setting if you are certain that email addresses match between Discourse
        and WordPress.</strong>',
				'wp-discourse'
			)
		);

	}

	/**
	 * Outputs markup for webhook-secret input.
	 */
	public function webhook_secret_input() {
		$url = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $url ) . '/admin/api/web_hooks" target="_blank" rel="noreferrer noopener">' .
									  esc_url( $url ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Webhook secret input. Placeholder: discourse_webhooks_url.
			__(
				'The secret key used to verify Discourse webhook requests. Set it to a string of text, at least 12
		        characters long. It needs to match the key set at %1$s.',
				'wp-discourse'
			),
			$discourse_webhooks_url
		);

		$this->input( 'webhook-secret', $description );
	}

	/**
	 * Outputs markup for the verbose-webhook-logs checkbox.
	 */
	public function verbose_webhook_logs() {
		$this->checkbox_input(
			'verbose-webhook-logs',
			__(
				'Enable verbose logs for webhooks.',
				'wp-discourse'
			),
			__( 'Will log successful syncs as well as errors.', 'wp-discourse' )
		);
		$this->next_setting_heading( __( 'Publishing Settings', 'wp-discourse' ) );
	}

	/**
	 * ***********************
	 *
	 * Publish Settings Fields.
	 **************************/

	/**
	 * Outputs markup for hide-discourse-name-field checkbox.
	 */
	public function hide_discourse_name_field_checkbox() {
		$this->checkbox_input(
			'hide-discourse-name-field',
			__(
				'Removes the Discourse Name field
	    from the WordPress user profile page.',
				'wp-discourse'
			),
			__(
				'If you enable this setting and also enable the Update
        Userdata webhook, new users created on Discourse will have the their Discourse Name automatically filled in and be
        uneditable on WordPress.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the verbose-publication-logs checkbox.
	 */
	public function verbose_publication_logs() {
		$this->checkbox_input(
			'verbose-publication-logs',
			__(
				'Enable verbose logs for publication.',
				'wp-discourse'
			),
			__( 'Will log successful publications as well as errors.', 'wp-discourse' )
		);
		$this->next_setting_heading( __( 'Comment Settings', 'wp-discourse' ) );
	}

	/**
	 * ***********************
	 *
	 * Comment Settings Fields.
	 **************************/

	/**
	 * Outputs markup for the verbose-comment-logs checkbox.
	 */
	public function verbose_comment_logs() {
		$this->checkbox_input(
			'verbose-comment-logs',
			__(
				'Enable verbose logs for comments.',
				'wp-discourse'
			),
			__( 'Will log successful operations as well as errors.', 'wp-discourse' )
		);
		$this->next_setting_heading( __( 'DiscourseConnect Settings', 'wp-discourse' ) );
	}

	/**
	 * *******************
	 *
	 * DiscourseConnect Settings Fields.
	 **********************/


	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_provider_checkbox() {
		$sso_documentation_url  = get_admin_url( BLOG_ID_CURRENT_SITE, '/admin.php?page=wp_discourse_options&tab=sso_provider&parent_tab=sso_options' );
		$sso_documentation_link = '<a href="' . esc_url( $sso_documentation_url ) . '" target="_blank" rel="noreferrer noopener">' . __( 'DiscourseConnect Provider tab', 'wp-discourse' ) . '</a>';
		$description            = __( 'Use this WordPress instance as the DiscourseConnect provider for your Discourse forum.', 'wp-discourse' );
		$details                = sprintf(
			// translators: enable_sso_provider input. Placeholder: sso_documentation_link.
			__( 'For details about using WordPress as the DiscourseConnect Provider, please visit the %1s of the main site in your network.', 'wp-discourse' ),
			$sso_documentation_link
		);
		$this->checkbox_input( 'enable-sso', $description, $details );
	}

	/**
	 * Outputs markup for sso-client-enabled checkbox.
	 */
	public function enable_sso_client_checkbox() {
		$sso_documentation_url  = get_admin_url( BLOG_ID_CURRENT_SITE, '/admin.php?page=wp_discourse_options&tab=sso_client&parent_tab=sso_options' );
		$sso_documentation_link = '<a href="' . esc_url( $sso_documentation_url ) . '" target="_blank" rel="noreferrer noopener">' . __( 'DiscourseConnect Client tab', 'wp-discourse' ) . '</a>';
		$description            = __( 'Allow your WordPress site to function as a DiscourseConnect client to Discourse.', 'wp-discourse' );
		$details                = sprintf(
			// translators: enable_sso_client checkbox. Placeholder: sso_documentation_link.
			__( 'For details about using WordPress as a DiscourseConnect Client for Discourse, please visit the %1s of the main site in your network.', 'wp-discourse' ),
			$sso_documentation_link
		);
		$this->checkbox_input( 'sso-client-enabled', $description, $details );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		$url = $this->get_site_option( 'url' );
		if ( ! empty( $url ) ) {
			$discourse_sso_url = '<a href="' . esc_url( $url ) . '/admin/site_settings/category/all_results?filter=sso" target="_blank" rel="noreferrer noopener">' .
								 esc_url( $url ) . '/admin/site_settings/category/all_results?filter=sso</a>';
		} else {
			$discourse_sso_url = 'http://forum.example.com/admin/site_settings/category/all_results?filter=sso';
		}

		$description = sprintf(
			// translators: SSO secret input. Placeholder: discourse_sso_url.
			__(
				'The secret key used to verify Discourse DiscourseConnect requests. Set it to a string of text, at least 10
		        characters long. It needs to match the key set at %1$s.',
				'wp-discourse'
			),
			$discourse_sso_url
		);
		$this->input( 'sso-secret', $description );
	}

	/**
	 * Outputs markup for the verbose-sso-logs checkbox.
	 */
	public function verbose_sso_logs() {
		$this->checkbox_input(
			'verbose-sso-logs',
			__(
				'Enable verbose logs for DiscourseConnect.',
				'wp-discourse'
			),
			__( 'Will log successful operations as well as errors.', 'wp-discourse' )
		);
	}

	/**
	 * Creates the network options page.
	 */
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
						src="<?php echo esc_attr( WPDISCOURSE_LOGO ); ?>"
						alt="Discourse logo" class="discourse-logo">
				<?php esc_html_e( 'WP Discourse Network Settings', 'wp-discourse' ); ?>
			</h2>

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

	/**
	 * Saves the options.
	 *
	 * @return null
	 */
	public function save_network_settings() {
		if ( ! isset( $_POST['update_discourse_network_options_nonce'] ) || // Input var okay.
			 ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['update_discourse_network_options_nonce'] ) ), 'update_discourse_network_options' ) // Input var okay.
		) {

			return null;
		}

		if ( ! current_user_can( 'manage_network_options' ) ) {

			return null;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return null;
		}

		if ( isset( $_POST['wpdc_site_options'] ) ) { // Input var okay.

			// See: https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/wiki/Sanitizing-array-input-data.
			// @codingStandardsIgnoreStart
			$site_options = array_map( array( $this, 'sanitize_item' ), wp_unslash( $_POST['wpdc_site_options'] ) ); // Input var okay.
            // @codingStandardsIgnoreEnd
			$this->validate_site_options( $site_options );
		}

		wp_redirect(
			add_query_arg(
				array(
					'page'    => 'discourse_network_options',
					'updated' => 'true',
				),
				network_admin_url( 'admin.php' )
			)
		);

		exit();
	}

	/**
	 * A callback function used for sanitizing array values.
	 *
	 * @param string $item The item to be sanitized.
	 *
	 * @return array|string
	 */
	protected function sanitize_item( $item ) {

		return esc_attr( $item );
	}

	/**
	 * Adds documentation to the top of the settings page.
	 */
	public function network_settings_details() {
		?>
		<p>
			<em>
				<?php
				esc_html_e(
					"The Multisite Configuration option is for the case where one Discourse forum is connected
                to a network of WordPress sites. If, instead, you would like to connect many forums to
                many subsites, don't enable the Multisite Configuration option or any settings on this page. For that case you can
                configure the settings individually on each of your subsites' WP Discourse options pages.",
					'wp-discourse'
				);
				?>
			</em>
		</p>
		<h2><?php esc_html_e( 'Connection', 'wp-discourse' ); ?></h2>
		<p>
			<em>
				<?php
				esc_html_e(
					"If the Multisite Configuration option is enabled, all the subsites in your network will
                be configured to use the API credentials entered on this page. Your forum's API credentials will not be displayed
                on your subsites' settings pages.",
					'wp-discourse'
				);
				?>
			</em>
		</p>
		<h2><?php esc_html_e( 'Webhooks', 'wp-discourse' ); ?></h2>
		<p>
			<em>
				<?php
				esc_html_e(
					"Webhooks can be used to sync data between Discourse and WordPress. Their use is optional,
				but they're easy to setup. In a multisite environment the Sync Comment Data webhook will improve the efficiency of syncing Discourse
				topic data with WordPress. The Update User Data webhook is used to sync user data between WordPress and Discourse when
				a Discourse user is created or updated. When Multisite Configuration is enabled, all webhook options for your network
				are set on this page.",
					'wp-discourse'
				);
				?>
			</em>
		</p>
		<h2><?php esc_html_e( 'DiscourseConnect', 'wp-discourse' ); ?></h2>
		<p>
			<em>
				<?php
				esc_html_e(
					'When Multisite Configuration is enabled, DiscourseConnect functionality to either use WordPress as the
                DiscourseConnect provider for Discourse, or WordPress as a DiscourseConnect client to Discourse is enabled on this page. The DiscourseConnect Secret
                Key is also set here. In a multisite setup, the DiscourseConnect Client functionality is only available when Multisite Configuration
                is enabled.',
					'wp-discourse'
				);
				?>
			</em>
		</p>
		<div class="discourse-doc-section-end">
			<hr class="discourse-options-section-hr">
			<h2><?php esc_html_e( 'Multisite Settings', 'wp-discourse' ); ?></h2>
		</div>
		<?php
	}

	/**
	 * Validates the options and saves them as a site option with the key wpdc_site_options.
	 *
	 * @param array $site_options The array of options to be validated.
	 */
	public function validate_site_options( $site_options ) {
		$updated_options = array();
		foreach ( $site_options as $key => $value ) {
			$filter                  = 'wpdc_validate_site_' . str_replace( '-', '_', $key );
			$value                   = apply_filters( $filter, $value );
			$updated_options[ $key ] = $value;

			if ( 'multisite-configuration-enabled' === $key ) {
				update_site_option( 'wpdc_multisite_configuration', $value );
			}
		}

		update_site_option( 'wpdc_site_options', $updated_options );
	}

	/**
	 * Sets success and error notices.
	 *
	 * This is an awkward way to do it, but I'm not finding a better option.
	 */
	public function network_config_notices() {
		$screen                          = get_current_screen();
		$discourse_screen                = ! empty( $screen->parent_base ) && 'discourse_network_options' === $screen->parent_base;
		$multisite_configuration_enabled = $this->get_site_option( 'multisite-configuration-enabled' );
		if ( $discourse_screen && $multisite_configuration_enabled ) {
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
			} elseif ( empty( $this->check_connection_status() ) ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
							__( 'You are not connected to Discourse. Check that your connection settings are correct. If the issue persists, enable connection logs and check Logs.', 'wp-discourse' ) .
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
							__( 'To use WordPress as the DiscourseConnect Provider, you need to supply a DiscourseConnect Secret Key.', 'wp-discourse' ) .
							'</p></div>';
			}

			if ( ! empty( $sso_client_enabled ) && empty( $sso_secret )
			) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
							__( 'To use WordPress as the DiscourseConnect Client, you need to supply a DiscourseConnect Secret Key.', 'wp-discourse' ) .
							'</p></div>';
			}

			if ( ! empty( $enable_sso ) && ! empty( $sso_client_enabled ) ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
							__( "You can't enable both the DiscourseConnect Client and DiscourseConnect Provider functionality.", 'wp-discourse' ) .
							'</p></div>';
			}

			if ( ! empty( $sso_secret ) && strlen( $sso_secret ) < 10 ) {
				$notices .= '<div class="notice notice-error is-dismissible"><p>' .
							__( 'The DiscourseConnect Secret Key must be at least 10 characters long.', 'wp-discourse' ) .
							'</p></div>';
			}
		}// End if().

		if ( ! empty( $notices ) ) {
			echo wp_kses_post( $notices );
		}
	}

	/**
	 * Returns a single option from the wpdc_site_options array.
	 *
	 * @param string $key The option to find.
	 *
	 * @return bool|mixed
	 */
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
	 * @param string          $option The name of the option.
	 * @param string          $description The description of the settings field.
	 * @param null|string     $type The type of input ('number', 'url', etc).
	 * @param null|int        $min The min value (applied to number inputs).
	 * @param null|int        $max The max value (applies to number inputs).
	 * @param null|int|string $default An optional default value.
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
			<?php
			if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
			}
			?>
			<?php
			if ( isset( $max ) ) {
				echo 'max="' . esc_attr( $max ) . '"';
			}
			?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the markup for a checkbox input.
	 *
	 * @param string $option The option name.
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

	/**
	 * A workaround for creating subsections when option fields are displayed using settings_fields function.
	 *
	 * @param null|string $title The title of the next setting.
	 */
	protected function next_setting_heading( $title = null ) {
		?>
		<div class="discourse-options-section-end">
			<hr class="discourse-options-section-hr">
			<?php if ( $title ) : ?>
				<h2><?php echo esc_attr( $title ); ?></h2>
			<?php endif; ?>
		</div>
		<?php
	}
}
