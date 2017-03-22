<?php
/**
 * Validation methods for the settings page.
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/settings-validator.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class SettingsValidator
 *
 * @package WPDiscourse\Validator
 */
class SettingsValidator {

	/**
	 * Indicates whether or not the "discourse_sso_common['sso-secret']" option has been set.
	 * @access protected
	 * @var bool|void
	 */
	protected $sso_secret_set;

	/**
	 * Indicates whether or not the "discourse_sso_provider['enable-sso']" option is enabled.
	 *
	 * @access protected
	 * @var bool|void
	 */
	protected $sso_provider_enabled;

	/**
	 * Indicates whether or not the "discourse_sso_client['sso-client-enabled']" option is enabled.
	 * @access protected
	 * @var bool|void
	 */
	protected $sso_client_enabled;

	/**
	 * Indicates whether or not 'use_discourse_comments' is enabled.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $use_discourse_comments = false;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var array|void
	 */
	protected $options;

	/**
	 * SettingsValidator constructor.
	 *
	 * Adds the callback function for each of the validator filters that are applied
	 * in `admin.php`.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );

		add_filter( 'wpdc_validate_url', array( $this, 'validate_url' ) );
		add_filter( 'wpdc_validate_api_key', array( $this, 'validate_api_key' ) );
		add_filter( 'wpdc_validate_publish_username', array( $this, 'validate_publish_username' ) );
		add_filter( 'wpdc_validate_publish_category', array( $this, 'validate_publish_category' ) );
		add_filter( 'wpdc_validate_publish_category_update', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_full_post_content', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_auto_publish', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_auto_track', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_allowed_post_types', array( $this, 'validate_allowed_post_types' ) );
		add_filter( 'wpdc_validate_use_discourse_comments', array( $this, 'validate_use_discourse_comments' ) );
		add_filter( 'wpdc_validate_show_existing_comments', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_existing_comments_heading', array( $this, 'validate_existing_comments_heading' ) );
		add_filter( 'wpdc_validate_max_comments', array( $this, 'validate_max_comments' ) );
		add_filter( 'wpdc_validate_min_replies', array( $this, 'validate_min_replies' ) );
		add_filter( 'wpdc_validate_min_score', array( $this, 'validate_min_score' ) );
		add_filter( 'wpdc_validate_min_trust_level', array( $this, 'validate_min_trust_level' ) );
		add_filter( 'wpdc_validate_bypass_trust_level_score', array( $this, 'validate_bypass_trust_level_score' ) );
		add_filter( 'wpdc_validate_custom_excerpt_length', array( $this, 'validate_custom_excerpt_length' ) );
		add_filter( 'wpdc_validate_custom_datetime_format', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_only_show_moderator_liked', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_display_subcategories', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_debug_mode', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_discourse_link_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_start_discussion_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_continue_discussion_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_notable_replies_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_comments_not_available_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_participants_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_published_at_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_single_reply_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_many_replies_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_more_replies_more_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_external_login_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_link_to_discourse_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_linked_to_discourse_text', array( $this, 'validate_text_input' ) );

		add_filter( 'wpdc_validate_sso_client_enabled', array( $this, 'validate_sso_client_enabled' ) );
		add_filter( 'wpdc_validate_sso_client_login_form_change', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_sso_client_sync_by_email', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_enable_sso', array( $this, 'validate_enable_sso' ) );
		add_filter( 'wpdc_validate_auto_create_sso_user', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_auto_create_login_redirect', array( $this, 'validate_auto_create_login_redirect' ) );
		add_filter( 'wpdc_validate_auto_create_welcome_redirect', array(
			$this,
			'validate_auto_create_welcome_redirect'
		) );
		add_filter( 'wpdc_validate_sso_secret', array( $this, 'validate_sso_secret' ) );
		add_filter( 'wpdc_validate_login_path', array( $this, 'validate_login_path' ) );
		add_filter( 'wpdc_validate_redirect_without_login', array( $this, 'validate_checkbox' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();

		$this->sso_provider_enabled = ! empty( $this->options['enable-sso'] ) && 1 === intval( $this->options['enable-sso'] ) ? true : false;
		$this->sso_client_enabled   = ! empty( $this->options['sso-client-enabled'] ) && 1 === intval( $this->options['sso-client-enabled'] ) ? true : false;
		$this->sso_secret_set       = ! empty( $this->options['sso-secret'] ) ? true : false;
	}

	/**
	 * Validates the Discourse URL.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_url( $input ) {
		$regex = '/^(http:|https:)/';

		// Make sure the url starts with a valid protocol.
		if ( ! preg_match( $regex, $input ) ) {
			add_settings_error( 'discourse', 'discourse_url', __( 'The Discourse URL needs to be set to a valid URL that begins with either \'http:\' or \'https:\'.', 'wp-discourse' ) );

			return '';
		}

		if ( filter_var( $input, FILTER_VALIDATE_URL ) ) {
			return untrailingslashit( esc_url_raw( $input ) );
		} else {
			add_settings_error( 'discourse', 'discourse_url', __( 'The Discourse URL you provided is not a valid URL.', 'wp-discourse' ) );

			return untrailingslashit( esc_url_raw( $input ) );
		}
	}

	/**
	 * Validates the api key.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_api_key( $input ) {
		$regex = '/^\s*([0-9]*[a-z]*|[a-z]*[0-9]*)*\s*$/';

		if ( empty( $input ) ) {
			add_settings_error( 'discourse', 'api_key', __( 'You must provide an API key.', 'wp-discourse' ) );

			return '';

		} elseif ( preg_match( $regex, $input ) ) {
			return trim( $input );

		} else {
			add_settings_error( 'discourse', 'api_key', __( 'The API key you provided is not valid.', 'wp-discourse' ) );

			return $this->sanitize_text( $input );
		}
	}

	/**
	 * Validates the publish_username.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_publish_username( $input ) {
		if ( ! empty( $input ) ) {
			return $this->sanitize_text( $input );
		} else {
			add_settings_error( 'discourse', 'publish_username', __( 'You need to provide a Discourse username.', 'wp-discourse' ) );

			return '';
		}
	}

	/**
	 * Validates the 'publish_category' select input.
	 *
	 * Returns the category id.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_publish_category( $input ) {
		return $this->sanitize_int( $input );
	}

	/**
	 * Validates the 'allowed_post_types' multi-select.
	 *
	 * @param array $input The array of allowed post-types.
	 *
	 * @return array
	 */
	public function validate_allowed_post_types( $input ) {
		$output = array();
		foreach ( $input as $post_type ) {
			$output[] = sanitize_text_field( $post_type );
		}

		return $output;
	}

