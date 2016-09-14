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

		// connection
		add_settings_section( 'discourse_connection_settings_section', __( 'Connection Settings', 'wp-discourse' ), array(
			$this,
			'connection_settings_display'
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
			'discourse_validate_options'
		) );

		// publish

		add_settings_section( 'discourse_publishing_settings_section', __( 'Publishing Settings', 'wp-discourse' ), array(
			$this,
			'publishing_settings_display'
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
			'discourse_validate_options'
		) );

		// commenting

		add_settings_section( 'discourse_commenting_settings_section', __( 'Comment Settings', 'wp-discourse' ), array(
			$this,
			'commenting_settings_display'
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

		add_settings_field( 'discourse_custom_excerpt_length', __( 'Custom excerpt length', 'wp-discourse' ), array(
			$this,
			'custom_excerpt_length',
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
			'discourse_validate_options'
		) );

		// sso

		add_settings_section( 'discourse_sso_settings_section', __( 'SSO Settings', 'wp-discourse' ), array(
			$this,
			'sso_settings_display'
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
			'discourse_validate_options'
		) );
	}

	// Settings fields - connection

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	function url_input() {
		$this->text_input( 'url', 'discourse_connect', __( 'e.g. http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	function api_key_input() {
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
	function publish_username_input() {
		$this->text_input( 'publish-username', 'discourse_connect', __( 'Discourse username of publisher (will be overriden if Discourse Username is specified on user)', 'wp-discourse' ) );
	}

	// Settings fields - publishing

	/**
	 * Outputs markup for the display-subcategories checkbox.
	 */
	function display_subcategories() {
		$this->checkbox_input( 'display-subcategories', 'discourse_publish', __( 'Include subcategories in the list of available categories.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category input.
	 */
	function publish_category_input() {
		$this->category_select( 'publish-category', 'discourse_publish', __( 'Default category used to published in Discourse (optional)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category-update input.
	 */
	function publish_category_input_update() {
		$this->checkbox_input( 'publish-category-update', 'discourse_publish', __( 'Update the discourse publish category list, (normally set to refresh every hour)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the use-full-post checkbox.
	 */
	function full_post_checkbox() {
		$this->checkbox_input( 'full-post-content', 'discourse_publish', __( 'Use the full post for content rather than an excerpt.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the auto-publish checkbox.
	 */
	function auto_publish_checkbox() {
		$this->checkbox_input( 'auto-publish', 'discourse_publish', __( 'Publish all new posts to Discourse', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the auto-track checkbox.
	 */
	function auto_track_checkbox() {
		$this->checkbox_input( 'auto-track', 'discourse_publish', __( 'Author automatically tracks published Discourse topics', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the post-types select input.
	 */
	function post_types_select() {
		$this->post_type_select_input( 'allowed_post_types',
			$this->post_types_to_publish( array( 'attachment' ) ),
			__( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple options.', 'wp-discourse' ) );
	}

	// Settings fields - commenting

	/**
	 * Outputs markup for the use-discourse-comments checkbox.
	 */
	function use_discourse_comments_checkbox() {
		$this->checkbox_input( 'use-discourse-comments', 'discourse_comment', __( 'Use Discourse to comment on Discourse published posts', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the show-existing-comments checkbox.
	 */
	function show_existing_comments_checkbox() {
		$this->checkbox_input( 'show-existing-comments', 'discourse_comment', __( 'Display existing WordPress comments beneath Discourse comments', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the existing-comments-heading input.
	 */
	function existing_comments_heading_input() {
		$this->text_input( 'existing-comments-heading', 'discourse_comment', __( 'Heading for existing WordPress comments (e.g. "Historical Comment Archive")', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the max-comments input.
	 */
	function max_comments_input() {
		$this->text_input( 'max-comments', 'discourse_comment', __( 'Maximum number of comments to display', 'wp-discourse' ), 'number' );
	}

	/**
	 * Outputs markup for the min-replies input.
	 */
	function min_replies_input() {
		$this->text_input( 'min-replies', 'discourse_comment', __( 'Minimum replies required prior to pulling comments across', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-score input.
	 */
	function min_score_input() {
		$this->text_input( 'min-score', 'discourse_comment', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-trust-level input.
	 */
	function min_trust_level_input() {
		$this->text_input( 'min-trust-level', 'discourse_comment', __( 'Minimum trust level required prior to pulling comments across (0-5)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the bypass-trust-level input.
	 */
	function bypass_trust_level_input() {
		$this->text_input( 'bypass-trust-level-score', 'discourse_comment', __( 'Bypass trust level check on posts with this score', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the custom-excerpt-length input.
	 */
	function custom_excerpt_length() {
		$this->text_input( 'custom-excerpt-length', 'discourse_comment', __( 'Custom excerpt length in words (default: 55)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the custom-datetime input.
	 */
	function custom_datetime_format() {
		$this->text_input( 'custom-datetime-format', 'discourse_comment', __( 'Custom comment meta datetime string format (default: "', 'wp-discourse' ) .
		                                            get_option( 'date_format' ) . '").' .
		                                            __( ' See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
		                                            __( 'this', 'wp-discourse' ) . '</a>' . __( ' for more info.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the only-show-moderator-liked checkbox.
	 */
	function only_show_moderator_liked_checkbox() {
		$this->checkbox_input( 'only-show-moderator-liked', 'discourse_comment', __( 'Yes', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the debug-mode checkbox.
	 */
	function debug_mode_checkbox() {
		$this->checkbox_input( 'debug-mode', 'discourse_comment', __( '(always refresh comments)', 'wp-discourse' ) );
	}

	// Settings fields - sso

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	function enable_sso_checkbox() {
		$this->checkbox_input( 'enable-sso', 'discourse_sso', __( 'Enable SSO to Discourse', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	function wordpress_login_path() {
		$this->text_input( 'login-path', 'discourse_sso', __( '(Optional) The path to your login page. It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	function sso_secret_input() {
		$this->text_input( 'sso-secret', 'discourse_sso', '' );
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	function redirect_without_login_checkbox() {
		$this->checkbox_input( 'redirect-without-login', 'discourse_sso', __( 'Do not force login for link to Discourse comments thread (No effect if not using SSO)' ) );
	}

	// Form field functions
	// ====================

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string $option The name of the option.
	 * @param string $description The description of the settings field.
	 * @param null $type The type of input ('number', 'url', etc).
	 * @param null $min The min value (applied to number inputs).
	 */
	function text_input( $option, $option_group, $description, $type = null, $min = null ) {
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
		       name='<?php echo $this->option_name( $option, $option_group ); ?>'
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
	 * @param string $label The text for the label.
	 * @param string $description The description of the settings field.
	 */
	function checkbox_input( $option, $option_group, $label, $description = '' ) {
		$options = $this->options;
		if ( array_key_exists( $option, $options ) and 1 === intval( $options[ $option ] ) ) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<label>
			<input id='discourse-<?php echo esc_attr( $option ); ?>'
			       name='<?php echo $this->option_name( $option, $option_group ); ?>' type='checkbox'
			       value='1' <?php echo esc_attr( $checked ); ?> />
			<?php echo esc_html( $label ); ?>
		</label>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Outputs the post-type select input.
	 *
	 * @param string $option Used to set the selected option.
	 * @param array $post_types An array of available post types.
	 * @param string $description The description of the settings field.
	 */
	function post_type_select_input( $option, $post_types, $description = '' ) {
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
	 * @param string $description The description of the settings field.
	 */
	function category_select( $option, $option_group, $description ) {
		$options = $this->options;

		$categories = DiscourseUtilities::get_discourse_categories();

		if ( is_wp_error( $categories ) ) {
			esc_html_e( 'The category list will be synced with Discourse when you establish a connection.', 'wp-discourse' );

			return;
		}

		$selected = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$option_name     = $this->option_name( $option, $option_group );
		$this->option_input( $option, $option_name, $categories, $selected );
	}

	/**
	 * Outputs the markup for an option input.
	 *
	 * @param string $name Suppies the 'name' value for the select input.
	 * @param array $group The array of items to be selected.
	 * @param int $selected The value of the selected option.
	 */
	function option_input( $option, $option_name, $group, $selected ) {
		echo '<select id="discourse-' . esc_attr( $option ) . '" name="' . esc_attr( $option_name ) . '">';

		foreach ( $group as $item ) {
			printf( '<option value="%s"%s>%s</option>',
				esc_attr( $item['id'] ),
				selected( $selected, $item['id'], false ),
				esc_html( $item['name'] )
			);
		}

		echo '</select>';
	}


	/**
	 * Outputs the markup for a text area.
	 *
	 * @param string $option The name of the option.
	 * @param string $description The description of the settings field.
	 */
	function text_area( $option, $description ) {
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


	// Create the options pages

	function discourse_settings_menu() {
		$suffix = add_menu_page(
			__( 'Discourse', 'wp-discourse' ),
			__( 'Discourse', 'wp-discourse' ),
			'manage_options',
			'wp_discourse_options',
			array( $this, 'wp_discourse_options_display' )
		);
		add_action( 'load-' . $suffix, array( $this, 'connection_status_notice' ) );



		add_submenu_page(
			'wp_discourse_options',
			__( 'Connection Options', 'wp-discourse' ),
			__( 'Connection Options', 'wp-discourse' ),
			'manage_options',
			'discourse_connect',
			array( $this, 'wp_discourse_connection_options_display' )
		);

		add_submenu_page(
			'wp_discourse_options',
			__( 'Publishing Options', 'wp-discourse' ),
			__( 'Publishing Options', 'wp-discourse' ),
			'manage_options',
			'discourse_publish',
			array( $this, 'wp_discourse_publishing_options_display' )
		);

		add_submenu_page(
			'wp_discourse_options',
			__( 'Commenting Options', 'wp-discourse' ),
			__( 'Commenting Options', 'wp-discourse' ),
			'manage_options',
			'discourse_comment',
			array( $this, 'wp_discourse_commenting_options_display' )
		);

		add_submenu_page(
			'wp_discourse_options',
			__( 'SSO Options', 'wp-discourse' ),
			__( 'SSO Options', 'wp-discourse' ),
			'manage_options',
			'discourse_sso',
			array( $this, 'wp_discourse_sso_options_display' )
		);
	}

	function wp_discourse_connection_options_display() {
		$this->wp_discourse_options_display( 'connection_options' );
	}

	function wp_discourse_publishing_options_display() {
		$this->wp_discourse_options_display( 'publishing_options' );
	}

	function wp_discourse_commenting_options_display() {
		$this->wp_discourse_options_display( 'commenting_options' );
	}

	function wp_discourse_sso_options_display() {
		$this->wp_discourse_options_display( 'sso_options' );
	}

	// Menu page callbacks

	function connection_settings_display() {
		?>
		<p class="documentation-link">
			<?php esc_html_e( 'This section is for configuring your connection to discourse.', 'wp-discourse' ); ?>
		</p>
		<?php
	}

	function publishing_settings_display() {}

	function commenting_settings_display() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'For documentation on customizing the plugin\'s html, visit ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Template-Customization">https://github.com/discourse/wp-discourse/wiki/Template-Customization</a>
		</p>
		<?php
	}

	function sso_settings_display() {}

	function wp_discourse_options_display( $active_tab = '' ) {
		?>
		<div class="wrap">
			<h2><?php _e( 'WP Discourse Options', 'wp-discourse' ); ?></h2>
			<?php settings_errors(); ?>

			<?php
			if ( isset( $_GET['tab'] ) ) {
				$tab = $_GET['tab'];
			} else {
				$tab = $active_tab;
			}
			?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=wp_discourse_options&tab=connection_options"
				   class="nav-tab <?php echo 'connection_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'Connection', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=publishing_options"
				   class="nav-tab <?php echo 'publishing_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'Publishing', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=commenting_options"
				   class="nav-tab <?php echo 'commenting_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'Commenting', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=sso_options"
				   class="nav-tab <?php echo 'sso_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php _e( 'SSO', 'wp-discourse' ); ?>
				</a>
			</h2>

			<form action="options.php" method="post">
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

	// Utilities

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
	 * Outputs the markup for the 'connected' notice.
	 */
	function connection_status_notice() {
		if ( ! DiscourseUtilities::check_connection_status() ) {
			add_action( 'admin_notices', array( $this, 'disconnected' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'connected' ) );
		}
	}

	/**
	 * Outputs the markup for the 'disconnected' notice.
	 */
	function disconnected() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'You are not currently connected to a Discourse forum. ' .
				                          "To establish a connection, check your settings for 'Discourse URL', 'API Key', and 'Publishing username'. " .
				                          'Also, make sure that your Discourse forum is online.', 'wp-discourse' ); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the 'connected' notice.
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

	protected function option_name( $option, $option_group ) {
		return $option_group . '[' . esc_attr( $option ) . ']';
	}
}
