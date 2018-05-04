<?php
/**
 * Configurable Text Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class ConfigurableTextSettings
 */
class ConfigurableTextSettings {
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
		$this->options = $this->get_options();

		add_settings_section(
			'discourse_configurable_text_settings_section', __( 'Text Content Settings', 'wp-discourse' ), array(
				$this,
				'configurable_text_tab_details',
			), 'discourse_configurable_text'
		);

		add_settings_field(
			'discourse_link_text', __( 'Discourse Link', 'wp-discourse' ), array(
				$this,
				'discourse_link_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_start_discussion_text', __( 'Start Discussion', 'wp-discourse' ), array(
				$this,
				'start_discussion_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_continue_discussion_text', __( 'Continue Discussion', 'wp-discourse' ), array(
				$this,
				'continue_discussion_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_join_discussion_text', __( 'Join Discussion', 'wp-discourse' ), array(
				$this,
				'join_discussion_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_no_comments_text', __( 'Join Discussion Link: no Comments', 'wp-discourse' ), array(
				$this,
				'no_comments_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_comments_singular_text', __( 'Join Discussion Link: Comments Singular', 'wp-discourse' ), array(
				$this,
				'comments_singular_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_comments_plural_text', __( 'Join Discussion Link: Comments Plural', 'wp-discourse' ), array(
				$this,
				'comments_plural_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_notable_replies_text', __( 'Top Level Comments Heading', 'wp-discourse' ), array(
				$this,
				'notable_replies_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_comments_not_available_text', __( 'Comments Not Available', 'wp-discourse' ), array(
				$this,
				'comments_not_available_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_participants_text', __( 'Participants Heading', 'wp-discourse' ), array(
				$this,
				'participants_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_published_at_text', __( 'Published at Text', 'wp-discourse' ), array(
				$this,
				'published_at_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_single_reply_text', __( 'Single Reply', 'wp-discourse' ), array(
				$this,
				'single_reply_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_many_replies_text', __( 'Many Replies', 'wp-discourse' ), array(
				$this,
				'many_replies_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_more_replies_text', __( 'More Replies', 'wp-discourse' ), array(
				$this,
				'more_replies_more_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_external_login_text', __( 'External Login Text', 'wp-discourse' ), array(
				$this,
				'external_login_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_link_to_discourse_text', __( 'Link Accounts Text', 'wp-discourse' ), array(
				$this,
				'link_to_discourse_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		add_settings_field(
			'discourse_linked_to_discourse_text', __( 'Account is Linked Text', 'wp-discourse' ), array(
				$this,
				'linked_to_discourse_text',
			), 'discourse_configurable_text', 'discourse_configurable_text_settings_section'
		);

		register_setting(
			'discourse_configurable_text', 'discourse_configurable_text', array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs the markup for the discourse-link-text input.
	 */
	public function discourse_link_text() {
		$default = ! empty( $this->options['url'] ) ? preg_replace( '(https?://)', '', esc_url( $this->options['url'] ) ) : '';
		$this->form_helper->input(
			'discourse-link-text', 'discourse_configurable_text', __(
				'The link-text
		for links to the Discourse topic. Used after the text set in both the \'start discussion\' and \'continue discussion\' settings. It is combined with
		those settings to create the complete links to your forum. Defaults to your forum\'s URL.', 'wp-discourse'
			), 'text', null, null, $default
		);
	}

	/**
	 * Outputs the markup for the start-discussion-text input.
	 */
	public function start_discussion_text() {
		$this->form_helper->input(
			'start-discussion-text', 'discourse_configurable_text', __(
				'Text used after posts with no comments, for starting a discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the join-discussion-text input.
	 */
	public function join_discussion_text() {
		$this->form_helper->input(
			'join-discussion-text', 'discourse_configurable_text', __(
				"Text used after posts with comments on Discourse, but no comments that are displayed on Discourse.
	                    This is combined with the 'Discourse link text' to create a link to your forum."
			)
		);
	}

	/**
	 * Outputs the markup for the continue-discussion-text input.
	 */
	public function continue_discussion_text() {
		$this->form_helper->input(
			'continue-discussion-text', 'discourse_configurable_text', __(
				'Text used after posts that have comments, for continuing the discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the comments-singular-text input.
	 */
	public function comments_singular_text() {
		$this->form_helper->input(
			'comments-singular-text', 'discourse_configurable_text', __(
				"Text used when the 'Link to Comments Without Displaying Them' option is selected and one comment has been created. (The number 1 will be prepended to the text.)", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the comments-plural-text input.
	 */
	public function comments_plural_text() {
		$this->form_helper->input(
			'comments-plural-text', 'discourse_configurable_text', __(
				"Text used when the 'Link to Comments Without Displaying Them' option is selected and multiple comments have been created. (The number of comments will be prepended to the text.)", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the no-comments-text input.
	 */
	public function no_comments_text() {
		$this->form_helper->input(
			'no-comments-text', 'discourse_configurable_text', __(
				"Text used when the 'Link to Comments Without Displaying Them' option is selected and no comments have been created.", 'wp-discourse'
			)
		);
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
		$this->form_helper->input(
			'more-replies-more-text', 'discourse_configurable_text', __(
				"Text used when there are more replies on Discourse than are being shown on WordPress.
		For example, if there are 10 replies on Discourse and 5 replies on WordPress, the text '5 more replies' will be shown underneath the comments section.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the external-login-text input.
	 */
	public function external_login_text() {
		$this->form_helper->input( 'external-login-text', 'discourse_configurable_text', __( 'Text for the login page login link when Discourse is used as the SSO provider.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the link-to-discourse-text input.
	 */
	public function link_to_discourse_text() {
		$this->form_helper->input(
			'link-to-discourse-text', 'discourse_configurable_text', __(
				'Text added to the login and profile pages when Discourse is used as the
	    SSO provider. Used for linking existing accounts between Discourse and WordPress.', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the linked-to-discourse-text input.
	 */
	public function linked_to_discourse_text() {
		$this->form_helper->input(
			'linked-to-discourse-text', 'discourse_configurable_text', __(
				"Text added to the user's profile page when Discourse is used as the
	    SSO proveder. Used to indicate that the user's account is linked to Discourse.", 'wp-discourse'
			)
		);
	}

	/**
	 * Details for the 'text_content_options' tab.
	 */
	public function configurable_text_tab_details() {
		?>
		<p class="wpdc-options-documentation">
			<em><?php esc_html_e( "This section is for configuring the plugin's user facing text.", 'wp-discourse' ); ?></em>
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

		$configurable_text_url = add_query_arg(
			array(
				'page' => 'wp_discourse_options',
				'tab'  => 'text_content_options',
			), admin_url( 'admin.php' )
		);

		wp_safe_redirect( esc_url_raw( $configurable_text_url ) );

		exit;
	}
}
