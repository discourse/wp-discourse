<?php
/**
 * Validation methods for the settings page.
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/settings-validator.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Validator;

/**
 * Class SettingsValidator
 *
 * @package WPDiscourse\Validator
 */
class SettingsValidator {

	/**
	 * Indicates whether or not SSO is enabled.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $sso_enabled = false;

	/**
	 * Indicates whether or not 'use_discourse_comments' is enabled.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $use_discourse_comments = false;

	/**
	 * SettingsValidator constructor.
	 *
	 * Adds the callback function for each of the validator filters that are applied
	 * in `admin.php`.
	 */
	public function __construct() {
		add_filter( 'validate_url', array( $this, 'validate_url' ) );
		add_filter( 'validate_api_key', array( $this, 'validate_api_key' ) );
		add_filter( 'validate_publish_username', array(
			$this,
			'validate_publish_username',
		) );
		add_filter( 'validate_publish_category', array(
			$this,
			'validate_publish_category',
		) );
		add_filter( 'validate_publish_category_update', array(
			$this,
			'validate_publish_category_update',
		) );
		add_filter( 'validate_full_post_content', array(
			$this,
			'validate_full_post_content',
		) );
		add_filter( 'validate_auto_publish', array(
			$this,
			'validate_auto_publish',
		) );
		add_filter( 'validate_auto_track', array( $this, 'validate_auto_track' ) );
		add_filter( 'validate_allowed_post_types', array(
			$this,
			'validate_allowed_post_types',
		) );
		add_filter( 'validate_use_discourse_comments', array(
			$this,
			'validate_use_discourse_comments',
		) );
		add_filter( 'validate_show_existing_comments', array(
			$this,
			'validate_show_existing_comments',
		) );
		add_filter( 'validate_existing_comments_heading', array(
			$this,
			'validate_existing_comments_heading',
		) );
		add_filter( 'validate_max_comments', array(
			$this,
			'validate_max_comments',
		) );
		add_filter( 'validate_min_replies', array(
			$this,
			'validate_min_replies',
		) );
		add_filter( 'validate_min_score', array( $this, 'validate_min_score' ) );
		add_filter( 'validate_min_trust_level', array(
			$this,
			'validate_min_trust_level',
		) );
		add_filter( 'validate_bypass_trust_level_score', array(
			$this,
			'validate_bypass_trust_level_score',
		) );
		add_filter( 'validate_custom_excerpt_length', array(
			$this,
			'validate_custom_excerpt_length',
		) );
		add_filter( 'validate_custom_datetime_format', array(
			$this,
			'validate_custom_datetime_format',
		) );
		add_filter( 'validate_only_show_moderator_liked', array(
			$this,
			'validate_only_show_moderator_liked',
		) );
		add_filter( 'validate_display_subcategories', array(
			$this,
			'validate_display_subcategories',
		) );
		add_filter( 'validate_debug_mode', array( $this, 'validate_debug_mode' ) );
		add_filter( 'validate_enable_sso', array( $this, 'validate_enable_sso' ) );
		add_filter( 'validate_sso_secret', array( $this, 'validate_sso_secret' ) );
		add_filter( 'validate_login_path', array( $this, 'validate_login_path' ) );
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
			add_settings_error( 'discourse', 'discourse_url', __( 'The Discourse URL needs to begin with either \'http:\' or \'https:\'.' ) );
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
			add_settings_error( 'discourse', 'publish_username', __( 'You must provide a Discourse username with which to publish the posts', 'wp-discourse' ) );

			return '';
		}
	}

	/**
	 * Validated the 'display_subcategories' checkbox.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_display_subcategories( $input ) {
		return $this->sanitize_checkbox( $input );
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
	 * Validates the 'publish_category_update' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_publish_category_update( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'full_post_content' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_full_post_content( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'auto_publish' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_auto_publish( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'auto_track' checkbox.
	 *
	 * @param string $input The input to be validates.
	 *
	 * @return int
	 */
	public function validate_auto_track( $input ) {
		return $this->sanitize_checkbox( $input );
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
	 * If this function is called, it sets the 'use_discourse_comments' property to true. This makes it possible
	 * to only show warnings for the comment settings if Discourse is being used for comments.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_use_discourse_comments( $input ) {
		$this->use_discourse_comments = true;

		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'show_existing_comments' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_show_existing_comments( $input ) {
		return $this->sanitize_checkbox( $input );
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
		return $this->validate_int( $input, 'max_comments', 1, null,
			__( 'The max visible comments setting requires a positive integer.', 'wp-discourse' ),
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
		return $this->validate_int( $input, 'excerpt_length', 1, null,
			__( 'The custom excerpt length setting requires a positive integer.', 'wp-discourse' ),
		$this->use_discourse_comments );
	}

	/**
	 * Validates the 'custom_date_time' text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_custom_datetime_format( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Validates the 'only_show_moderator_liked' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_only_show_moderator_liked( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the 'debug_mode' checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_debug_mode( $input ) {
		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validated the 'enable_sso'checkbox.
	 *
	 * This function is only called if the checkbox is checked. It sets the `sso_enabled` property to true.
	 * This allows sso validation notices to only be displayed if sso is enabled.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_enable_sso( $input ) {
		$this->sso_enabled = true;

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
		if ( strlen( sanitize_text_field( $input ) ) >= 10 ) {
			return sanitize_text_field( $input );

			// Only add a settings error if sso is enabled, otherwise just sanitize the input.
		} elseif ( $this->sso_enabled ) {
			add_settings_error( 'discourse', 'sso_secret', __( 'The SSO secret key setting must be at least 10 characters long.', 'wp-discourse' ) );

			return sanitize_text_field( $input );

		} else {
			return sanitize_text_field( $input );
		}
	}

	/**
	 * Validates the 'login_path' text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_login_path( $input ) {
		if ( $this->sso_enabled && $input ) {

			$regex = '/^\/([a-z0-9\-]+)*(\/[a-z0-9\-]+)*(\/)?$/';
			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'discourse', 'login_path', __( 'The path to login page setting needs to be a valid file path, starting with \'/\'.', 'wp-discourse' ) );

				return $this->sanitize_text( $input );

			}

			// It's valid.
			return $this->sanitize_text( $input );
		}

		// Sanitize, but don't validate. SSO is not enabled.
		return $this->sanitize_text( $input );
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
	 * @param int    $input The input to be validated.
	 * @param string $option_id The option being validated.
	 * @param null   $min The minimum allowed value.
	 * @param null   $max The maximum allowed value.
	 * @param string $error_message The error message to return.
	 * @param bool   $add_error Whether or not to add a setting error.
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

		if ( filter_var( $input, FILTER_VALIDATE_INT, array( 'options' => $options ) ) === false ) {
			if ( $add_error ) {
				add_settings_error( 'discourse', $option_id, $error_message );

				return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
			}

			// The input is not valid, but the setting's section is not being used, sanitize the input and return it.
			return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
		} else {
			// Valid input.
			return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
		}
	}
}
