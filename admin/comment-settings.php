<?php
/**
 * Commenting Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

/**
 * Class CommentSettings
 */
class CommentSettings {

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

	/**
	 * CommentSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_comment_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_comment_settings() {
		add_settings_section(
			'discourse_commenting_settings_section', __( 'Comment Settings', 'wp-discourse' ), array(
				$this,
				'commenting_settings_tab_details',
			), 'discourse_comment'
		);

		add_settings_field(
			'discourse_use_discourse_comments', __( 'Use Discourse Comments', 'wp-discourse' ), array(
				$this,
				'use_discourse_comments_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_add_join_link', __( 'Link to Comments Without Displaying Them', 'wp-discourse' ), array(
				$this,
				'add_join_link_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_cache_html', __( 'Cache Comment HTML', 'wp-discourse' ), array(
				$this,
				'cache_html_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_clear_cached_comment_html', __( 'Clear Cached Comment HTML', 'wp-discourse' ), array(
				$this,
				'clear_cached_comment_html_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_ajax_load', __( 'Load Comments With Ajax', 'wp-discourse' ), array(
				$this,
				'ajax_load_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_load_comment_css', __( 'Load Comment CSS', 'wp-discourse' ), array(
				$this,
				'load_comment_css_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_new_tab', __( 'Open Links in New Tab', 'wp-discourse' ), array(
				$this,
				'discourse_new_tab_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_show_existing_comments', __( 'Show Existing WP Comments', 'wp-discourse' ), array(
				$this,
				'show_existing_comments_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_existing_comments_heading', __( 'Existing Comments Heading', 'wp-discourse' ), array(
				$this,
				'existing_comments_heading_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_max_comments', __( 'Max Visible Comments', 'wp-discourse' ), array(
				$this,
				'max_comments_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_min_replies', __( 'Min Number of Replies', 'wp-discourse' ), array(
				$this,
				'min_replies_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_min_score', __( 'Min Score of Posts', 'wp-discourse' ), array(
				$this,
				'min_score_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_min_trust_level', __( 'Min Trust Level', 'wp-discourse' ), array(
				$this,
				'min_trust_level_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_bypass_trust_level_score', __( 'Bypass Trust Level Score', 'wp-discourse' ), array(
				$this,
				'bypass_trust_level_input',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_custom_datetime_format', __( 'Custom Datetime Format', 'wp-discourse' ), array(
				$this,
				'custom_datetime_format',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		add_settings_field(
			'discourse_only_show_moderator_liked', __( 'Only Import Moderator-Liked', 'wp-discourse' ), array(
				$this,
				'only_show_moderator_liked_checkbox',
			), 'discourse_comment', 'discourse_commenting_settings_section'
		);

		register_setting(
			'discourse_comment', 'discourse_comment', array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs markup for the use-discourse-comments checkbox.
	 */
	public function use_discourse_comments_checkbox() {
		$this->form_helper->checkbox_input(
			'use-discourse-comments', 'discourse_comment', __( 'Use Discourse to comment on Discourse published posts.', 'wp-discourse' ),
			__( 'For Discourse comments to appear on your WordPress site, you must select this setting and enable comments for the WordPress post.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the add-join-link checkbox.
	 */
	public function add_join_link_checkbox() {
		$this->form_helper->checkbox_input(
			'add-join-link', 'discourse_comment', __(
				"Add a 'Join Discussion' link underneath posts that are published
	            to Discourse.", 'wp-discourse'
			), __( 'This setting is used in place of showing Discourse comments underneath the post.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the cache-html checkbox.
	 */
	public function cache_html_checkbox() {
		$this->form_helper->checkbox_input(
			'cache-html', 'discourse_comment', __(
				'Cache the Discourse comment HTML that is generated by the plugin.', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the clear-cached-comment-html checkbox.
	 */
	public function clear_cached_comment_html_checkbox() {
		$this->form_helper->checkbox_input(
			'clear-cached-comment-html', 'discourse_comment', __(
				'Selecting this option will clear all cached comment HTML.', 'wp-discourse'
			), __( 'Only enabled for a single request.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the discourse-new-tab checkbox.
	 */
	public function ajax_load_checkbox() {
		$this->form_helper->checkbox_input(
			'ajax-load', 'discourse_comment', __( 'Load comments with Ajax.', 'wp-discourse' ),
			__(
				'This is useful if page caching is preventing Discourse comments from updating on WordPress. When this setting is enabled, old WordPress comments
			cannot be displayed beneath the Discourse comments.', 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the load-comment-css checkbox.
	 */
	public function load_comment_css_checkbox() {
		$this->form_helper->checkbox_input(
			'load-comment-css', 'discourse_comment', __( 'Loads a CSS file for styling comments', 'wp-discourse' ),
			__( 'This is currently adding styles to Discourse oneboxes and quotes.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the discourse-new-tab checkbox.
	 */
	public function discourse_new_tab_checkbox() {
		$this->form_helper->checkbox_input(
			'discourse-new-tab', 'discourse_comment', __( 'Open links to Discourse in a new tab.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the show-existing-comments checkbox.
	 */
	public function show_existing_comments_checkbox() {
		$this->form_helper->checkbox_input( 'show-existing-comments', 'discourse_comment', __( 'Display existing WordPress comments beneath Discourse comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the existing-comments-heading input.
	 */
	public function existing_comments_heading_input() {
		$this->form_helper->input( 'existing-comments-heading', 'discourse_comment', __( 'Heading for existing WordPress comments (for example, "Historical Comment Archive".)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the max-comments input.
	 */
	public function max_comments_input() {
		$this->form_helper->input(
			'max-comments', 'discourse_comment', __(
				"Maximum number of comments to display. To display a link to the Discourse
		topic, without displaying comments on WordPress, set 'max visible comments' to 0.", 'wp-discourse'
			), 'number', 0
		);
	}

	/**
	 * Outputs markup for the min-replies input.
	 */
	public function min_replies_input() {
		$this->form_helper->input( 'min-replies', 'discourse_comment', __( 'Minimum replies required prior to pulling comments across.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-score input.
	 */
	public function min_score_input() {
		$this->form_helper->input( 'min-score', 'discourse_comment', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read.)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-trust-level input.
	 */
	public function min_trust_level_input() {
		$this->form_helper->input(
			'min-trust-level', 'discourse_comment', __(
				'Minimum Discourse user trust level required
		for comments that are pulled to WordPress. (Trust levels range between 0 and 5.)', 'wp-discourse'
			), 'number', 0, 5
		);
	}

	/**
	 * Outputs markup for the bypass-trust-level input.
	 */
	public function bypass_trust_level_input() {
		$this->form_helper->input( 'bypass-trust-level-score', 'discourse_comment', __( 'Bypass trust level check on posts with this score.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the custom-datetime input.
	 */
	public function custom_datetime_format() {
		$this->form_helper->input(
			'custom-datetime-format', 'discourse_comment', __( 'The datetime format used for displaying the comment date/time. (default: "', 'wp-discourse' ) .
																				  get_option( 'date_format' ) . '").' .
																				  __( ' See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
			__( 'this page', 'wp-discourse' ) . '</a>' . __( ' for more information.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the only-show-moderator-liked checkbox.
	 */
	public function only_show_moderator_liked_checkbox() {
		$this->form_helper->checkbox_input( 'only-show-moderator-liked', 'discourse_comment', __( "Only import comments 'liked' by a Discourse moderator.", 'wp-discourse' ) );
	}

	/**
	 * Details for the 'commenting_options' tab.
	 */
	public function commenting_settings_tab_details() {
		$setup_howto_url            = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url         = 'https://meta.discourse.org/';
		$template_customization_url = 'https://meta.discourse.org/t/wp-discourse-template-customization/50754';
		?>
		<p class="wpdc-options-documentation">
			<em><?php esc_html_e( 'This section is for configuring how Discourse comments are displayed on your WordPress site.', 'wp-discourse' ); ?></em>
		</p>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'For detailed instructions, see the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $setup_howto_url ); ?>"
				   target="_blank"><?php esc_html_e( 'WP Discourse plugin installation and setup', 'wp-discourse' ); ?></a>
				<?php esc_html_e( 'and', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $template_customization_url ); ?>"
				   target="_blank"><?php esc_html_e( 'WP Discourse template customization', 'wp-discourse' ); ?></a>
				<?php esc_html_e( 'topics on the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $discourse_meta_url ); ?>" target="_blank">Discourse Meta</a>
				<?php esc_html_e( 'forum.', 'wp-discourse' ); ?>
			</em>
		</p>
		<?php
	}
}
