<?php
/**
 * Validation methods for the settings page.
 *
 * @link    https://github.com/discourse/wp-discourse/blob/master/lib/settings-validator.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class SettingsValidator
 *
 * @package WPDiscourse\Validator
 */
class SettingsValidator {

	use PluginUtilities;

	/**
	 * Indicates whether or not the "discourse_sso_common['sso-secret']" option has been set.
	 *
	 * @access protected
	 * @var    bool|void
	 */
	protected $sso_secret_set;

	/**
	 * Indicates whether or not the "discourse_sso_provider['enable-sso']" option is enabled.
	 *
	 * @access protected
	 * @var    bool|void
	 */
	protected $sso_provider_enabled;

	/**
	 * Indicates whether or not the "discourse_sso_client['sso-client-enabled']" option is enabled.
	 *
	 * @access protected
	 * @var    bool|void
	 */
	protected $sso_client_enabled;

	/**
	 * Indicates whether or not 'use_discourse_comments' is enabled.
	 *
	 * @access protected
	 * @var    bool
	 */
	protected $use_discourse_comments = false;

	/**
	 * Indicates whether or not 'use_discourse_webhook' is enabled.
	 *
	 * @access protected
	 * @var    bool
	 */
	protected $use_discourse_webhook;

	/**
	 * Indicates whether or not 'use_discourse_user_webhook' is enabled.
	 *
	 * @access protected
	 * @var    bool
	 */
	protected $use_discourse_user_webhook;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var    array|void
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
		add_filter( 'wpdc_validate_allow_tags', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_max_tags', array( $this, 'validate_max_tags' ) );
		add_filter( 'wpdc_validate_full_post_content', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_auto_publish', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_force_publish', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_add_featured_link', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_auto_track', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_allowed_post_types', array( $this, 'validate_allowed_post_types' ) );
		add_filter( 'wpdc_validate_publish_failure_notice', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_publish_failure_email', array( $this, 'validate_email' ) );
		add_filter( 'wpdc_validate_hide_discourse_name_field', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_use_discourse_comments', array( $this, 'validate_use_discourse_comments' ) );
		add_filter( 'wpdc_validate_add_join_link', array( $this, 'validate_add_join_link' ) );
		add_filter( 'wpdc_validate_cache_html', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_clear_cached_comment_html', array( $this, 'validate_clear_comments_html' ) );
		add_filter( 'wpdc_validate_ajax_load', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_load_comment_css', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_discourse_new_tab', array( $this, 'validate_checkbox' ) );
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

		add_filter( 'wpdc_validate_discourse_link_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_start_discussion_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_continue_discussion_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_join_discussion_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_comments_singular_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_comments_plural_text', array( $this, 'validate_text_input' ) );
		add_filter( 'wpdc_validate_no_comments_text', array( $this, 'validate_text_input' ) );
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

		add_filter( 'wpdc_validate_use_discourse_webhook', array( $this, 'validate_use_discourse_webhook' ) );
		add_filter( 'wpdc_validate_webhook_match_old_topics', array( $this, 'validate_webhook_match_old_topics' ) );
		add_filter( 'wpdc_validate_use_discourse_user_webhook', array( $this, 'validate_use_discourse_user_webhook' ) );
		add_filter( 'wpdc_validate_webhook_match_user_email', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_webhook_secret', array( $this, 'validate_webhook_secret' ) );

		add_filter( 'wpdc_validate_sso_client_enabled', array( $this, 'validate_sso_client_enabled' ) );
		add_filter( 'wpdc_validate_sso_client_login_form_change', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_sso_client_login_form_redirect', array( $this, 'validate_sso_client_login_form_redirect' ) );
		add_filter( 'wpdc_validate_sso_client_sync_by_email', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_sso_client_sync_logout', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_enable_sso', array( $this, 'validate_enable_sso' ) );
		add_filter( 'wpdc_validate_auto_create_sso_user', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_sso_secret', array( $this, 'validate_sso_secret' ) );
		add_filter( 'wpdc_validate_login_path', array( $this, 'validate_login_path' ) );
		add_filter( 'wpdc_validate_real_name_as_discourse_name', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_force_avatar_update', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_redirect_without_login', array( $this, 'validate_checkbox' ) );

		add_filter( 'wpdc_validate_site_multisite_configuration_enabled', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_url', array( $this, 'validate_url' ) );
		add_filter( 'wpdc_validate_site_api_key', array( $this, 'validate_api_key' ) );
		add_filter( 'wpdc_validate_site_publish_username', array( $this, 'validate_publish_username' ) );
		add_filter( 'wpdc_validate_site_use_discourse_webhook', array( $this, 'validate_use_discourse_webhook' ) );
		add_filter( 'wpdc_validate_site_webhook_match_old_topics', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_webhook_secret', array( $this, 'validate_webhook_secret' ) );
		add_filter( 'wpdc_validate_site_webhook_match_user_email', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_use_discourse_user_webhook', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_hide_discourse_name_field', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_sso_secret', array( $this, 'validate_sso_secret' ) );
		add_filter( 'wpdc_validate_site_enable_sso', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_site_sso_client_enabled', array( $this, 'validate_checkbox' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();

		$this->sso_provider_enabled = ! empty( $this->options['enable-sso'] );
		$this->sso_client_enabled   = ! empty( $this->options['sso-client-enabled'] );
		$this->sso_secret_set       = ! empty( $this->options['sso-secret'] );
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

			$url = '';
		} else {
			$url = untrailingslashit( esc_url_raw( $input ) );

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				add_settings_error( 'discourse', 'discourse_url', __( 'The Discourse URL you provided is not a valid URL.', 'wp-discourse' ) );

			}
		}

		return $url;
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

			$api_key = '';
		} else {
			$api_key = trim( $input );

			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'discourse', 'api_key', __( 'The API key you provided is not valid.', 'wp-discourse' ) );
			}
		}

		return $api_key;
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
			$publish_username = $this->sanitize_text( $input );
		} else {
			add_settings_error( 'discourse', 'publish_username', __( 'You need to provide a Discourse username.', 'wp-discourse' ) );

			$publish_username = '';
		}

		return $publish_username;
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
	 * Validates the add_join_link checkbox.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_add_join_link( $input ) {
		$new_value = $this->sanitize_checkbox( $input );
		if ( 1 === $new_value && $this->use_discourse_comments ) {
			add_settings_error(
				'discourse', 'add_join_link', __(
					"The 'Add Join Link' option can only be used when the 'Use Discourse Comments' option is not set.
			If you would like to use it, deselect the 'Use Discourse Comments' option.", 'wp-discourse'
				)
			);

			return 0;
		}

		return $new_value;
	}

	/**
	 * Validates the 'clear_cached_comment_html input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_clear_comments_html( $input ) {
		if ( 1 === intval( $input ) ) {
			$this->clear_cached_html();
		}

		return 0;
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
		return $this->validate_int(
			$input, 'max_comments', 0, null,
			__( 'The max visible comments must be set to at least 0.', 'wp-discourse' ),
			$this->use_discourse_comments
		);
	}

	/**
	 * Validates the 'min_replies' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_replies( $input ) {
		return $this->validate_int(
			$input, 'min_replies', 0, null,
			__( 'The min number of replies setting requires a number greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments
		);
	}

	/**
	 * Validates the 'min_score' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_score( $input ) {
		return $this->validate_int(
			$input, 'min_score', 0, null,
			__( 'The min score of posts setting requires a number greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments
		);
	}

	/**
	 * Validates the 'min_trust_level' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_min_trust_level( $input ) {
		return $this->validate_int(
			$input, 'min_trust_level', 0, 5,
			__( 'The trust level setting requires a number between 0 and 5.', 'wp-discourse' ),
			$this->use_discourse_comments
		);
	}

	/**
	 * Validates the 'bypass_trust_level_score' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_bypass_trust_level_score( $input ) {
		return $this->validate_int(
			$input, 'bypass_trust_level', 0, null,
			__( 'The bypass trust level score setting requires an integer greater than or equal to 0.', 'wp-discourse' ),
			$this->use_discourse_comments
		);
	}

	/**
	 * Validates the 'custom_excerpt_length' number input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_custom_excerpt_length( $input ) {

		return $this->validate_int(
			$input, 'excerpt_length', 0, null,
			__( 'The custom excerpt length setting requires a positive integer.', 'wp-discourse' ),
			true
		);
	}

	/**
	 * Validates the 'max_tags' input.
	 *
	 * @param int $input The input to be validated.
	 *
	 * @return mixed
	 */
	public function validate_max_tags( $input ) {
		return $this->validate_int( $input );
	}

	/**
	 * Validates use_discourse_webhook.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return bool|int
	 */
	public function validate_use_discourse_webhook( $input ) {
		$this->use_discourse_webhook = $this->validate_checkbox( $input );

		return $this->use_discourse_webhook;
	}

	/**
	 * Validates user_discourse_user_webhook.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return bool|int
	 */
	public function validate_use_discourse_user_webhook( $input ) {
		$this->use_discourse_user_webhook = $this->validate_checkbox( $input );

		return $this->use_discourse_user_webhook;
	}

	/**
	 * Validates the webhook_secret input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_webhook_secret( $input ) {
		if ( ( $this->use_discourse_webhook || $this->use_discourse_user_webhook ) && strlen( $input ) < 12 ) {
			add_settings_error( 'discourse', 'webhook_secret', __( 'To use a Discourse webhook, the secret must be set to a value at least 12 characters long.', 'wp-discourse' ) );

			return '';
		}

		return $input;
	}

	/**
	 * Validates the webhook_match_old_topics input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_webhook_match_old_topics( $input ) {
		$match_old_topics = $this->validate_checkbox( $input );

		return $match_old_topics;
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
			add_settings_error(
				'discourse', 'sso_client_enabled', __(
					"You have the 'SSO Client' option enabled. Visit the 'SSO Client' settings tab
			to disable it before enabling your site to function as the SSO provider.", 'wp-discourse'
				)
			);

			return 0;
		}

		if ( 1 === $new_value && ! $this->sso_secret_set ) {
			add_settings_error(
				'discourse', 'sso_provider_no_secret', __(
					'Before enabling your site to function as the SSO provider,
            you need to set the SSO Secret Key.', 'wp-discourse'
				)
			);

			return 0;
		}

		// When the SSO Provider option is updated, clear the comment cache to update links to Discourse.
		if ( ! empty( $this->options['cache-html'] ) ) {
			$this->clear_cached_html();
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
			add_settings_error(
				'discourse', 'sso_provider_enabled', __(
					"You have the 'SSO Provider' option enabled. Click on the 'SSO Provider' settings tab
			to disable it before enabling your site to function as an SSO client.", 'wp-discourse'
				)
			);

			return 0;
		}

		if ( 1 === $new_value && ! $this->sso_secret_set ) {
			add_settings_error(
				'discourse', 'sso_client_no_secret', __(
					'Before enabling your site to function as an SSO client,
            you need to set the SSO Secret Key.', 'wp-discourse'
				)
			);

			return 0;
		}

		return $this->sanitize_checkbox( $input );
	}

	/**
	 * Validates the sso_client_login_form_redirect redirect text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_sso_client_login_form_redirect( $input ) {
		if ( empty( $input ) ) {

			return '';
		}
		$regex = '/^(http:|https:)/';

		// Make sure the url starts with a valid protocol.
		if ( ! preg_match( $regex, $input ) ) {
			add_settings_error( 'discourse', 'sso_client_login_redirect', __( 'The redirect URL needs to be set to a valid URL that begins with either \'http:\' or \'https:\'.', 'wp-discourse' ) );

			$url = '';
		} else {
			$url = untrailingslashit( esc_url_raw( $input ) );

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				add_settings_error( 'discourse', 'sso_client_login_redirect', __( 'The redirect URL you provided is not a valid URL.', 'wp-discourse' ) );
			}
		}

		return $url;
	}

	/**
	 * Validates the 'sso_secret' text input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_sso_secret( $input ) {
		if ( strlen( $input ) < 10 ) {
			add_settings_error( 'discourse', 'sso_secret', __( 'The SSO secret key must be at least 10 characters long.', 'wp-discourse' ) );

			return '';
		}

		return $input;
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

	/**
	 * Validates the 'auto-create-welcome-redirect' field.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
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
	 * Validates an email input.
	 *
	 * @param string $input The input to be validated.
	 *
	 * @return string
	 */
	public function validate_email( $input ) {
		if ( ! empty( $input ) ) {

			return sanitize_email( $input );
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
	 * @param int    $input         The input to be validated.
	 * @param string $option_id     The option being validated.
	 * @param null   $min           The minimum allowed value.
	 * @param null   $max           The maximum allowed value.
	 * @param string $error_message The error message to return.
	 * @param bool   $add_error     Whether or not to add a setting error.
	 *
	 * @return mixed
	 */
	protected function validate_int( $input, $option_id = null, $min = null, $max = null, $error_message = '', $add_error = false ) {
		$options = array();

		if ( isset( $min ) ) {
			$options['min_range'] = $min;
		}
		if ( isset( $max ) ) {
			$options['max_range'] = $max;
		}

		$input = filter_var(
			$input, FILTER_VALIDATE_INT, array(
				'options' => $options,
			)
		);

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

	/**
	 * Clears all cached comment HTML.
	 */
	protected function clear_cached_html() {
		$transient_keys = get_option( 'wpdc_cached_html_keys' );
		if ( ! empty( $transient_keys ) ) {
			foreach ( $transient_keys as $transient_key ) {
				delete_transient( $transient_key );
			}
		}
	}
}
