<?php
/**
 * Connection Settings
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class ConnectionSettings
 */
class ConnectionSettings {

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	protected $display_connection_options;

	/**
	 * ConnectionSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_connection_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_connection_settings() {
		$this->options                    = DiscourseUtilities::get_options();
		$this->display_connection_options = is_main_site() || empty( $this->options['multisite-configuration'] );

		add_settings_section( 'discourse_connection_settings_section', __( 'Connecting With Discourse', 'wp-discourse' ), array(
			$this,
			'connection_settings_tab_details',
		), 'discourse_connect' );

		if ( $this->display_connection_options ) {
			add_settings_field( 'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array(
				$this,
				'url_input',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			add_settings_field( 'discourse_api_key', __( 'API Key', 'wp-discourse' ), array(
				$this,
				'api_key_input',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			add_settings_field( 'discourse_publish_username', __( 'Publishing Username', 'wp-discourse' ), array(
				$this,
				'publish_username_input',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			add_settings_field( 'discourse_use_discourse_webhook', __( 'Use Discourse Webhook', 'wp-discourse' ), array(
				$this,
				'use_discourse_webhook_checkbox',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			add_settings_field( 'discourse_webhook_secret', __( 'Webhook Secret Key', 'wp-discourse' ), array(
				$this,
				'webhook_secret_input',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			add_settings_field( 'discourse_webhook_sync_notification', __( 'Send Email Notification if Webhook Sync Fails', 'wp-discourse' ), array(
				$this,
				'webhook_sync_notification_checkbox',
			), 'discourse_connect', 'discourse_connection_settings_section' );

			if ( is_multisite() && is_main_site() ) {
				add_settings_field( 'discourse_multisite_configuration', __( 'Multisite Configuration', 'wp-discourse' ), array(
					$this,
					'multisite_configuration_checkbox',
				), 'discourse_connect', 'discourse_connection_settings_section' );
			}
		}// End if().

		register_setting( 'discourse_connect', 'discourse_connect', array(
			$this->form_helper,
			'validate_options',
		) );
	}

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	public function url_input() {
		$this->form_helper->input( 'url', 'discourse_connect', __( 'The base URL of your forum, for example http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	public function api_key_input() {
		$discourse_options = $this->options;
		if ( ! empty( $discourse_options['url'] ) ) {
			$this->form_helper->input( 'api-key', 'discourse_connect', __( 'Found on your forum at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) .
			                                                           '/admin/api/keys" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api/keys</a>. ' .
			"If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse' );
		} else {
			$this->form_helper->input( 'api-key', 'discourse_connect', __( "Found on your forum at /admin/api/keys.
			If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->form_helper->input( 'publish-username', 'discourse_connect', __( 'The default Discourse username under which WordPress posts will be published on your forum.
		The Publishing Username is also used for making API calls to Discourse. It must be set to a Discourse admin username.', 'wp-discourse' ) );
	}

	public function use_discourse_webhook_checkbox() {
		$webhook_payload_url = home_url( '/wp-json/wp-discourse/v1/update-topic-content' );
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks' . '" target="_blank">' .
			                          esc_url( $this->options['url'] ) . '/admin/api/web_hooks' . '</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			__( 'A Discourse webhook can be used to improve the efficiency of syncing comments between WordPress and your Discourse forum.
 To use this setting create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>.', 'wp-discourse' ), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->form_helper->checkbox_input( 'use-discourse-webhook', 'discourse_connect', __( 'Use a webhook
		for syncing data between Discourse and WordPress.', 'wp-discourse' ), $description );
	}

	public function webhook_secret_input() {
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks' . '" target="_blank">' .
			                          esc_url( $this->options['url'] ) . '/admin/api/web_hooks' . '</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}
		$description = sprintf(
			__( 'The secret key used to verify Discourse webhook requests. Set it to a string of text, at least 12
		        characters long. It needs to match the key set at %1$s.', 'wp-discourse' ), $discourse_webhooks_url
		);

		$this->form_helper->input( 'webhook-secret', 'discourse_connect', $description );
	}

	public function webhook_sync_notification_checkbox() {
	    $this->form_helper->checkbox_input( 'webhook-sync-notification', 'discourse_connect', __( 'Send email notification to
	    site administrator if webhook sync fails.', 'wp-discourse' ), __( "For posts that have been published to Discourse before
	    WP Discourse version 1.4.0, posts are being matched with Discourse topics through their title. If a match can't be
	    made between any topics and posts, a notification email will be sent to the site's administrator (no more often than
	    once every 12 hours.)", 'wp-discourse' ) );
	}

	public function multisite_configuration_checkbox() {
		$this->form_helper->checkbox_input( 'multisite-configuration', 'discourse_connect', __( 'Configure the plugin for a
	    WordPress multisite setup', 'wp-discourse' ), __( "This setting is intended for the case when a single Discourse forum
	    is connected to a network of WordPress sites. Enabling it will remove the following settings from your network's subsites:", 'wp-discourse' ) );
	}

	/**
	 * Details for the connection_options tab.
	 */
	public function connection_settings_tab_details() {
		$self_install_url          = 'https://github.com/discourse/discourse/blob/master/docs/INSTALL-cloud.md';
		$community_install_url     = 'https://www.literatecomputing.com/product/discourse-install/';
		$discourse_org_install_url = 'https://payments.discourse.org/buy/';
		$setup_howto_url           = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url        = 'https://meta.discourse.org/';
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( "The WP Discourse plugin is used to connect an existing Discourse forum with your WordPress site.
                If you don't already have a Discourse forum, here are some options for setting one up:", 'wp-discourse' ); ?>
			</em>
		</p>
		<ul class="wpdc-documentation-list">
			<em>
				<li>
					<a href="<?php echo esc_url( $self_install_url ); ?>" target="_blank">install it yourself for
						free</a>
				</li>
				<li>
					<a href="<?php echo esc_url( $community_install_url ); ?>" target="_blank">self-supported community
						installation</a>
				</li>
				<li>
					<a href="<?php echo esc_url( $discourse_org_install_url ); ?>" target="_blank">discourse.org
						hosting</a>
				</li>
			</em>
		</ul>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'For detailed instructions on setting up the plugin, please see the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $setup_howto_url ); ?>"
				   target="_blank"><?php esc_html_e( 'WP Discourse plugin installation and setup', 'wp-discourse' ); ?></a>
				<?php esc_html_e( 'topic on the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $discourse_meta_url ); ?>" target="_blank">Discourse Meta</a>
				<?php esc_html_e( 'forum.', 'wp-discourse' ); ?>
			</em>
		</p>
		<?php if ( $this->display_connection_options ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<strong><?php esc_html_e( 'The following settings are used to establish a connection between your site and your forum:', 'wp-discourse' ); ?></strong>
				</em>
			</p>
		<?php else : ?>
			<p class="wpdc-options-documentation wpdc-subsite-documentation">
				<em>
					<strong><?php esc_html_e( "You are using the WP Discourse plugin in a subsite of a multisite installation.
                    The plugin's API credentials are being managed through the installations main site. If you have difficulty
                    connecting to the Discourse forum. Please contact the network administrator.", 'wp-discourse' ); ?></strong>
				</em>
			</p>
		<?php endif; ?>

		<?php
	}
}