	/**
	 * Validates the 'use_discourse_comments' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_use_discourse_comments( $input ) {
		$new_value                    = $this->sanitize_checkbox( $input );
		$this->use_discourse_comments = 1 === $new_value ? true : false;

		return $new_value;
	}

	/**
	 * Validates the 'existing_comments_heading' input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_existing_comments_heading( $input ) {
		return $this->sanitize_html( $input );
	}

	/**
	 * Validates the 'max_comments' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_max_comments( $input ) {
		return $this->validate_int( $input, 'max_comments', 0, null,
			__( 'The max visible comments must be set to at least 0.', 'wp-discourse' ),
			$this->use_discourse_comments );
	}

	/**
	 * Validates the 'min_replies' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_replies( $input ) {
		return $this->validate_int( $input, 'min_replies', 0, null,
			__( 'The min number of replies setting requires a number greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments );
	}

	/**
	 * Validates the 'min_score' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_score( $input ) {
		return $this->validate_int( $input, 'min_score', 0, null,
			__( 'The min score of posts setting requires a number greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments );
	}

	/**
	 * Validates the 'min_trust_level' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_trust_level( $input ) {
		return $this->validate_int( $input, 'min_trust_level', 0, 5,
			__( 'The trust level setting requires a number between 0 and 5.', 'wp-discourse' ),
			$this->use_discourse_comments );
	}

	/**
	 * Validates the 'bypass_trust_level_score' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_bypass_trust_level_score( $input ) {
		return $this->validate_int( $input, 'bypass_trust_level', 0, null,
			__( 'The bypass trust level score setting requires an integer greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments );
	}

	/**
	 * Validates the 'custom_excerpt_length' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_custom_excerpt_length( $input ) {

		return $this->validate_int( $input, 'excerpt_length', 0, null,
			__( 'The custom excerpt length setting requires a positive integer.', 'wp-discourse' ),
			true );
	}

	/**
	 * Validates the 'enable_sso'checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_enable_sso( $input ) {
		$new_value = $this->sanitize_checkbox( $input );

		if ( 1 === $new_value && $this->sso_client_enabled ) {
			add_settings_error( 'discourse', 'sso_client_enabled', __( "You have the 'SSO Client' option enabled. Visit the 'SSO Client' settings tab
			to disable it before enabling your site to function as the SSO provider.", 'wp-discourse' ) );

			return 0;
		}

		if ( 1 === $new_value && ! $this->sso_secret_set ) {
			add_settings_error( 'discourse', 'sso_provider_no_secret', __( "Before enabling your site to function as the SSO provider,
			you need to set the SSO Secret Key.", 'wp-discourse' ) );

			return 0;
		}

		return $new_value;
	}

	/**
	 * Validates the 'sso_client_enabled' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_sso_client_enabled( $input ) {
		$new_value = $this->sanitize_checkbox( $input );

		if ( 1 === $new_value && $this->sso_provider_enabled ) {
			add_settings_error( 'discourse', 'sso_provider_enabled', __( "You have the 'SSO Provider' option enabled. Click on the 'SSO Provider' settings tab
			to disable it before enabling your site to function as an SSO client.", 'wp-discourse' ) );

			return 0;
		}

		if ( 1 === $new_value && ! $this->sso_secret_set ) {
			add_settings_error( 'discourse', 'sso_client_no_secret', __( "Before enabling your site to function as an SSO client,
			you need to set the SSO Secret Key.", 'wp-discourse' ) );

			return 0;
		}

		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'sso_secret' text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_sso_secret( $input ) {

		return sanitize_text_field( $input );
	}

	/**
	 * Validates the 'login_path' text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_login_path( $input ) {
		if ( $this->sso_provider_enabled && $input ) {

			$regex = '/^\//';
			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'discourse', 'login_path', __( 'The path to login page setting needs to be a valid file path, starting with \'/\'.', 'wp-discourse' ) );
			}

			// It's valid.
			return $this->sanitize_text( $input );
		}

		// Sanitize, but don't validate. SSO is not enabled.
		return $this->sanitize_text( $input );
	}

	/**
	 * Validates the 'auto-create-login-redirect' field.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_auto_create_login_redirect( $input ) {
		if ( $this->sso_provider_enabled && $input ) {

			$regex = '/^\//';
			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'discourse', 'auto_create_login_redirect', __( 'The path to the login redirect page setting needs to be a valid file path, starting with \'/\'.', 'wp-discourse' ) );
			}

			// It's valid.
			return $this->sanitize_text( $input );
		}

		// Sanitize, but don't validate. SSO is not enabled.
		return $this->sanitize_text( $input );
	}

	public function validate_auto_create_welcome_redirect( $input ) {
		if ( $this->sso_provider_enabled && $input ) {

			$regex = '/^\//';
			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'discourse', 'auto_create_welcome_redirect', __( 'The path to the welcome page setting needs to be a valid file path, starting with \'/\'.', 'wp-discourse' ) );
			}

			// It's valid.
			return $this->sanitize_text( $input );
		}

		// Sanitize, but don't validate. SSO is not enabled.
		return $this->sanitize_text( $input );
	}

	/**
	 * Validate a checkbox input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_checkbox( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validate a text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_text_input( $input ) {
		if ( ! empty( $input ) ) {
			return $this->sanitize_text( $input );
		} else {
			return '';
		}
	}

	/**
	 * Helper methods
	 ******************************/

