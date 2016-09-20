<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseAdmin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseAdmin
 */
class DiscourseAdmin {
	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		$this->options = DiscourseUtilities::get_options( array(
			'discourse_connect',
			'discourse_publish',
			'discourse_comment',
			'discourse_sso',
		) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'discourse_settings_menu' ) );
	}

	/**
	 * Enqueues the admin stylesheet.
	 */
	public function admin_styles() {
		wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/css/admin-styles.css' );
		wp_enqueue_style( 'wp_discourse_admin' );
	}

	/**
	 * Settings
	 */
	public function admin_init() {

		// Connection settings.
		add_settings_section( 'discourse_connection_settings_section', __( 'Connection Settings', 'wp-discourse' ), array(
			$this,
			'connection_settings_tab_details',
		), 'discourse_connect' );

		add_settings_field( 'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array(
			$this,
			'url_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		add_settings_field( 'discourse_api_key', __( 'API Key', 'wp-discourse' ), array(
			$this,
			'api_key_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		add_settings_field( 'discourse_publish_username', __( 'Publishing username', 'wp-discourse' ), array(
			$this,
			'publish_username_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		register_setting( 'discourse_connect', 'discourse_connect', array(
			$this,
			'discourse_validate_options',
		) );

		// Publishing settings.
		add_settings_section( 'discourse_publishing_settings_section', __( 'Publishing Settings', 'wp-discourse' ), array(
			$this,
			'publishing_settings_tab_details',
		), 'discourse_publish' );

		add_settings_field( 'discourse_display_subcategories', __( 'Display subcategories', 'wp-discourse' ), array(
			$this,
			'display_subcategories',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_publish_category', __( 'Published category', 'wp-discourse' ), array(
			$this,
			'publish_category_input',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_publish_category_update', __( 'Force category update', 'wp-discourse' ), array(
			$this,
			'publish_category_input_update',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_full_post_content', __( 'Use full post content', 'wp-discourse' ), array(
			$this,
			'full_post_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_custom_excerpt_length', __( 'Custom excerpt length', 'wp-discourse' ), array(
			$this,
			'custom_excerpt_length',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_auto_publish', __( 'Auto Publish', 'wp-discourse' ), array(
			$this,
			'auto_publish_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_auto_track', __( 'Auto Track Published Topics', 'wp-discourse' ), array(
			$this,
			'auto_track_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_allowed_post_types', __( 'Post Types to publish to Discourse', 'wp-discourse' ), array(
			$this,
			'post_types_select',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		register_setting( 'discourse_publish', 'discourse_publish', array(
			$this,
			'discourse_validate_options',
		) );

		// Commenting settings.
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
			$this,
			'discourse_validate_options',
		) );

		// SSO settings.
		add_settings_section( 'discourse_sso_settings_section', __( 'SSO Settings', 'wp-discourse' ), array(
			$this,
			'sso_settings_tab_details',
		), 'discourse_sso' );

		add_settings_field( 'discourse_enable_sso', __( 'Enable SSO', 'wp-discourse' ), array(
			$this,
			'enable_sso_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array(
			$this,
			'wordpress_login_path',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array(
			$this,
			'sso_secret_input',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_redirect_without_login', __( 'Redirect Without Login', 'wp-discourse' ), array(
			$this,
			'redirect_without_login_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		register_setting( 'discourse_sso', 'discourse_sso', array(
			$this,
			'discourse_validate_options',
		) );
	}

	/**
	 * ---------------------------
	 * Connection settings fields.
	 * ---------------------------
	 */

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	public function url_input() {
		$this->text_input( 'url', 'discourse_connect', __( 'e.g. http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	public function api_key_input() {
		$discourse_options = $this->options;
		if ( isset( $discourse_options['url'] ) && ! empty( $discourse_options['url'] ) ) {
			$this->text_input( 'api-key', 'discourse_connect', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) . '/admin/api" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api</a>' );
		} else {
			$this->text_input( 'api-key', 'discourse_connect', __( 'Found at http://discourse.example.com/admin/api', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->text_input( 'publish-username', 'discourse_connect', __( 'The default username under which posts will be published on Discourse. This will be overriden if a Discourse username has been supplied by the user (this can be set on the user\'s WordPress profile page.)', 'wp-discourse' ) );
	}

	/**
	 * ---------------------------
	 * Publishing settings fields.
	 * ---------------------------
	 */

	/**
	 * Outputs markup for the display-subcategories checkbox.
	 */
	public function display_subcategories() {
		$this->checkbox_input( 'display-subcategories', 'discourse_publish', __( 'Include subcategories in the list of available categories.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category input.
	 */
	public function publish_category_input() {
		$this->category_select( 'publish-category', 'discourse_publish', __( 'The default category that your posts will have on Discourse (this can be changed in the \'Publish to Discourse\' meta-box when you create a post.)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category-update input.
	 */
	public function publish_category_input_update() {
		$this->checkbox_input( 'publish-category-update', 'discourse_publish', __( 'Update the discourse publish category list, (normally set to refresh every hour)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the use-full-post checkbox.
	 */
	public function full_post_checkbox() {
		$discourse_admin_posting_url = isset( $this->options['url'] ) && ! empty( $this->options['url'] )  ? $this->options['url'] . '/admin/site_settings/category/posting' : null;
		if ( $discourse_admin_posting_url ) {
			$description = __( '<strong>Note: to keep the \'Show Full Post\'</strong> button from appearing under your post on Discourse, you must unselect the <strong>\'embed truncate\'</strong> setting on Discourse. ' .
			                   'This setting is found at <a href="' . esc_url_raw( $discourse_admin_posting_url ) .'" target="_blank">' . esc_url( $discourse_admin_posting_url ) . '</a>.', 'wp-discourse' );
		} else {
			$description = __( '<strong>Note: to keep the \'Show Full Post\'</strong> button from appearing under your post on Discourse, you must unselect the <strong>\'embed truncate\'</strong> setting on Discourse. ' .
			                   'This setting is found at http://discourse.example.com/admin/site_settings/category/posting.', 'wp-discourse' );
		}

		$this->checkbox_input( 'full-post-content', 'discourse_publish', __( 'Use the full post for content rather than an excerpt.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the custom-excerpt-length input.
	 */
	public function custom_excerpt_length() {
		$this->text_input( 'custom-excerpt-length', 'discourse_publish', __( 'Custom excerpt length in words (default: 55).', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the auto-publish checkbox.
	 */
	public function auto_publish_checkbox() {
		$this->checkbox_input( 'auto-publish', 'discourse_publish', __( 'Publish all new posts to Discourse', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the auto-track checkbox.
	 */
	public function auto_track_checkbox() {
		$this->checkbox_input( 'auto-track', 'discourse_publish', __( 'Author automatically tracks published Discourse topics', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the post-types select input.
	 */
	public function post_types_select() {
		$this->post_type_select_input( 'allowed_post_types',
			$this->post_types_to_publish( array( 'attachment' ) ),
		__( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple options.', 'wp-discourse' ) );
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
		$this->checkbox_input( 'use-discourse-comments', 'discourse_comment', __( 'Use Discourse to comment on Discourse published posts.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the show-existing-comments checkbox.
	 */
	public function show_existing_comments_checkbox() {
		$this->checkbox_input( 'show-existing-comments', 'discourse_comment', __( 'Display existing WordPress comments beneath Discourse comments.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the existing-comments-heading input.
	 */
	public function existing_comments_heading_input() {
		$this->text_input( 'existing-comments-heading', 'discourse_comment', __( 'Heading for existing WordPress comments (e.g. "Historical Comment Archive").', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the max-comments input.
	 */
	public function max_comments_input() {
		$this->text_input( 'max-comments', 'discourse_comment', __( 'Maximum number of comments to display.', 'wp-discourse' ), 'number' );
	}

	/**
	 * Outputs markup for the min-replies input.
	 */
	public function min_replies_input() {
		$this->text_input( 'min-replies', 'discourse_comment', __( 'Minimum replies required prior to pulling comments across.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-score input.
	 */
	public function min_score_input() {
		$this->text_input( 'min-score', 'discourse_comment', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read).', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-trust-level input.
	 */
	public function min_trust_level_input() {
		$this->text_input( 'min-trust-level', 'discourse_comment', __( 'Minimum trust level required prior to pulling comments across (0-5).', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the bypass-trust-level input.
	 */
	public function bypass_trust_level_input() {
		$this->text_input( 'bypass-trust-level-score', 'discourse_comment', __( 'Bypass trust level check on posts with this score.', 'wp-discourse' ), 'number', 0 );
	}


	/**
	 * Outputs markup for the custom-datetime input.
	 */
	public function custom_datetime_format() {
		$this->text_input( 'custom-datetime-format', 'discourse_comment', __( 'Custom comment meta datetime string format (default: "', 'wp-discourse' ) .
		                                                                  get_option( 'date_format' ) . '").' .
		                                                                  __( ' See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
		__( 'this', 'wp-discourse' ) . '</a>' . __( ' for more info.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the only-show-moderator-liked checkbox.
	 */
	public function only_show_moderator_liked_checkbox() {
		$this->checkbox_input( 'only-show-moderator-liked', 'discourse_comment' );
	}

	/**
	 * Outputs markup for the debug-mode checkbox.
	 */
	public function debug_mode_checkbox() {
		$this->checkbox_input( 'debug-mode', 'discourse_comment', __( 'Always refresh comments.', 'wp-discourse' ), __( 'This setting is not recommended for production, when this setting is not enabled comments will be cached for 10 minutes.', 'wp-discourse' ) );
	}

	/**
	 * --------------------
	 * SSO settings fields.
	 * --------------------
	 */

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_checkbox() {
		$this->checkbox_input( 'enable-sso', 'discourse_sso', __( 'Enable SSO to Discourse.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	public function wordpress_login_path() {
		$this->text_input( 'login-path', 'discourse_sso', __( '(Optional) The path to your login page. It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		$options = $this->options;
		if ( isset( $options['url'] ) && ! empty( $options['url'] ) ) {
			$this->text_input( 'sso-secret', 'discourse_sso', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $options['url'] ) . '/admin/site_settings/category/login" target="_blank">' . esc_url( $options['url'] ) . '/admin/site_settings/category/login</a>' );
		} else {
			$this->text_input( 'sso-secret', 'discourse_connect', __( 'Found at http://discourse.example.com/admin/site_settings/category/login', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	public function redirect_without_login_checkbox() {
		$this->checkbox_input( 'redirect-without-login', 'discourse_sso', __( 'Do not force login for link to Discourse comments thread.' ) );
	}

	/**
	 * ---------------------------------------------------------
	 * Methods for creating the plugin's menu and submenu pages.
	 * ---------------------------------------------------------
	 */

	/**
	 * Adds the Discourse menu and submenu page, called from the 'admin_menu' action hook.
	 */
	public function discourse_settings_menu() {
		$settings = add_menu_page(
			__( 'Discourse', 'wp-discourse' ),
			__( 'Discourse', 'wp-discourse' ),
			'manage_options',
			'wp_discourse_options',
			array( $this, 'options_pages_display' ),
			'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTExIDc5LjE1ODMyNSwgMjAxNS8wOS8xMC0wMToxMDoyMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6QzNEQzI4NEU3MjY1MTFFNjlCMTdFMUZCNjUyNTEyQTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6QzNEQzI4NEY3MjY1MTFFNjlCMTdFMUZCNjUyNTEyQTYiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDM0RDMjg0QzcyNjUxMUU2OUIxN0UxRkI2NTI1MTJBNiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpDM0RDMjg0RDcyNjUxMUU2OUIxN0UxRkI2NTI1MTJBNiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PkgZl2kAAAOzSURBVHjafJXLS9RRFMfPPHw/xkeoOIhvMxFlUChUMDM3QknLFrWQ+g+C/oegTeCmlfvCndTCNMJNjNCgC/OxCVNRUUcdx/fY+RzmDpNpF75z7+/+zv2e7zn3nN94amtr5YbBi17FA4/Hc0fnWwrv5eXlrs5Liq+KL4pFReLqYc81xKWKZ4pXXq83mEgkBJixxyO6Z3NyL6p4q3iv2Pwf8W3FiB7uV2VycnIix8fH9oJnN3JzcyU7O9v2Li4ucPRdt18q5pyNP420TvHR5/O1np+fSywWE11LTU2N5Ofnm0KcbG9vy+rqqmRmZkp5ebn4/X5R+7tK/kHPP06mJqU4RzGpRPccaUVFhYRCISkuLrbQi4qKjAyVs7OzMjExIXt7e+YYAaenp9iFlee+Iu7joI7XuvmcsCCtq6uT/v5+ycrKMgcdHR3S3t4uzc3N0tTUJJ2dneZ0eXlZFhcXpbCw0JSr06BykbtvKK7XxQ9FwcHBgZSVlcng4KApaGxslK6uLikoKJCzszMD6jMyMizPGxsbMjw8LPPz81JfX2/51pTF1CaE4qd6WU+4KPLY09NjSjEcGBiw8Mnr0dGROQOHh4c2l5aWSktLi0xNTcn+/r45U9JM8uzVnz7yhhoMA4GAqe7u7jZ1W1tb5pAycyCnXGQ0GpXq6mqL0F0uZ3Q89OqiwdUquWKgljUHXe3+VaO654Dy1tZWc0BUXL7u13Ii4OoUcsJBeTJfTsE/A3uUJ+tYqqqqLG0Q68inRRPOO3kiv5BDmt4U1w0iId87OztW6xC7LkXxJQbuktbW1lK3f9NwkXCGZtnd3bU16pMj5nVhoZR8zczMyMrKioUJ0pW7FgbUP2FHIhFTi/J4PG71rONXitjd9vr6unUUg2dqlncuRJ6pGubR0VFLQ15entUyduzTxX53yxzEY1tbm90yzlwzsOajgxoOQzY2NiZLS0vS19dndby5uWmNROep/ecUMaSQDw0NWcfR2nQi+1wmaeKZbqOVed/b2yvhcFimp6ctx4jQNPEZXUgRc2GECDCcnJw0AtfalZWVZsMz6nNycmR8fNxyzP2QZ815ROneuK8b/wYNLlwGN02xkzuccjAYDNoXDlVEQfVQDTgAGu0vxSO1n3PECzo3kTvIMebSMHYlh0rU8t5ViPsQJStnVrdeKMLpH/piV5scKikpMcKrJJSWq1/Ikk7jmtN3uh7R9e/0Wod4WUFlH6f9l13Xvh4Fng6pU93jz/STnvmp8/nVM38EGAA8rx3ghdSQ8wAAAABJRU5ErkJggg=='
		);
		add_action( 'load-' . $settings, array( $this, 'connection_status_notice' ) );

		$all_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'All Options', 'wp-discourse' ),
			__( 'All Options', 'wp-discourse' ),
			'manage_options',
			'wp_discourse_options'
		);
		add_action( 'load-' . $all_settings, array( $this, 'connection_status_notice' ) );

		$connection_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Connection', 'wp-discourse' ),
			__( 'Connection', 'wp-discourse' ),
			'manage_options',
			'connection_options',
			array( $this, 'connection_options_tab' )
		);
		add_action( 'load-' . $connection_settings, array( $this, 'connection_status_notice' ) );

		$publishing_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Publishing', 'wp-discourse' ),
			__( 'Publishing', 'wp-discourse' ),
			'manage_options',
			'publishing_options',
			array( $this, 'publishing_options_tab' )
		);
		add_action( 'load-' . $publishing_settings, array( $this, 'connection_status_notice' ) );

		$commenting_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Commenting', 'wp-discourse' ),
			__( 'Commenting', 'wp-discourse' ),
			'manage_options',
			'commenting_options',
			array( $this, 'commenting_options_tab' )
		);
		add_action( 'load-' . $commenting_settings, array( $this, 'connection_status_notice' ) );

		$sso_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'SSO', 'wp-discourse' ),
			__( 'SSO', 'wp-discourse' ),
			'manage_options',
			'sso_options',
			array( $this, 'sso_options_tab' )
		);
		add_action( 'load-' . $sso_settings, array( $this, 'connection_status_notice' ) );
	}

	/**
	 * Displays the options options page and options page tabs.
	 *
	 * @param string $active_tab The current tab, used if `$_GET['tab']` is not set.
	 */
	public function options_pages_display( $active_tab = '' ) {
		?>
		<div class="wrap">
			<h2>
				<img
					src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTExIDc5LjE1ODMyNSwgMjAxNS8wOS8xMC0wMToxMDoyMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6QzNEQzI4NEE3MjY1MTFFNjlCMTdFMUZCNjUyNTEyQTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6QzNEQzI4NEI3MjY1MTFFNjlCMTdFMUZCNjUyNTEyQTYiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpGMzQzOTQ2QjcyNjMxMUU2OUIxN0UxRkI2NTI1MTJBNiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpGMzQzOTQ2QzcyNjMxMUU2OUIxN0UxRkI2NTI1MTJBNiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqiLpIEAAAPqSURBVHjahJVLSFxnFMfPjO/XOD6Lb7TxgWgQBVeilCBm1YUQgpBNN1111WXtooR0X7IQiit3tt20tEjAjSa4UKnCUJWowRJHCSoa328n53ecb7gziZkP/nrnu+f5P4/rq62tlc+ce4pexVeKJsUXCp9iV7GqeKl4oQjdZcB3h4NyxU+KR4qgz+f7SCASibjHK8XviqeK14lyKQUFBYl3jxV/ELXf78/k4uzsTM7Pz+Xy8tLASUtLc/J+RaviiWJHMf85B98qRhQBoj45OTFkZGRIIBCQ7OxsSU1NlaOjIzk+PhYNQNLT0102BPO14r1i2hlM9RjvV/yKYRQODg7MaGtrq1RVVZlh3l1dXZnxxcVFWVpaMmclJSVyfX3t7PyieKf4zVuDCsWCIp8fh4eHwn1XV5dFeHNzI8XFxeaILDgYnp+fl5GREZOvqamJ0YcJRbMi7ByMwj0p7+/vS3V1tfT19VlUWVlZ0tHRYXdk4QpMNikpKbK8vCyDg4OyuroqlZWVFkyUMgr/GAe0X0gV0igmEff395syFPX29kowGLSILy4uzIBVVoPBYVFRkTkZGBiwu8LCQqNR7dFd9ynyN/rwkJdw29LSIhUVFVZYssjPz5ednZ0Yx8i5OnEHLRgl05mZGbtHV//TXW/504MCgrReeXm5CbW1tZni7u6uZeMMxwZIn7kjK9DZ2Snt7e0WvctSzwMcfMkTDvBMEXNzc63IUJbsYIzAyKahoUHy8vKMzmgwVTgIxo22voBXakFkn5riRHkXII5KS0u9LRvAgeUDDUwrdSADjkcw6aH7kCcDzyrx+W+fIxYxDsLhcCzyZNGjRydRi7W1NSt0gs6B35sqKa6srJgTl3Iy/sl2a2vLWpX60XEe6sJ+r3BOTo5sb2/L3Nyc7SC3Zz4F6GBOoHZ0dNS4R2djYyM27Xom/N6IaLHMzExpbGw0LukifqOAITe9UEEjQM3Q0JBsbm5KXV2dTE9PG73oRG6L8Fds2SFMBKTc3d1tLUtNiNRNOIo44T4UCsnY2Ji1ZE9Pj8zOzsrCwoJlFZ2DvxX/xTmgExBubm6OpctCIzPmgqD29vZkfX3duGYom5qaZGpqSiYnJ2OZqtyJmvw+bl3jlbVAhMPDwzIxMWEr+/T01IpdX19v2xR6yKasrMwCGh8fN4dk7javnu8Ub9y6DrOuXauiRHQo0IJEhBKOCQDqODgmG3cPA1HjPyp+9n6T3zLScZ+52zTjVjPK0BbdlOacbDyGjxU/KJ57bUFRYWJ/J06wc0Ybe+cGw1HjfyqeKf5NtIWD/xWVfNuTrYPoCsbioT5D7SvFP4qZu3Q+CDAAAzkFrQnbawkAAAAASUVORK5CYII="
					alt="">
				<?php esc_html_e( 'WP Discourse', 'wp-discourse' ); ?>
			</h2>
			<?php settings_errors(); ?>

			<?php
			if ( isset( $_GET['tab'] ) ) { // Input var okay.
				$tab = sanitize_key( wp_unslash( $_GET['tab'] ) ); // Input var okay.
			} elseif ( $active_tab ) {
				$tab = $active_tab;
			} else {
				$tab = 'connection_options';
			}
			?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=wp_discourse_options&tab=connection_options"
				   class="nav-tab <?php echo 'connection_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Connection', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=publishing_options"
				   class="nav-tab <?php echo 'publishing_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Publishing', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=commenting_options"
				   class="nav-tab <?php echo 'commenting_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Commenting', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=sso_options"
				   class="nav-tab <?php echo 'sso_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'SSO', 'wp-discourse' ); ?>
				</a>
			</h2>

			<form action="options.php" method="post" class="wp-discourse-options-form">
				<?php
				switch ( $tab ) {
					case 'connection_options':
						settings_fields( 'discourse_connect' );
						do_settings_sections( 'discourse_connect' );
						break;

					case 'publishing_options':
						settings_fields( 'discourse_publish' );
						do_settings_sections( 'discourse_publish' );
						break;

					case 'commenting_options':
						settings_fields( 'discourse_comment' );
						do_settings_sections( 'discourse_comment' );
						break;

					case 'sso_options':
						settings_fields( 'discourse_sso' );
						do_settings_sections( 'discourse_sso' );
						break;

					default:
						settings_fields( 'discourse_connect' );
						do_settings_sections( 'discourse_connect' );
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Called to display the 'connection_options' tab.
	 */
	public function connection_options_tab() {
		$this->options_pages_display( 'connection_options' );
	}

	/**
	 * Called to display the 'publishing_options' tab.
	 */
	public function publishing_options_tab() {
		$this->options_pages_display( 'publishing_options' );
	}

	/**
	 * Called to display the 'commenting_options' tab.
	 */
	public function commenting_options_tab() {
		$this->options_pages_display( 'commenting_options' );
	}

	/**
	 * Called to display the 'sso_options' tab.
	 */
	public function sso_options_tab() {
		$this->options_pages_display( 'sso_options' );
	}

	/**
	 * -----------------------------------------------------------------
	 * The following methods add markup to the top of each settings tab.
	 * -----------------------------------------------------------------
	 */

	/**
	 * Details for the connection_options tab.
	 */
	function connection_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring your site\'s connection to your Discourse forum. For detailed instructions, see the ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Details for the 'publishing_options' tab.
	 */
	function publishing_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring how the plugin publishes posts to Discourse. For detailed instructions, see the  ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
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

	/**
	 * Details for the 'sso_options' tab.
	 */
	function sso_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring WordPress as the Single Sign On provider for Discourse. Unless you have a need to manage your forum\'s users through your WordPress site, you can leave this setting alone. For more information, see the ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Adds notices to indicate the connection status with Discourse.
	 *
	 * This method is called by the `load-{settings_page_hook}` action - see https://codex.wordpress.org/Plugin_API/Action_Reference/load-(page).
	 */
	function connection_status_notice() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // Input var okay.
		if ( ! $tab ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // Input var okay.
		}

		$current_page = $tab ? $tab : $page;

		if ( ! DiscourseUtilities::check_connection_status() ) {

			if ( 'publishing_options' === $current_page || 'commenting_options' === $current_page || 'sso_options' === $current_page ) {
				add_action( 'admin_notices', array( $this, 'establish_connection' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'disconnected' ) );
			}
		} else if ( 'connection_options' === $current_page || 'wp_discourse_options' === $current_page ) {
			add_action( 'admin_notices', array( $this, 'connected' ) );
		}
	}

	/**
	 * Outputs the markup for the 'disconnected' notice that is displayed on the 'connection_options' tab.
	 */
	function disconnected() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'You are not connected to a Discourse forum. ' .
				                          "Please check your settings for 'Discourse URL', 'API Key', and 'Publishing username'. " .
				'Also, make sure that your Discourse forum is online.', 'wp-discourse' ); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the 'connected' notice that is displayed on the 'connection_options' tab.
	 */
	function connected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'You are connected to Discourse!', 'wp-discourse' ); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the 'establish_connection' notice that is displayed when a connection is
	 * not established on all tabs except 'connection_options'.
	 */
	function establish_connection() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'You are not connected to a Discourse forum. ' .
				"To establish a connection navigate back to the 'Connection' tab and check your settings.", 'wp-discourse' ); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * --------------------
	 * Settings validation.
	 * --------------------
	 */

	/**
	 * The callback for validating the 'discourse' options.
	 *
	 * @param array $inputs The inputs to be validated.
	 *
	 * @return array
	 */
	function discourse_validate_options( $inputs ) {
		$output = array();
		foreach ( $inputs as $key => $input ) {
			$filter = 'validate_' . str_replace( '-', '_', $key );

			if ( ! has_filter( $filter ) ) {
				error_log( 'Missing validation filter: ' . $filter );
			}
			$output[ $key ] = apply_filters( $filter, $input );
		}

		return $output;
	}

	/**
	 * ------------------------
	 * Admin utility functions.
	 * ------------------------
	 */

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $description The description of the settings field.
	 * @param null   $type The type of input ('number', 'url', etc).
	 * @param null   $min The min value (applied to number inputs).
	 */
	protected function text_input( $option, $option_group, $description, $type = null, $min = null ) {
		$options = $this->options;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		if ( array_key_exists( $option, $options ) ) {
			$value = $options[ $option ];
		} else {
			$value = '';
		}

		?>
		<input id='discourse-<?php echo esc_attr( $option ); ?>'
		       name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
		       type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
			<?php if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
} ?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the markup for a checkbox input.
	 *
	 * @param string $option The option name.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $label The text for the label.
	 * @param string $description The description of the settings field.
	 */
	protected function checkbox_input( $option, $option_group, $label = '', $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
			'strong' => array(),
		);
		if ( array_key_exists( $option, $options ) and 1 === intval( $options[ $option ] ) ) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<label>
			<input id='discourse-<?php echo esc_attr( $option ); ?>'
			       name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
			       type='checkbox'
			       value='1' <?php echo esc_attr( $checked ); ?> />
			<?php echo wp_kses( $label, $allowed ); ?>
		</label>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the post-type select input.
	 *
	 * @param string $option Used to set the selected option.
	 * @param array  $post_types An array of available post types.
	 * @param string $description The description of the settings field.
	 */
	protected function post_type_select_input( $option, $post_types, $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'strong' => array(),
		);

		echo "<select multiple id='discourse-allowed-post-types' class='discourse-allowed-types' name='discourse_publish[allowed_post_types][]'>";

		foreach ( $post_types as $post_type ) {

			if ( array_key_exists( $option, $options ) and in_array( $post_type, $options[ $option ], true ) ) {
				$value = 'selected';
			} else {
				$value = '';
			}

			echo '<option ' . esc_attr( $value ) . " value='" . esc_attr( $post_type ) . "'>" . esc_html( $post_type ) . '</option>';
		}

		echo '</select>';
		echo '<p class="description">' . wp_kses( $description, $allowed ) . '</p>';
	}

	/**
	 * Outputs the markup for the categories select input.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $description The description of the settings field.
	 */
	protected function category_select( $option, $option_group, $description ) {
		$options = $this->options;

		$categories = DiscourseUtilities::get_discourse_categories();

		if ( is_wp_error( $categories ) ) {
			esc_html_e( 'The category list will be synced with Discourse when you establish a connection.', 'wp-discourse' );

			return;
		}

		$selected    = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$option_name = $this->option_name( $option, $option_group );
		$this->option_input( $option, $option_name, $categories, $selected, $description );
	}

	/**
	 * Outputs the markup for an option input.
	 *
	 * @param string $option The name of the option to be saved.
	 * @param string $option_name Supplies the 'name' value for the select input.
	 * @param array  $group The array of items to be selected.
	 * @param int    $selected The value of the selected option.
	 * @param string $description The description of the option.
	 */
	protected function option_input( $option, $option_name, $group, $selected, $description ) {
		echo '<select id="discourse-' . esc_attr( $option ) . '" name="' . esc_attr( $option_name ) . '">';

		foreach ( $group as $item ) {
			printf( '<option value="%s"%s>%s</option>',
				esc_attr( $item['id'] ),
				selected( $selected, $item['id'], false ),
				esc_html( $item['name'] )
			);
		}

		echo '</select>';
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}


	/**
	 * Outputs the markup for a text area.
	 *
	 * @param string $option The name of the option.
	 * @param string $description The description of the settings field.
	 */
	protected function text_area( $option, $description ) {
		$options = $this->options;

		if ( array_key_exists( $option, $options ) ) {
			$value = $options[ $option ];
		} else {
			$value = '';
		}

		?>
		<textarea cols=100 rows=6 id='discourse_<?php echo esc_attr( $option ); ?>'
		          name='<?php echo esc_attr( $option ); ?>'><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php

	}

	/**
	 * Returns the 'public' post-types minus the post-types in the 'excluded' array.
	 *
	 * @param array $excluded_types An array of post-types to exclude from publishing to Discourse.
	 *
	 * @return mixed|void
	 */
	protected function post_types_to_publish( $excluded_types = array() ) {
		$post_types = get_post_types( array( 'public' => true ) );
		foreach ( $excluded_types as $excluded ) {
			unset( $post_types[ $excluded ] );
		}

		return apply_filters( 'discourse_post_types_to_publish', $post_types );
	}

	/**
	 * Creates the full option name for the form `name` fields.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The group to save the option to, each option group is saved as an array in the `wp_options` table.
	 *
	 * @return string
	 */
	protected function option_name( $option, $option_group ) {
		return $option_group . '[' . esc_attr( $option ) . ']';
	}
}
