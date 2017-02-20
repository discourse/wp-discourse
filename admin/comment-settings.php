<?php
/**
 * Commenting Settings.
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class CommentSettings {
	protected $options;
	protected $option_input;

	public function __construct( $option_input ) {
		$this->option_input = $option_input;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_commenting_settings_section', __( 'Comment Settings', 'wp-discourse' ), array(
			$this,
			'commenting_settings_tab_details',
		), 'discourse_comment' );

		add_settings_field( 'discourse_use_discourse_comments', __( 'Use Discourse Comments', 'wp-discourse' ), array(
			$this,
			'use_discourse_comments_checkbox',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_show_existing_comments', __( 'Show Existing WP Comments', 'wp-discourse' ), array(
			$this,
			'show_existing_comments_checkbox',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_existing_comments_heading', __( 'Existing Comments Heading', 'wp-discourse' ), array(
			$this,
			'existing_comments_heading_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_max_comments', __( 'Max visible comments', 'wp-discourse' ), array(
			$this,
			'max_comments_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_min_replies', __( 'Min number of replies', 'wp-discourse' ), array(
			$this,
			'min_replies_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_min_score', __( 'Min score of posts', 'wp-discourse' ), array(
			$this,
			'min_score_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_min_trust_level', __( 'Min trust level', 'wp-discourse' ), array(
			$this,
			'min_trust_level_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_bypass_trust_level_score', __( 'Bypass trust level score', 'wp-discourse' ), array(
			$this,
			'bypass_trust_level_input',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_custom_datetime_format', __( 'Custom Datetime Format', 'wp-discourse' ), array(
			$this,
			'custom_datetime_format',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_only_show_moderator_liked', __( 'Only import comments liked by a moderator', 'wp-discourse' ), array(
			$this,
			'only_show_moderator_liked_checkbox',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		add_settings_field( 'discourse_debug_mode', __( 'Debug mode', 'wp-discourse' ), array(
			$this,
			'debug_mode_checkbox',
		), 'discourse_comment', 'discourse_commenting_settings_section' );

		register_setting( 'discourse_comment', 'discourse_comment', array(
			$this->option_input,
			'discourse_validate_options',
		) );
	}

	/**
	 * ---------------------------
	 * Commenting settings fields.
	 * ---------------------------
	 */

	/**
	 * Outputs markup for the use-discourse-comments checkbox.
	 */
	public function use_discourse_comments_checkbox() {
		$this->option_input->checkbox_input( 'use-discourse-comments', 'discourse_comment', __( 'Use Discourse to comment on Discourse published posts.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the show-existing-comments checkbox.
	 */
	public function show_existing_comments_checkbox() {
		$this->option_input->checkbox_input( 'show-existing-comments', 'discourse_comment', __( 'Display existing WordPress comments beneath Discourse comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the existing-comments-heading input.
	 */
	public function existing_comments_heading_input() {
		$this->option_input->text_input( 'existing-comments-heading', 'discourse_comment', __( 'Heading for existing WordPress comments (e.g. "Historical Comment Archive".)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the max-comments input.
	 */
	public function max_comments_input() {
		$this->option_input->text_input( 'max-comments', 'discourse_comment', __( 'Maximum number of comments to display.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-replies input.
	 */
	public function min_replies_input() {
		$this->option_input->text_input( 'min-replies', 'discourse_comment', __( 'Minimum replies required prior to pulling comments across.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-score input.
	 */
	public function min_score_input() {
		$this->option_input->text_input( 'min-score', 'discourse_comment', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read.)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-trust-level input.
	 */
	public function min_trust_level_input() {
		$this->option_input->text_input( 'min-trust-level', 'discourse_comment', __( 'Minimum trust level required prior to pulling comments across (0-5).', 'wp-discourse' ), 'number', 0, 5 );
	}

	/**
	 * Outputs markup for the bypass-trust-level input.
	 */
	public function bypass_trust_level_input() {
		$this->option_input->text_input( 'bypass-trust-level-score', 'discourse_comment', __( 'Bypass trust level check on posts with this score.', 'wp-discourse' ), 'number', 0 );
	}


	/**
	 * Outputs markup for the custom-datetime input.
	 */
	public function custom_datetime_format() {
		$this->option_input->text_input( 'custom-datetime-format', 'discourse_comment', __( 'Custom comment meta datetime string format (default: "', 'wp-discourse' ) .
		                                                                  get_option( 'date_format' ) . '").' .
		                                                                  __( ' See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
		                                                                  __( 'this', 'wp-discourse' ) . '</a>' . __( ' for more info.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the only-show-moderator-liked checkbox.
	 */
	public function only_show_moderator_liked_checkbox() {
		$this->option_input->checkbox_input( 'only-show-moderator-liked', 'discourse_comment' );
	}

	/**
	 * Outputs markup for the debug-mode checkbox.
	 */
	public function debug_mode_checkbox() {
		$this->option_input->checkbox_input( 'debug-mode', 'discourse_comment', __( 'Always refresh comments.', 'wp-discourse' ), __( 'This setting is not recommended for production, when this setting is not enabled comments will be cached for 10 minutes.', 'wp-discourse' ) );
	}

	/**
	 * Details for the 'commenting_options' tab.
	 */
	function commenting_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring how comments are published on your WordPress site. For detailed instructions, see the  ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
			<em><?php esc_html_e( ' For documentation on customizing the html templates that are used for comments, see the ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Template-Customization">Template
				Customization</a>
			<em><?php esc_html_e( ' section of the wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}
}