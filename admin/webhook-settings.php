<?php
/**
 * Webhook Settings
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class WebhookSettings
 */
class WebhookSettings {

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
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_webhook_settings_section', __( 'Webhook Settings', 'wp-discourse'), array(
			$this,
			'webhook_settings_tab_details',
		), 'discourse_webhook' );

		add_settings_field( 'discourse_use_discourse_webhook', __( 'Use Discourse Webhook', 'wp-discourse' ), array(
			$this,
			'use_discourse_webhook_checkbox',
		), 'discourse_webhook', 'discourse_webhook_settings_section' );

		add_settings_field( 'discourse_webhook_match_old_topics', __( 'Match Old Topics', 'wp-discourse' ), array(
			$this,
			'webhook_match_old_topics_checkbox',
		), 'discourse_webhook', 'discourse_webhook_settings_section' );

		add_settings_field( 'discourse_webhook_secret', __( 'Webhook Secret Key', 'wp-discourse' ), array(
			$this,
			'webhook_secret_input',
		), 'discourse_webhook', 'discourse_webhook_settings_section' );

		register_setting( 'discourse_webhook', 'discourse_webhook', array(
			$this->form_helper,
			'validate_options',
		) );
	}

	/**
	 * Outpurs markup for use-discourse-webhook checkbox.
	 */
	public function use_discourse_webhook_checkbox() {
		$webhook_payload_url = home_url( '/wp-json/wp-discourse/v1/update-topic-content' );
		if ( ! empty( $this->options['url'] ) ) {
			$discourse_webhooks_url = '<a href="' . esc_url( $this->options['url'] ) . '/admin/api/web_hooks" target="_blank">' .
			                          esc_url( $this->options['url'] ) . '/admin/api/web_hooks</a>';
		} else {
			$discourse_webhooks_url = 'http://forum.example.com/admin/api/web_hooks';
		}

		$description = sprintf(
		// translators: Discourse webhook description. Placeholder: discourse_webhook_url, webhook_payload_url.
			__( 'A Discourse webhook can be used to improve the efficiency of syncing comments between WordPress and your Discourse forum.
 To use this setting create a new webhook on your forum (found at %1$s.) In the webhook\'s Payload URL field, enter the
 URL <code>%2$s</code>.', 'wp-discourse' ), $discourse_webhooks_url, $webhook_payload_url
		);

		$this->form_helper->checkbox_input( 'use-discourse-webhook', 'discourse_connect', __( 'Use a webhook
		for syncing data between Discourse and WordPress.', 'wp-discourse' ), $description );
	}


	public function webhook_match_old_topics_checkbox() {
		$this->form_helper->checkbox_input( 'webhook-match-old-topics', 'discourse_connect', __( 'Match WordPress posts
	    published prior to WP Discourse version 1.4.0 with Discourse topics.', 'wp-discourse' ), __( "By default, posts
	    are matched to Discourse topics through their discourse_topic_id metadata. That value is't available for posts
	    published through WP Discourse prior to version 1.4.0. Enabling this setting will match posts with the post_type
	    'post' to Discourse topics through their titles.", 'wp-discourse' ) );
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
			__( 'The secret key used to verify Discourse webhook requests. Set it to a string of text, at least 12
		        characters long. It needs to match the key set at %1$s.', 'wp-discourse' ), $discourse_webhooks_url
		);

		$this->form_helper->input( 'webhook-secret', 'discourse_connect', $description );
	}

	/**
	 * Details for the 'webhook_options' tab.
	 */
	function webhook_settings_tab_details() {
		$setup_howto_url    = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url = 'https://meta.discourse.org/';
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( "This section is for configuring Discourse Webhooks. Webhooks may be used to sync data
				between Discourse and your WordPress site. The use of Discourse webhooks is optional, but they're easy to
				setup. On a busy site, using a webhook to sync data with Discourse may noticably improve your WordPress
				site's performance.", 'wp-discourse' ); ?>
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
		<?php
	}


}