	/**
	 * A helper method to sanitize text inputs.
	 *
	 * @param string $input The input to be sanitized.
	 *
	 * @return string
	 */
	protected function sanitize_text( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * A helper method to sanitize the value returned from checkbox inputs.
	 *
	 * @param string $input The value returned from the checkbox.
	 *
	 * @return int
	 */
	protected function sanitize_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	/**
	 * A helper function to sanitize HTML.
	 *
	 * @param string $input HTML input to be sanitized.
	 *
	 * @return string
	 */
	protected function sanitize_html( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * A helper function to sanitize an int.
	 *
	 * @param mixed|int $input The input to be validated.
	 *
	 * @return int
	 */
	protected function sanitize_int( $input ) {
		return intval( $input );
	}

	/**
	 * A helper function to validate and sanitize integers.
	 *
	 * @param int $input The input to be validated.
	 * @param string $option_id The option being validated.
	 * @param null $min The minimum allowed value.
	 * @param null $max The maximum allowed value.
	 * @param string $error_message The error message to return.
	 * @param bool $add_error Whether or not to add a setting error.
	 *
	 * @return mixed
	 */
	protected function validate_int( $input, $option_id, $min = null, $max = null, $error_message = '', $add_error = false ) {
		$options = array();

		if ( isset( $min ) ) {
			$options['min_range'] = $min;
		}
		if ( isset( $max ) ) {
			$options['max_range'] = $max;
		}

		$input = filter_var( $input, FILTER_VALIDATE_INT, array(
			'options' => $options,
		) );

		if ( false === $input ) {
			if ( $add_error ) {
				add_settings_error( 'discourse', $option_id, $error_message );
			}

			// The input is not valid, but the setting's section is not being used, sanitize the input and return it.
			return null;
		} else {
			// Valid input.
			return $input;
		}
	}
}
