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
			'discourse_webhook_settings_section',
			__( 'Webhook Settings', 'wp-discourse' ),
			array(
				$this,
				'webhook_settings_tab_details',
			),
			'discourse_webhook'
		);

		if ( $this->display_webhook_options ) {
			add_settings_field(
				'discourse_use_discourse_webhook',
				__( 'Sync Comment Data', 'wp-discourse' ),
				array(
					$this,
					'use_discourse_webhook_checkbox',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_match_old_topics',
				__( 'Match Posts by Title', 'wp-discourse' ),
				array(
					$this,
					'webhook_match_old_topics_checkbox',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_use_discourse_user_webhook',
				__( 'Update Userdata', 'wp-discourse' ),
				array(
					$this,
					'use_discourse_user_webhook_checkbox',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_match_user_email',
				__( 'Match Users by Email', 'wp-discourse' ),
				array(
					$this,
					'webhook_match_user_email_checkbox',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_webhook_secret',
				__( 'Webhook Secret Key', 'wp-discourse' ),
				array(
					$this,
					'webhook_secret_input',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

			add_settings_field(
				'discourse_verbose_webhook_logs',
				__( 'Verbose Webhook Logs', 'wp-discourse' ),
				array(
					$this,
					'verbose_webhook_logs',
				),
				'discourse_webhook',
				'discourse_webhook_settings_section'
			);

		}// End if().

		register_setting(
			'discourse_webhook',
			'discourse_webhook',
			array(
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

			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank" rel="noreferrer noopener">' .
										esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
				'<strong>URL:</strong><code>%2$s</code>
         <strong>Events:</strong> "Post is created", "Post is updated".',
				'wp-discourse'
			),
			$discourse_webhooks_url,
			$webhook_payload_url
		);

		$this->form_helper->checkbox_input(
			'use-discourse-webhook',
			'discourse_webhook',
			__(
				'Enable the Sync Comment Data webhook endpoint.',
				'wp-discourse'
			),
			$description
		);
	}

	/**
	 * Outputs markup for webhook-match-old-topics input.
	 */
	public function webhook_match_old_topics_checkbox() {
		$this->form_helper->checkbox_input(
			'webhook-match-old-topics',
			'discourse_webhook',
			__(
				'Match WordPress posts with Discourse topics by title.',
				'wp-discourse'
			),
			__(
				'Sync Comment Data will attempt to match posts to topics by title if other methods of matching have not worked.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for use-discourse-user-webhook checkbox.
	 */
	public function use_discourse_user_webhook_checkbox() {
		$webhook_payload_url = home_url( '/wp-json/wp-discourse/v1/update-user' );
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank" rel="noreferrer noopener">' .
										esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
			// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__(
        '<strong>URL:</strong><code>%2$s</code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
         <strong>Events:</strong> "User is created", "User is Updated".',
				'wp-discourse'
			),
			$discourse_webhooks_url,
			$webhook_payload_url
		);

		$this->form_helper->checkbox_input(
			'use-discourse-user-webhook',
			'discourse_webhook',
			__(
				'Enable the Update Userdata webhook endpoint.',
				'wp-discourse'
			),
			$description
		);
	}

	/**
	 * Outputs markup for webhook-match-user-email checkbox.
	 */
	public function webhook_match_user_email_checkbox() {
		$this->form_helper->checkbox_input(
			'webhook-match-user-email',
			'discourse_webhook',
			__(
				'Match WordPress users with Discourse users by email.',
				'wp-discourse'
			),
			__(
				'Update Userdata will attempt to match users by email if other methods of matching have not worked.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for webhook-secret input.
	 */
	public function webhook_secret_input() {
		$description = sprintf(
			// translators: Webhook secret input.
			__(
				'String of text at least 12 characters long.',
				'wp-discourse'
			),
		);

		$this->form_helper->input( 'webhook-secret', 'discourse_webhook', $description );
	}

	/**
	 * Outputs markup for the discourse_verbose_webhook_logs checkbox.
	 */
	public function verbose_webhook_logs() {
		$this->form_helper->checkbox_input(
			'verbose-webhook-logs',
			'discourse_webhook',
			__(
				'Enable verbose logs for webhooks.',
				'wp-discourse'
			),
			__( 'Will log successful syncs as well as errors.', 'wp-discourse' ) . ' View logs in the <a href="?page=wp_discourse_options&tab=log_viewer">' . __( 'Log Viewer', 'wp-discourse' ) . '</a>.'
		);
	}

	/**
	 * Details for the 'webhook_options' tab.
	 */
	public function webhook_settings_tab_details() {
		$setup_howto_url = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		?>
    <p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					'This section is for configuring Discourse webhooks.',
					'wp-discourse'
				);
				?>
			</em>
		</p>
		<?php if ( $this->display_webhook_options ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<?php esc_html_e( 'For instructions on setting up webhooks see ', 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $setup_howto_url ); ?>"
						target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Configure WP Discourse webhooks.', 'wp-discourse' ); ?></a>
				</em>
			</p>
		<?php else : ?>
			<p class="wpdc-options-documentation wpdc-subsite-documentation">
				<em>
					<strong>
					<?php
					esc_html_e(
						"You are using the WP Discourse plugin in a subsite of a multisite installation.
                    The plugin's webhook configuration is being managed through the installation's main site.",
						'wp-discourse'
					);
					?>
</strong>
				</em>
			</p>
		<?php endif; ?>
		<?php
	}
}
