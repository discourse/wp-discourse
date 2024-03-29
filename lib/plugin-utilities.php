<?php
/**
 * Shared Utility methods.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Shared;

use WPDiscourse\Logs\Logger;

/**
 * Trait PluginUtilities
 */
trait PluginUtilities {

	/**
	 * Gets the 'discourse_configurable_text' options.
	 *
	 * @param string $option The option key.
	 *
	 * @return string
	 */
	protected static function get_text_options( $option ) {
		$text_options = get_option( 'discourse_configurable_text' );
		$text         = ! empty( $text_options[ $option ] ) ? $text_options[ $option ] : '';
		return self::apply_text_translations( $text, $option );
	}

	/**
	 * Applies translations of configurable text.
	 *
	 * @param string $text The text to be translated.
	 * @param string $option The text option to be translated.
	 *
	 * @return string
	 */
	protected static function apply_text_translations( $text, $option ) {
		// See https://wpml.org/wpml-hook/wpml_translate_single_string/.
		$text = apply_filters( 'wpml_translate_single_string', $text, 'wp-discourse', $option );

		return $text;
	}

	/**
	 * Registers translations of configurable text.
	 *
	 * @param string $text The text to be translated.
	 * @param string $option The text option to be translated.
	 *
	 * @return void
	 */
	protected static function register_text_translations( $text, $option ) {
		// See https://wpml.org/wpml-hook/wpml_register_single_string/.
		do_action( 'wpml_register_single_string', 'wp-discourse', $option, $text );
	}

	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * @return array
	 */
	protected function get_options() {
		$options = array();

		$discourse_option_groups = get_option( 'discourse_option_groups' );
		if ( $discourse_option_groups ) {
			foreach ( $discourse_option_groups as $option_name ) {
				if ( get_option( $option_name ) ) {
					$option  = get_option( $option_name );
					$options = array_merge( $options, $option );
				}
			}

			$multisite_configuration_enabled = get_site_option( 'wpdc_multisite_configuration' );
			if ( 1 === intval( $multisite_configuration_enabled ) ) {
				$site_options = get_site_option( 'wpdc_site_options' );
				foreach ( $site_options as $key => $value ) {
					$options[ $key ] = $value;
				}
			}
		}

		return apply_filters( 'wpdc_utilities_options_array', $options );
	}

	/**
	 * Checks the connection status to Discourse.
	 *
	 * @return int|\WP_Error
	 */
	public function check_connection_status() {
		$options         = isset( $this->options ) ? $this->options : $this->get_options();
		$logger          = Logger::create( 'connection', $options );
		$api_credentials = $this->get_api_credentials();

		if ( is_wp_error( $api_credentials ) ) {
			if ( ! empty( $options['connection-logs'] ) ) {
				$logger->info( 'check_connection_status.invalid_api_credentials' );
			}
			return false;
		}

		// discourse >= 2.9.0.beta5.
		$path   = '/session/scopes.json';
		$scopes = true;
		$body   = $this->discourse_request( $path );

		// discourse < 2.9.0.beta5.
		if ( is_wp_error( $body ) ) {
			$error_data = $body->get_error_data();

			if ( 404 === $error_data['http_code'] ) {
				$scopes = false;
				$path   = "/users/{$api_credentials['api_username']}.json";
				$body   = $this->discourse_request( $path );
			}
		}

		if ( ! empty( $options['connection-logs'] ) ) {
			$log_args = array();

			if ( is_wp_error( $body ) ) {
				$log_type            = 'failed_to_connect';
				$log_args['error']   = $body->get_error_code();
				$log_args['message'] = $body->get_error_message();

				$error_data = $body->get_error_data();
				if ( isset( $error_data['http_code'] ) ) {
					$log_args['http_code'] = $error_data['http_code'];
				}
				if ( isset( $error_data['http_body'] ) ) {
					$log_args['http_body'] = $error_data['http_body'];
				}
			} else {
				$log_type = 'successful_connection';
			}

			$logger->info( "check_connection_status.$log_type", $log_args );
		}

		if ( is_wp_error( $body ) ) {
			return false;
		}

		// discourse < 2.9.0.beta5.
		if ( ! $scopes ) {
			return true;
		}

		$scope_validation = $this->validate_scopes( $body->scopes );

		if ( ! empty( $options['connection-logs'] ) ) {
			$log_args = array();

			if ( $scope_validation->success ) {
				$log_type = 'valid_scopes';
			} else {
				$log_type = 'invalid_scopes';
			}

			if ( ! empty( $scope_validation->errors ) ) {
				$log_args['message'] = implode( ', ', $scope_validation->errors );
			}

			$logger->info( "check_connection_status.$log_type", $log_args );
		}

		return $scope_validation->success;
	}

