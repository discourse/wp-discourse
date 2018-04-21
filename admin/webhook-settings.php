<?php
/**
 * Webhook Settings
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class WebhookSettings
 */
class WebhookSettings {
	use PluginUtilities;

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

	/**
	 * Whether or not to display the webhook_options fields.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $display_webhook_options;

	/**
	 * CommentSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_webhook_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_webhook_settings() {
		$this->options                 = $this->get_options();
		$this->display_webhook_options = ! is_multisite() || empty( $this->options['multisite-configuration-enabled'] );

		add_settings_section(
			'discourse_webhook_settings_section', __( 'Webhook Settings', 'wp-discourse' ), array(
				$this,
				'webhook_settings_tab_details',
			), 'discourse_webhook'
		);

		if ( $this->display_webhook_options ) {
			add_settings_field(
				'discourse_use_discourse_webhook', __( 'Sync Comment Data', 'wp-discourse' ), array(
					$this,
					'use_discourse_webhook_checkbox',
				), 'discourse_webhook', 'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_match_old_topics', __( 'Match Old Topics', 'wp-discourse' ), array(
					$this,
					'webhook_match_old_topics_checkbox',
				), 'discourse_webhook', 'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_use_discourse_user_webhook', __( 'Update Userdata', 'wp-discourse' ), array(
					$this,
					'use_discourse_user_webhook_checkbox',
				), 'discourse_webhook', 'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_match_user_email', __( 'Match Users by Email Address', 'wp-discourse' ), array(
					$this,
					'webhook_match_user_email_checkbox',
				), 'discourse_webhook', 'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_secret', __( 'Webhook Secret Key', 'wp-discourse' ), array(
					$this,
					'webhook_secret_input',
				), 'discourse_webhook', 'discourse_webhook_settings_section'
			);

		}// End if().

		register_setting(
			'discourse_webhook', 'discourse_webhook', array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs markup for use-discourse-webhook checkbox.
	 */
	public function use_discourse_webhook_checkbox() {
		$blog_id             = is_multisite() ? get_current_blog_id() : null;
		$webhook_payload_url = get_rest_url( $blog_id, '/wp-discourse/v1/update-topic-content' );
		if ( ! empty( $this->options['url'] ) ) {

			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank">' .
									  esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
				'Before enabling this setting, create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code class="wpdc-select-all">%2$s</code>. Make sure that the \'Post Event\' and the \'Active\' checkboxes are enabled.', 'wp-discourse'
			), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->form_helper->checkbox_input(
			'use-discourse-webhook', 'discourse_webhook', __(
				'Use a webhook
		to sync comment data between Discourse and WordPress.', 'wp-discourse'
			), $description
		);
	}

	/**
	 * Outputs markup for webhook-match-old-topics input.
	 */
	public function webhook_match_old_topics_checkbox() {
		$this->form_helper->checkbox_input(
			'webhook-match-old-topics', 'discourse_webhook', __(
				'Match WordPress posts
	    published prior to WP Discourse version 1.4.0.', 'wp-discourse'
			), __(
				"By default, posts
	    are matched to Discourse topics through their discourse_topic_id metadata. That value isn't available for posts
	    published through WP Discourse prior to version 1.4.0. Enabling this setting will match posts with the post_type
	    'post' to Discourse topics through their titles.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for use-discourse-user-webhook checkbox.
	 */
	public function use_discourse_user_webhook_checkbox() {
		$webhook_payload_url = home_url( '/wp-json/wp-discourse/v1/update-user' );
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank">' .
									  esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
				'Used to automatically fill in the WordPress user\'s Discourse Name field and store their Discourse Id as metadata.
This setting will only be activated if your site is functioning as the SSO provider for Discourse (this can be overridden by hooking into the
\'wpdc_use_discourse_user_webhook\' filter.) Before enabling this setting, create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>. Make sure that only the \'User Event\' checkbox is enabled.', 'wp-discourse'
			), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->form_helper->checkbox_input(
			'use-discourse-user-webhook', 'discourse_webhook', __(
				'Use a webhook
		to sync user data with Discourse.', 'wp-discourse'
			), $description
		);

	}

	/**
	 * Outputs markup for webhook-match-user-email checkbox.
	 */
	public function webhook_match_user_email_checkbox() {
		$this->form_helper->checkbox_input(
			'webhook-match-user-email', 'discourse_webhook', __(
				'Match users with Discourse
        through their email address.', 'wp-discourse'
			), __(
				'Used for syncing accounts that were created before enabling the
        Update Userdata webhook. Existing accounts are synced when the user updates and saves their profile on Discourse.
        <strong>Note: only enable this setting if you are certain that email addresses match between Discourse
        and WordPress.</strong>', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for webhook-secret input.
	 */
	public function webhook_secret_input() {
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank">' .
									  esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Webhook secret input. Placeholder: discourse_webhooks_url.
			__(
				'The secret key used to verify Discourse webhook requests. Set it to a string of text, at least 12
		        characters long. It needs to match the key set at %1$s.', 'wp-discourse'
			), $discourse_webhooks_url
		);

		$this->form_helper->input( 'webhook-secret', 'discourse_webhook', $description );
	}

	/**
	 * Details for the 'webhook_options' tab.
	 */
	public function webhook_settings_tab_details() {
		$setup_howto_url    = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url = 'https://meta.discourse.org/';
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					"Webhooks can be used to sync data between Discourse and WordPress. Their use is optional, but they're easy to
				setup. The WP Discourse plugin has two webhook endpoints, Sync Comment Data and Update Userdata. The
				Sync Comment Data webhook is used to let the plugin know when a Discourse topic has had a new post added to it.
				Using it will reduce the number of API requests made between WordPress and your forum. The Update Userdata
				webhook will only be functional when WordPress is used as the SSO Provider for Discourse. If enabled, it
				automatically fills in the user's WordPress name field when a new account is created or updated on Discourse
				through SSO.", 'wp-discourse'
				);
?>
			</em>
		</p>
		<?php if ( $this->display_webhook_options ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<?php
					esc_html_e(
						"There are some issues with syncing posts that were published from WordPress to
					Discourse before WP Discourse version 1.4.0. Old posts can be synced with their corresponding Discourse
					topic if they are using the post type 'post' and the title of the post matches the title of the Discourse
					topic. To enable this functionality, select the 'Match Old Topics' option.", 'wp-discourse'
					);
?>
				</em>
			</p>
			<p class="wpdc-options-documentation">
				<em>
					<?php esc_html_e( 'For detailed instructions on setting up webhooks, see the ', 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $setup_howto_url ); ?>"
					   target="_blank"><?php esc_html_e( 'WP Discourse plugin installation and setup', 'wp-discourse' ); ?></a>
					<?php esc_html_e( 'topic on the ', 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $discourse_meta_url ); ?>" target="_blank">Discourse Meta</a>
					<?php esc_html_e( 'forum.', 'wp-discourse' ); ?>
				</em>
			</p>
		<?php else : ?>
			<p class="wpdc-options-documentation wpdc-subsite-documentation">
				<em>
					<strong>
					<?php
					esc_html_e(
						"You are using the WP Discourse plugin in a subsite of a multisite installation.
                    The plugin's webhook configuration is being managed through the installation's main site.", 'wp-discourse'
					);
?>
</strong>
				</em>
			</p>
		<?php endif; ?>
		<?php
	}
}
