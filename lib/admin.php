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
		$this->options = get_option( 'discourse' );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_menu', array( $this, 'discourse_admin_menu' ) );
		add_action( 'load-settings_page_discourse', array( $this, 'connection_status_notice' ) );
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
		register_setting( 'discourse', 'discourse', array( $this, 'discourse_validate_options' ) );
		add_settings_section( 'discourse_wp_api', __( 'Common Settings', 'wp-discourse' ), array(
			$this,
			'init_default_settings',
		), 'discourse' );

		add_settings_section( 'discourse_wp_publish', __( 'Publishing Settings', 'wp-discourse' ), array(
			$this,
			'init_default_settings',
		), 'discourse' );
		add_settings_section( 'discourse_comments', __( 'Comments Settings', 'wp-discourse' ), array(
			$this,
			'init_comment_settings',
		), 'discourse' );
		add_settings_section( 'discourse_wp_sso', __( 'SSO Settings', 'wp-discourse' ), array(
			$this,
			'init_default_settings',
		), 'discourse' );

		add_settings_field( 'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array(
			$this,
			'url_input',
		), 'discourse', 'discourse_wp_api' );
		add_settings_field( 'discourse_api_key', __( 'API Key', 'wp-discourse' ), array(
			$this,
			'api_key_input',
		), 'discourse', 'discourse_wp_api' );
		add_settings_field( 'discourse_publish_username', __( 'Publishing username', 'wp-discourse' ), array(
			$this,
			'publish_username_input',
		), 'discourse', 'discourse_wp_api' );

		add_settings_field( 'discourse_enable_sso', __( 'Enable SSO', 'wp-discourse' ), array(
			$this,
			'enable_sso_checkbox',
		), 'discourse', 'discourse_wp_sso' );
		add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array(
			$this,
			'wordpress_login_path',
		), 'discourse', 'discourse_wp_sso' );
		add_settings_field( 'discourse_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array(
			$this,
			'sso_secret_input',
		), 'discourse', 'discourse_wp_sso' );
		add_settings_field( 'discourse_display_subcategories', __( 'Display subcategories', 'wp-discourse' ), array(
			$this,
			'display_subcategories',
		), 'discourse', 'discourse_wp_publish' );
		add_settings_field( 'discourse_publish_category', __( 'Published category', 'wp-discourse' ), array(
			$this,
			'publish_category_input',
		), 'discourse', 'discourse_wp_publish' );
		add_settings_field( 'discourse_publish_category_update', __( 'Force category update', 'wp-discourse' ), array(
			$this,
			'publish_category_input_update',
		), 'discourse', 'discourse_wp_publish' );
		add_settings_field( 'discourse_full_post_content', __( 'Use full post content', 'wp-discourse' ), array(
			$this,
			'full_post_checkbox',
		), 'discourse', 'discourse_wp_publish' );

		add_settings_field( 'discourse_auto_publish', __( 'Auto Publish', 'wp-discourse' ), array(
			$this,
			'auto_publish_checkbox',
		), 'discourse', 'discourse_wp_publish' );
		add_settings_field( 'discourse_auto_track', __( 'Auto Track Published Topics', 'wp-discourse' ), array(
			$this,
			'auto_track_checkbox',
		), 'discourse', 'discourse_wp_publish' );
		add_settings_field( 'discourse_allowed_post_types', __( 'Post Types to publish to Discourse', 'wp-discourse' ), array(
			$this,
			'post_types_select',
		), 'discourse', 'discourse_wp_publish' );

		add_settings_field( 'discourse_use_discourse_comments', __( 'Use Discourse Comments', 'wp-discourse' ), array(
			$this,
			'use_discourse_comments_checkbox',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_show_existing_comments', __( 'Show Existing WP Comments', 'wp-discourse' ), array(
			$this,
			'show_existing_comments_checkbox',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_existing_comments_heading', __( 'Existing Comments Heading', 'wp-discourse' ), array(
			$this,
			'existing_comments_heading_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_max_comments', __( 'Max visible comments', 'wp-discourse' ), array(
			$this,
			'max_comments_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_min_replies', __( 'Min number of replies', 'wp-discourse' ), array(
			$this,
			'min_replies_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_min_score', __( 'Min score of posts', 'wp-discourse' ), array(
			$this,
			'min_score_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_min_trust_level', __( 'Min trust level', 'wp-discourse' ), array(
			$this,
			'min_trust_level_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_bypass_trust_level_score', __( 'Bypass trust level score', 'wp-discourse' ), array(
			$this,
			'bypass_trust_level_input',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_custom_excerpt_length', __( 'Custom excerpt length', 'wp-discourse' ), array(
			$this,
			'custom_excerpt_length',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_custom_datetime_format', __( 'Custom Datetime Format', 'wp-discourse' ), array(
			$this,
			'custom_datetime_format',
		), 'discourse', 'discourse_comments' );

		add_settings_field( 'discourse_only_show_moderator_liked', __( 'Only import comments liked by a moderator', 'wp-discourse' ), array(
			$this,
			'only_show_moderator_liked_checkbox',
		), 'discourse', 'discourse_comments' );
		add_settings_field( 'discourse_debug_mode', __( 'Debug mode', 'wp-discourse' ), array(
			$this,
			'debug_mode_checkbox',
		), 'discourse', 'discourse_comments' );

		add_settings_field( 'discourse_redirect_without_login', __( 'Redirect Without Login', 'wp-discourse' ), array(
			$this,
			'redirect_without_login_checkbox',
		), 'discourse', 'discourse_wp_sso' );
	}

	/**
	 * Adds Discourse username to the user contact methods.
	 *
	 * @param array $fields Contact information fields available to users.
	 *
	 * @return mixed
	 */
	function extend_user_profile( $fields ) {
		$fields['discourse_username'] = 'Discourse Username';

		return $fields;
	}

	/**
	 * Adds content to the top of the settings section.
	 */
	function init_default_settings() {
	}

	/**
	 * Adds content to the top of the comment section.
	 */
	function init_comment_settings() {
		?>

		<p class="documentation-link">
			<em><?php esc_html_e( 'For documentation on customizing the plugin\'s html, visit ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Template-Customization">https://github.com/discourse/wp-discourse/wiki/Template-Customization</a>
		</p>

		<?php
	}

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	function url_input() {
		self::text_input( 'url', __( 'e.g. http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	function wordpress_login_path() {
		self::text_input( 'login-path', __( '(Optional) The path to your login page. It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	function api_key_input() {
		$discourse_options = $this->options;
		if ( isset( $discourse_options['url'] ) && ! empty( $discourse_options['url'] ) ) {
			self::text_input( 'api-key', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) . '/admin/api" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api</a>' );
		} else {
			self::text_input( 'api-key', __( 'Found at http://discourse.example.com/admin/api', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	function enable_sso_checkbox() {
		self::checkbox_input( 'enable-sso', __( 'Enable SSO to Discourse', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	function sso_secret_input() {
		self::text_input( 'sso-secret', '' );
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	function publish_username_input() {
		self::text_input( 'publish-username', __( 'Discourse username of publisher (will be overriden if Discourse Username is specified on user)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the display-subcategories checkbox.
	 */
	function display_subcategories() {
		self::checkbox_input( 'display-subcategories', __( 'Include subcategories in the list of available categories.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category input.
	 */
	function publish_category_input() {
		self::category_select( 'publish-category', __( 'Default category used to published in Discourse (optional)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category-update input.
	 */
	function publish_category_input_update() {
		self::checkbox_input( 'publish-category-update', __( 'Update the discourse publish category list, (normally set to refresh every hour)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the max-comments input.
	 */
	function max_comments_input() {
		self::text_input( 'max-comments', __( 'Maximum number of comments to display', 'wp-discourse' ), 'number' );
	}

	/**
	 * Outputs markup for the aoto-publish checkbox.
	 */
	function auto_publish_checkbox() {
		self::checkbox_input( 'auto-publish', __( 'Publish all new posts to Discourse', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the auto-track checkbox.
	 */
	function auto_track_checkbox() {
		self::checkbox_input( 'auto-track', __( 'Author automatically tracks published Discourse topics', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the post-types select input.
	 */
	function post_types_select() {
		self::post_type_select_input( 'allowed_post_types',
			$this->post_types_to_publish( array( 'attachment' ) ),
		__( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple options.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the use-discourse-comments checkbox.
	 */
	function use_discourse_comments_checkbox() {
		self::checkbox_input( 'use-discourse-comments', __( 'Use Discourse to comment on Discourse published posts', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the show-existing-comments checkbox.
	 */
	function show_existing_comments_checkbox() {
		self::checkbox_input( 'show-existing-comments', __( 'Display existing WordPress comments beneath Discourse comments', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the existing-comments-heading input.
	 */
	function existing_comments_heading_input() {
		self::text_input( 'existing-comments-heading', __( 'Heading for existing WordPress comments (e.g. "Historical Comment Archive")', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the min-replies input.
	 */
	function min_replies_input() {
		self::text_input( 'min-replies', __( 'Minimum replies required prior to pulling comments across', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-trust-level input.
	 */
	function min_trust_level_input() {
		self::text_input( 'min-trust-level', __( 'Minimum trust level required prior to pulling comments across (0-5)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the min-score input.
	 */
	function min_score_input() {
		self::text_input( 'min-score', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the custom-excerpt-length input.
	 */
	function custom_excerpt_length() {
		self::text_input( 'custom-excerpt-length', __( 'Custom excerpt length in words (default: 55)', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the custom-datetime input.
	 */
	function custom_datetime_format() {
		self::text_input( 'custom-datetime-format', __( 'Custom comment meta datetime string format (default: "', 'wp-discourse' ) .
		                                            get_option( 'date_format' ) . '").' .
		                                            __( ' See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
		__( 'this', 'wp-discourse' ) . '</a>' . __( ' for more info.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the bypass-trust-level input.
	 */
	function bypass_trust_level_input() {
		self::text_input( 'bypass-trust-level-score', __( 'Bypass trust level check on posts with this score', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the debug-mode checkbox.
	 */
	function debug_mode_checkbox() {
		self::checkbox_input( 'debug-mode', __( '(always refresh comments)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the use-full-post checkbox.
	 */
	function full_post_checkbox() {
		self::checkbox_input( 'full-post-content', __( 'Use the full post for content rather than an excerpt.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the only-show-moderator-liked checkbox.
	 */
	function only_show_moderator_liked_checkbox() {
		self::checkbox_input( 'only-show-moderator-liked', __( 'Yes', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	function redirect_without_login_checkbox() {
		self::checkbox_input( 'redirect-without-login', __( 'Do not force login for link to Discourse comments thread (No effect if not using SSO)' ) );
	}

	/**
	 * Outputs the markup for a checkbox input.
	 *
	 * @param string $option The option name.
	 * @param string $label The text for the label.
	 * @param string $description The description of the settings field.
	 */
	function checkbox_input( $option, $label, $description = '' ) {
		$options = $this->options;
		if ( array_key_exists( $option, $options ) and 1 === intval( $options[ $option ] ) ) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<label>
			<input id='discourse_<?php echo esc_attr( $option ); ?>'
			       name='discourse[<?php echo esc_attr( $option ); ?>]' type='checkbox'
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
	 * @param array  $post_types An array of available post types.
	 * @param string $description The description of the settings field.
	 */
	function post_type_select_input( $option, $post_types, $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'strong' => array(),
		);

		echo "<select multiple id='discourse_allowed_post_types' class='discourse-allowed-types' name='discourse[allowed_post_types][]'>";

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
	function category_select( $option, $description ) {
		$options = get_option( 'discourse' );

		$categories = DiscourseUtilities::get_discourse_categories();

		if ( is_wp_error( $categories ) ) {
			esc_html_e( 'The category list will be synced with Discourse when you establish a connection.' , 'wp-discourse' );

			return;
		}

		$selected = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$name     = "discourse[$option]";
		self::option_input( $name, $categories, $selected );
	}

	/**
	 * Outputs the markup for an option input.
	 *
	 * @param string $name Suppies the 'name' value for the select input.
	 * @param array  $group The array of items to be selected.
	 * @param int    $selected The value of the selected option.
	 */
	function option_input( $name, $group, $selected ) {
		echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';

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
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string $option The name of the option.
	 * @param string $description The description of the settings field.
	 * @param null   $type The type of input ('number', 'url', etc).
	 * @param null   $min The min value (applied to number inputs).
	 */
	function text_input( $option, $description, $type = null, $min = null ) {
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
		<input id='discourse_<?php echo esc_attr( $option ); ?>' name='discourse[<?php echo esc_attr( $option ); ?>]'
		       type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
			<?php if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
} ?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
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
		          name='discourse[<?php echo esc_attr( $option ); ?>]'><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php

	}

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
			$filter         = 'validate_' . str_replace( '-', '_', $key );

			if ( ! has_filter( $filter ) ) {
				error_log( 'Missing validation filter: ' . $filter );
			}
			$output[ $key ] = apply_filters( $filter, $input );
		}

		return $output;
	}

	/**
	 * Adds the Discourse options page to the admin menu.
	 *
	 * Hooks into the 'admin_menu' action.
	 */
	function discourse_admin_menu() {
		add_options_page( __( 'Discourse', 'wp-discourse' ), __( 'Discourse', 'wp-discourse' ), 'manage_options', 'discourse', array(
			$this,
			'discourse_options_page',
		) );
	}

	/**
	 * The callback for creating the Discourse options page.
	 */
	function discourse_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-discourse' ) );
		}
		?>
		<div class="wrap">
			<h2>Discourse Options</h2>
			<p class="documentation-link">
				<em><?php esc_html_e( 'The WP Discourse plugin documentation can be found at ', 'wp-discourse' ); ?></em>
				<a href="https://github.com/discourse/wp-discourse/wiki">https://github.com/discourse/wp-discourse/wiki</a>
			</p>
			<form action="options.php" method="POST">
				<?php settings_fields( 'discourse' ); ?>
				<?php do_settings_sections( 'discourse' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
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
}
