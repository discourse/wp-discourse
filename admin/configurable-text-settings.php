<?php
/**
 * Configurable Text Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class ConfigurableTextSettings
 */
class ConfigurableTextSettings {

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
	 * ConfigurableTextSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_text_settings' ) );
		add_action( 'wpdc_options_page_after_form', array( $this, 'reset_options_form' ) );
		add_action( 'wp_ajax_text_options_reset', array( $this, 'process_text_options_reset' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_text_settings() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_configurable_text_settings_section', __( 'Text Content Settings', 'wp-discourse' ), array(
			$this,
			'configurable_text_tab_details',
		), 'discourse_configurable_text' );

		add_settings_field( 'discourse_link_text', __( 'Discourse link text', 'wp-discourse' ), array(
			$this,
			'discourse_link_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_start_discussion_text', __( '\'Start discussion\' text', 'wp-discourse' ), array(
			$this,
			'start_discussion_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_continue_discussion_text', __( '\'Continue discussion\' text', 'wp-discourse' ), array(
			$this,
			'continue_discussion_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_notable_replies_text', __( 'Top level comments heading', 'wp-discourse' ), array(
			$this,
			'notable_replies_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_comments_not_available_text', __( 'Comments not available', 'wp-discourse' ), array(
			$this,
			'comments_not_available_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_participants_text', __( 'Heading for the \'participants\' section', 'wp-discourse' ), array(
			$this,
			'participants_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_published_at_text', __( '\'Published at\' text', 'wp-discourse' ), array(
			$this,
			'published_at_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_single_reply_text', __( 'Single reply', 'wp-discourse' ), array(
			$this,
			'single_reply_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_many_replies_text', __( 'Many replies', 'wp-discourse' ), array(
			$this,
			'many_replies_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		add_settings_field( 'discourse_more_replies_text', __( 'More replies \'more\' text', 'wp-discourse' ), array(
			$this,
			'more_replies_more_text',
		), 'discourse_configurable_text', 'discourse_configurable_text_settings_section' );

		register_setting( 'discourse_configurable_text', 'discourse_configurable_text', array(
			$this->form_helper,
			'validate_options',
		) );
	}

	/**
	 * Outputs the markup for the discourse-link-text input.
	 */
	public function discourse_link_text() {
		$default = ! empty( $this->options['url'] ) ? preg_replace( '(https?://)', '', esc_url( $this->options['url'] ) ) : '';
		$this->form_helper->input( 'discourse-link-text', 'discourse_configurable_text', __( 'The link-text
		for links to the Discourse topic. Used after the text set in both the \'start discussion\' and \'continue discussion\' settings. It is combined with
		those settings to create the complete links to your forum. Defaults to your forum\'s URL.', 'wp-discourse' ), 'text', null, null, $default );
	}

	/**
	 * Outputs the markup for the start-discussion-text input.
	 */
	public function start_discussion_text() {
		$this->form_helper->input( 'start-discussion-text', 'discourse_configurable_text', __( 'Text used after posts with no comments, for starting a discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the continue-discussion-text input.
	 */
	public function continue_discussion_text() {
		$this->form_helper->input( 'continue-discussion-text', 'discourse_configurable_text', __( 'Text used after posts that have comments, for continuing the discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the notable-replies-text input.
	 */
	public function notable_replies_text() {
		$this->form_helper->input( 'notable-replies-text', 'discourse_configurable_text', __( 'Text used at the top of the comments section, when there are comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the comments-not-available input.
	 */
	public function comments_not_available_text() {
		$this->form_helper->input( 'comments-not-available-text', 'discourse_configurable_text', __( 'Text used beneath the post when there is a configuration error with Discourse.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the participants-text input.
	 */
	public function participants_text() {
		$this->form_helper->input( 'participants-text', 'discourse_configurable_text', __( 'Header text for the participants section, used when there are comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the published-at-text input.
	 */
	public function published_at_text() {
		$this->form_helper->input( 'published-at-text', 'discourse_configurable_text', __( 'Text used on Discourse to link back to the WordPress post.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the single-reply-text input.
	 */
	public function single_reply_text() {
		$this->form_helper->input( 'single-reply-text', 'discourse_configurable_text', __( 'The text used in the Discourse comments template when there is only one reply.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the 'many-replies-text' input.
	 */
	public function many_replies_text() {
		$this->form_helper->input( 'many-replies-text', 'discourse_configurable_text', __( 'Text used in the Discourse comments template when there is more than one reply.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the more-replies-more-text input.
	 */
	public function more_replies_more_text() {
		$this->form_helper->input( 'more-replies-more-text', 'discourse_configurable_text', __( 'Text used when there are more replies.', 'wp-discourse' ) );
	}

	/**
	 * Details for the 'text_content_options' tab.
	 */
	public function configurable_text_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring the plugin\'s user facing text. For detailed instructions, see the  ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Creates the reset_options form.
	 *
	 * @param string $tab The current options tab.
	 */
	public function reset_options_form( $tab ) {
		if ( 'text_content_options' === $tab ) {
			?>
			<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
				  method="post">
				<?php wp_nonce_field( 'text_options_reset', 'text_options_reset_nonce' ); ?>

				<input type="hidden" name="action" value="text_options_reset">
				<?php submit_button( 'Reset Default Values', 'secondary', 'discourse_reset_options', false ); ?>
			</form>
			<?php
		}
	}

	/**
	 * Resets the `discourse_configurable_text` option to its default values.
	 */
	public function process_text_options_reset() {
		if ( ! isset( $_POST['text_options_reset_nonce'] ) || // Input var okay.
			 ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['text_options_reset_nonce'] ) ), 'text_options_reset' ) // Input var okay.
		) {

			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			exit;
		}

		delete_option( 'discourse_configurable_text' );
		add_option( 'discourse_configurable_text', get_option( 'discourse_configurable_text_backup' ) );

		$configurable_text_url = add_query_arg( array(
			'page' => 'wp_discourse_options',
			'tab'  => 'text_content_options',
		), admin_url( 'admin.php' ) );

		wp_safe_redirect( esc_url_raw( $configurable_text_url ) );

		exit;
	}
}
