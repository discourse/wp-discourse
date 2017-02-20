<?php
/**
 * Configurable Text Settings.
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class ConfigurableTextSettings {
	protected $options;
	protected $option_input;

	public function __construct( $option_input ) {
		$this->option_input = $option_input;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
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
			$this->option_input,
			'discourse_validate_options',
		) );
	}

	/**
	 * ----------------------------------
	 * Configurable text settings fields.
	 * ----------------------------------
	 */

	/**
	 * Outputs the markup for the discourse-link-text input.
	 */
	public function discourse_link_text() {
		$default = ! empty( $this->options['url'] ) ? preg_replace( '(https?://)', '', esc_url( $this->options['url'] ) ) : '';
		$this->option_input->empty_option_text_input( 'discourse-link-text', 'discourse_configurable_text', __( 'The link-text
		for links to the Discourse topic. Used after the text set in both the \'start discussion\' and \'continue discussion\' settings. It is combined with
		those settings to create the complete links to your forum. Defaults to your forum\'s URL.', 'wp-discourse' ), $default );
	}

	/**
	 * Outputs the markup for the start-discussion-text input.
	 */
	public function start_discussion_text() {
		$this->option_input->text_input( 'start-discussion-text', 'discourse_configurable_text', __( 'Text used after posts with no comments, for starting a discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the continue-discussion-text input.
	 */
	public function continue_discussion_text() {
		$this->option_input->text_input( 'continue-discussion-text', 'discourse_configurable_text', __( 'Text used after posts that have comments, for continuing the discussion on Discourse.
		This is combined with the \'Discourse link text\' to create a link back to your forum.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the notable-replies-text input.
	 */
	public function notable_replies_text() {
		$this->option_input->text_input( 'notable-replies-text', 'discourse_configurable_text', __( 'Text used at the top of the comments section, when there are comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the comments-not-available input.
	 */
	public function comments_not_available_text() {
		$this->option_input->text_input( 'comments-not-available-text', 'discourse_configurable_text', __( 'Text used beneath the post when there is a configuration error with Discourse.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the participants-text input.
	 */
	public function participants_text() {
		$this->option_input->text_input( 'participants-text', 'discourse_configurable_text', __( 'Header text for the participants section, used when there are comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the published-at-text input.
	 */
	public function published_at_text() {
		$this->option_input->text_input( 'published-at-text', 'discourse_configurable_text', __( 'Text used on Discourse to link back to the WordPress post.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the single-reply-text input.
	 */
	public function single_reply_text() {
		$this->option_input->text_input( 'single-reply-text', 'discourse_configurable_text', __( 'The text used in the Discourse comments template when there is only one reply.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the 'many-replies-text' input.
	 */
	public function many_replies_text() {
		$this->option_input->text_input( 'many-replies-text', 'discourse_configurable_text', __( 'Text used in the Discourse comments template when there is more than one reply.', 'wp-discourse' ) );
	}

	/**
	 * Outputs the markup for the more-replies-more-text input.
	 */
	public function more_replies_more_text() {
		$this->option_input->text_input( 'more-replies-more-text', 'discourse_configurable_text', __( 'Text used when there are more replies.', 'wp-discourse' ) );
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
}