	/**
	 * Validates the response from `wp_remote_request`.
	 *
	 * @param array $response The response from `wp_remote_request`.
	 *
	 * @return int
	 */
	protected function validate( $response ) {
		if ( empty( $response ) ) {

			return 0;
		} elseif ( is_wp_error( $response ) ) {

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}

	/**
	 * Validates that the api key has sufficient scopes based on the current settings.
	 *
	 * @param array $scopes The scopes.
	 *
	 * @return int
	 */
	protected function validate_scopes( $scopes ) {
		$result = (object) array(
			'success' => false,
			'errors'  => array(),
		);

		// If scopes are empty the key is global.
		if ( empty( $scopes ) ) {
			$result->success = true;
			return $result;
		}

		$wordpress_scopes = array_filter(
             $scopes, function( $scope ) {
			return 'wordpress' === $scope->resource; // phpcs:ignore WordPress.WP.CapitalPDangit
		}
            );

		if ( empty( $wordpress_scopes ) ) {
			$result->errors[] = 'API Key has no WordPress scopes';
			return $result;
		}

		$scoped_actions    = array_column( $wordpress_scopes, 'key' );
		$feature_groups    = $this->enabled_feature_groups();
		$unscoped_features = array_diff( $feature_groups, $scoped_actions );

		if ( empty( $unscoped_features ) ) {
			$result->success = true;
			return $result;
		} else {
			foreach ( $unscoped_features as $usf ) {
				$result->errors[] = "API Key is missing WordPress $usf scope";
			}
			return $result;
		}
	}

	/**
	 * Lists the enabled feature groups.
	 *
	 * @return array
	 */
	protected function enabled_feature_groups() {
		// TODO: add 'enabled' setting for publishing features.
		$groups = array( 'publishing' );

		if ( ! empty( $this->options['enable-discourse-comments'] ) ) {
			$groups[] = 'commenting';
		}

		if ( ! empty( $this->options['enable-sso'] ) || ! empty( $this->options['sso-client-enabled'] ) ) {
			$groups[] = 'discourse_connect';
		}

		return $groups;
	}

	/**
	 * Gets the Discourse categories.
	 *
	 * @return array|\WP_Error
	 */
	protected function get_discourse_categories() {
		$options    = isset( $this->options ) ? $this->options : $this->get_options();
		$categories = get_transient( 'wpdc_discourse_categories' );

		if ( ! empty( $options['publish-category-update'] ) || ! $categories ) {

			$body = $this->discourse_request( '/site.json' );
			if ( is_wp_error( $body ) ) {
				return $body;
			}

			if ( $body->categories ) {
				$categories           = $body->categories;
				$discourse_categories = array();
				foreach ( $categories as $category ) {
					if ( ( empty( $options['display-subcategories'] ) ) && property_exists( $category, 'parent_category_id' ) ) {

						continue;
					}
					$current_category                     = array();
					$current_category['id']               = intval( $category->id );
					$current_category['name']             = sanitize_text_field( $category->name );
					$current_category['name']             = sanitize_text_field( $category->name );
					$current_category['color']            = sanitize_key( $category->color );
					$current_category['text_color']       = sanitize_key( $category->text_color );
					$current_category['slug']             = sanitize_text_field( $category->slug );
					$current_category['topic_count']      = intval( $category->topic_count );
					$current_category['post_count']       = intval( $category->post_count );
					$current_category['description_text'] = sanitize_text_field( $category->description_text );
					$current_category['read_restricted']  = intval( $category->read_restricted );

					$discourse_categories[] = $current_category;
				}

				// Note that setting the cache to 0 will disable transient expiration.
				$category_cache_period = apply_filters( 'wpdc_category_cache_minutes', 10 );

				set_transient( 'wpdc_discourse_categories', $discourse_categories, intval( $category_cache_period ) * MINUTE_IN_SECONDS );

				return $discourse_categories;
			} else {

				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}// End if().

		return $categories;
	}

	/**
	 * Returns a category from the wpdc_discourse_categories array if it is available.
	 *
	 * @param int $category_id The id of the post's Discourse category.
	 *
	 * @return mixed|\WP_Error|null
	 */
	protected function get_discourse_category_by_id( $category_id ) {
		$category_result = $this->get_discourse_categories();
		if ( ! is_wp_error( $category_result ) ) {
			foreach ( $category_result as $category ) {
				if ( intval( $category_id ) === $category['id'] ) {

					return $category;
				}
			}

			return null;
		} else {
			return $category_result;
		}
	}

	/**
	 * Get a Discourse user object.
	 *
	 * @param int  $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function get_discourse_user( $user_id, $match_by_email = false ) {
		$body = $this->discourse_request( "/users/by-external/{$user_id}.json" );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( isset( $body->user ) ) {

			return $body->user;
		}

		if ( $match_by_email ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {

				return $this->get_discourse_user_by_email( $user->user_email );
			} else {

				return new \WP_Error( 'wpdc_param_error', 'There is no WordPress user with the supplied id.' );
			}
		}

		return new \WP_Error( 'wpdc_response_error', 'The Discourse user could not be retrieved.' );
	}

	/**
	 * Gets a Discourse user by their email address.
	 *
	 * @param string $email The email address to search for.
	 *
	 * @return object \WP_Error
	 */
	protected function get_discourse_user_by_email( $email ) {
		$path = '/admin/users/list/all.json';
		$path = add_query_arg(
			array(
				'email'  => rawurlencode_deep( $email ),
				'filter' => rawurlencode_deep( $email ),
			),
			$path
		);

		$body = $this->discourse_request( $path );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( ! empty( $body ) && ! empty( $body[0] ) ) {
			return $body[0];
		} else {
			return new \WP_Error( 'wpdc_response_error', 'The user could not be retrieved by their email address.' );
		}
	}

	/**
	 * Gets a Discourse topic's json from its URL.
	 *
	 * @param string $topic_url The Discourse topic URL.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function get_discourse_topic( $topic_url ) {
		return $this->discourse_request( "{$topic_url}.json" );
	}

	/**
	 * Syncs a user with Discourse through SSO.
	 *
	 * @param array $sso_params The sso params to sync.
	 * @param int   $user_id The WordPress user's ID.
	 *
	 * @return int|string|\WP_Error
	 */
	protected function sync_sso( $sso_params, $user_id = null ) {
		$options = isset( $this->options ) ? $this->options : $this->get_options();
		if ( empty( $options['enable-sso'] ) ) {
			return new \WP_Error( 'sso_error', 'The sync_sso_record function can only be used when DiscourseConnect is enabled.' );
		}

		$path          = '/admin/users/sync_sso';
		$sso_secret    = $options['sso-secret'];
		$sso_payload   = base64_encode( http_build_query( $sso_params ) );
		$sig           = hash_hmac( 'sha256', $sso_payload, $sso_secret );
		$body          = array(
			'sso' => $sso_payload,
			'sig' => $sig,
		);
		$response_body = $this->discourse_request(
			$path,
			array(
				'method' => 'POST',
				'body'   => $body,
			)
		);

		if ( is_wp_error( $response_body ) ) {
			return $response_body;
		}

		$discourse_user = $response_body;

		if ( ! empty( $discourse_user->id ) ) {
			$wordpress_user_id = intval( $sso_params['external_id'] );
			update_user_meta( $wordpress_user_id, 'discourse_sso_user_id', intval( $discourse_user->id ) );
			update_user_meta( $wordpress_user_id, 'discourse_username', sanitize_text_field( $discourse_user->username ) );

			do_action( 'wpdc_after_sync_sso', $discourse_user, $user_id );
		}

		return true;
	}

	/**
	 * Perform a Discourse request
	 *
	 * @param string $url Request url.
	 * @param array  $args Request arguments.
	 *
	 * @return array|\WP_Error|void
	 */
	protected function discourse_request( $url, $args = array() ) {
		if ( ! $url ) {
			return new \WP_Error( 'discourse_configuration_error', 'No discourse url provided to request.' );
		}

		$api_credentials = $this->get_api_credentials();

		if ( is_wp_error( $api_credentials ) ) {
			return $api_credentials;
		}

		$api_username = $api_credentials['api_username'];
		if ( ! empty( $args['api_username'] ) ) {
			$api_username = $args['api_username'];
		}

		$headers = array(
			'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
			'Api-Username' => sanitize_text_field( $api_username ),
			'Accept'       => 'application/json',
		);
		$opts    = array(
			'timeout' => 30,
		);

		if ( ! empty( $args['body'] ) ) {
			$headers['Content-Type'] = 'application/json';
			$opts['body']            = json_encode( $args['body'] );
		}

		if ( ! empty( $args['headers'] ) ) {
			foreach ( $args['headers'] as $key => $value ) {
				$headers[ $key ] = $value;
			}
		}

		$opts['headers'] = $headers;

		// support relative paths.
		if ( strpos( $url, '://' ) === false ) {
			$url = esc_url_raw( $api_credentials['url'] . $url );
		}

		if ( isset( $args['method'] ) ) {
			$opts['method'] = strtoupper( $args['method'] ); // default GET.
		}

		$response = wp_remote_request( $url, $opts );

		if ( isset( $args['raw'] ) && $args['raw'] ) {
			return $response;
		}

		if ( ! $this->validate( $response ) ) {
			return new \WP_Error(
				'wpdc_response_error',
				'An invalid response was returned from Discourse',
				array(
					'http_code' => wp_remote_retrieve_response_code( $response ),
					'http_body' => wp_remote_retrieve_body( $response ),
				)
			);
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Gets the Discourse API credentials from the options array.
	 *
	 * @return array|\WP_Error
	 */
	protected function get_api_credentials() {
		$options      = isset( $this->options ) ? $this->options : $this->get_options();
		$url          = ! empty( $options['url'] ) ? $options['url'] : null;
		$api_key      = ! empty( $options['api-key'] ) ? $options['api-key'] : null;
		$api_username = ! empty( $options['publish-username'] ) ? $options['publish-username'] : null;

		if ( ! ( $url && $api_key && $api_username ) ) {
			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		return array(
			'url'          => $url,
			'api_key'      => $api_key,
			'api_username' => $api_username,
		);
	}

	/**
	 * Used to transfer data from the 'discourse' options array to the new option_group arrays.
	 *
	 * @param string $old_option The name of the old option_group.
	 * @param array  $transferable_option_groups The array of transferable_option_group names.
	 */
	protected function transfer_options( $old_option, $transferable_option_groups ) {
		$discourse_options = get_option( $old_option );

		foreach ( $transferable_option_groups as $group_name ) {
			$this->transfer_option_group( $discourse_options, $group_name );
		}
	}

	/**
	 * Transfers saved option values to the new options group.
	 *
	 * @param array  $existing_options The old 'discourse' options array.
	 * @param string $group_name The name of the current options group.
	 */
	protected function transfer_option_group( $existing_options, $group_name ) {
		$transferred_options = array();

		foreach ( $this->$group_name as $key => $value ) {
			if ( isset( $existing_options[ $key ] ) ) {
				$transferred_options[ $key ] = $existing_options[ $key ];
			}
		}

		add_option( $group_name, $transferred_options );
	}

	/**
	 * Gets the SSO parameters for a user.
	 *
	 * @param object $user The WordPress user.
	 * @param array  $sso_options An optional array of extra SSO parameters.
	 *
	 * @return array
	 */
	protected function get_sso_params( $user, $sso_options = array() ) {
		$plugin_options      = isset( $this->options ) ? $this->options : $this->get_options();
		$user_id             = $user->ID;
		$require_activation  = get_user_meta( $user_id, 'discourse_email_not_verified', true ) ? true : false;
		$require_activation  = apply_filters( 'discourse_email_verification', $require_activation, $user );
		$force_avatar_update = ! empty( $plugin_options['force-avatar-update'] );
		$avatar_url          = get_avatar_url(
			$user_id,
			array(
				'default' => '404',
			)
		);
		$avatar_url          = esc_url_raw( apply_filters( 'wpdc_sso_avatar_url', $avatar_url, $user_id ) );

		if ( ! empty( $plugin_options['real-name-as-discourse-name'] ) ) {
			$first_name = ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name  = ! empty( $user->last_name ) ? $user->last_name : '';

			if ( $first_name || $last_name ) {
				$name = trim( $first_name . ' ' . $last_name );
			}
		}

		if ( empty( $name ) ) {
			$name = $user->display_name;
		}

		$params = array(
			'external_id'         => $user_id,
			'username'            => $user->user_login,
			'email'               => $user->user_email,
			'require_activation'  => $require_activation ? 'true' : 'false',
			'name'                => $name,
			'bio'                 => $user->description,
			'avatar_url'          => $avatar_url,
			'avatar_force_update' => $force_avatar_update ? 'true' : 'false',
		);

		if ( ! empty( $sso_options ) ) {
			foreach ( $sso_options as $option_key => $option_value ) {
				$params[ $option_key ] = $option_value;
			}
		}

		return apply_filters( 'wpdc_sso_params', $params, $user );
	}

	/**
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	public function verify_discourse_webhook_request( $data ) {
		$options = isset( $this->options ) ? $this->options : $this->get_options();
		// The X-Discourse-Event-Signature consists of 'sha256=' . hamc of raw payload.
		// It is generated by computing `hash_hmac( 'sha256', $payload, $secret )`.
		$sig = substr( $data->get_header( 'X-Discourse-Event-Signature' ), 7 );
		if ( $sig ) {
			$payload = $data->get_body();
			// Key used for verifying the request - a matching key needs to be set on the Discourse webhook.
			$secret = ! empty( $options['webhook-secret'] ) ? $options['webhook-secret'] : '';

			if ( ! $secret ) {

				return new \WP_Error( 'discourse_webhook_configuration_error', 'The webhook secret key has not been set.' );
			}

			if ( hash_hmac( 'sha256', $payload, $secret ) === $sig ) {

				return $data;
			} else {

				return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: signatures did not match.' );
			}
		}

		return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: the X-Discourse-Event-Signature was not set for the request.' );
	}

	/**
	 * Saves the topic_id/blog_id to the wpdc_topic_blog table.
	 *
	 * Used for multisite installations so that a Discourse topic_id can be associated with a blog_id.
	 *
	 * @param int $topic_id The topic_id to save to the database.
	 * @param int $blog_id The blog_id to save to the database.
	 */
	public function save_topic_blog_id( $topic_id, $blog_id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
		$wpdb->insert(
			$table_name,
			array(
				'topic_id' => $topic_id,
				'blog_id'  => $blog_id,
			),
			array(
				'%d',
				'%d',
			)
		); // db call whitelist.
	}
}
