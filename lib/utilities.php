<?php
/**
 * Static utility functions used throughout the plugin.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Utilities;

/**
 * Class Utilities
 *
 * @package WPDiscourse
 */
class Utilities {

	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * @return array
	 */
	public static function get_options() {
		static $options = array();

		if ( empty( $options ) ) {
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
		}

		return apply_filters( 'wpdc_utilities_options_array', $options );
	}

	/**
	 * Validates the response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @param array $response The response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @return int
	 */
	public static function validate( $response ) {
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
	 * Gets the Discourse categories.
	 *
	 * @return array
	 */
	public static function get_discourse_categories() {

		return get_option( 'wpdc_discourse_categories' );
	}

	/**
	 * Creates a Discourse user through the API.
	 *
	 * @param \WP_User $user The WordPress user.
	 * @param bool     $require_activation Whether or not to require an activation email to be sent.
	 *
	 * @return int|\WP_Error
	 */
	public static function create_discourse_user( $user, $require_activation = true ) {

		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		if ( empty( $user ) || empty( $user->ID ) || is_wp_error( $user ) ) {

			return new \WP_Error( 'wpdc_user_not_set_error', 'The Discourse user you are attempting to create does not exist on WordPress.' );
		}

		$require_activation = apply_filters( 'wpdc_auto_create_user_require_activation', $require_activation, $user );
		$create_user_url    = esc_url_raw( "{$api_credentials['url']}/users" );
		$username           = $user->user_login;
		$name               = $user->display_name;
		$email              = $user->user_email;
		$password           = wp_generate_password( 20 );
		$response           = wp_remote_post(
			$create_user_url, array(
				'method' => 'POST',
				'body'   => array(
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
					'name'         => $name,
					'email'        => $email,
					'password'     => $password,
					'username'     => $username,
					'active'       => $require_activation ? 'false' : 'true',
					'approved'     => 'true',
				),
			)
		);

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( wp_remote_retrieve_response_code( $response ), 'An error was returned from Discourse when attempting to create a user.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $user_data->success ) ) {

			return new \WP_Error( 'wpdc_response_error', $user_data->message );
		}

		if ( isset( $user_data->user_id ) ) {

			return $user_data->user_id;
		}

		return new \WP_Error( wp_remote_retrieve_response_code( $response ), 'The Discourse user could not be created.' );
	}

	/**
	 * Gets the Discourse groups and saves the non-automatic groups in a transient.
	 *
	 * The transient has an expiry time of 10 minutes.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_discourse_groups() {
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$groups_url = "{$api_credentials['url']}/groups.json";
		$groups_url = esc_url_raw(
			add_query_arg(
				array(
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				), $groups_url
			)
		);

		$response = wp_remote_get( $groups_url );

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'An invalid response was returned from Discourse when retrieving Discourse groups data' );
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $response->groups ) ) {
			$groups               = $response->groups;
			$non_automatic_groups = [];

			foreach ( $groups as $group ) {
				if ( empty( $group->automatic ) ) {
					$non_automatic_groups[] = $group;
				}
			}
			set_transient( 'wpdc_non_automatic_groups', $non_automatic_groups, 10 * MINUTE_IN_SECONDS );

			return $non_automatic_groups;
		}

		return new \WP_Error( 'wpdc_response_error', 'No groups were returned from Discourse.' );
	}

	/**
	 * Gets the SSO parameters for a user.
	 *
	 * @param object $user The WordPress user.
	 * @param array  $sso_options An optional array of extra SSO parameters.
	 *
	 * @return array
	 */
	public static function get_sso_params( $user, $sso_options = array() ) {
		$plugin_options      = self::get_options();
		$user_id             = $user->ID;
		$require_activation  = get_user_meta( $user_id, 'discourse_email_not_verified', true ) ? true : false;
		$require_activation  = apply_filters( 'discourse_email_verification', $require_activation, $user );
		$force_avatar_update = ! empty( $plugin_options['force-avatar-update'] );
		$avatar_url          = get_avatar_url(
			$user_id, array(
				'default' => '404',
			)
		);
		$avatar_url          = apply_filters( 'wpdc_sso_avatar_url', $avatar_url, $user_id );

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
	 * Syncs a user with Discourse through SSO.
	 *
	 * @param array $sso_params The sso params to sync.
	 *
	 * @return int|string|\WP_Error
	 */
	public static function sync_sso_record( $sso_params ) {
		$plugin_options = self::get_options();
		if ( empty( $plugin_options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The sync_sso_record function can only be used when SSO is enabled.' );
		}
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$url         = $api_credentials['url'] . '/admin/users/sync_sso';
		$sso_secret  = $plugin_options['sso-secret'];
		$sso_payload = base64_encode( http_build_query( $sso_params ) );
		// Create the signature for Discourse to match against the payload.
		$sig = hash_hmac( 'sha256', $sso_payload, $sso_secret );

		$response = wp_remote_post(
			esc_url_raw( $url ), array(
				'body' => array(
					'sso'          => $sso_payload,
					'sig'          => $sig,
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				),
			)
		);

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'An error was returned from Discourse while trying to sync the sso record.' );
		}

		$discourse_user = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $discourse_user->id ) ) {
			$wordpress_user_id = $sso_params['external_id'];
			update_user_meta( $wordpress_user_id, 'discourse_sso_user_id', $discourse_user->id );
			update_user_meta( $wordpress_user_id, 'discourse_username', $discourse_user->username );
		}

		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Adds a user to a Discourse groups.
	 *
	 * @param int    $user_id The user's ID.
	 * @param string $group_names A comma separated list of group names.
	 *
	 * @return int|string
	 */
	public static function add_user_to_discourse_group( $user_id, $group_names ) {
		$options = self::get_options();
		if ( empty( $options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The add_user_to_discourse_group function can only be used when SSO is enabled.' );
		}
		$user       = get_user_by( 'id', $user_id );
		$sso_params = self::get_sso_params(
			$user, array(
				'add_groups' => $group_names,
			)
		);

		return self::sync_sso_record( $sso_params );
	}

	/**
	 * Removes a user from Discourse groups.
	 *
	 * @param int    $user_id The user's ID.
	 * @param string $group_names A comma separated list of group names.
	 *
	 * @return int|string
	 */
	public static function remove_user_from_discourse_group( $user_id, $group_names ) {
		$options = self::get_options();
		if ( empty( $options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The remove_user_from_discourse_group function can only be used when SSO is enabled.' );
		}
		$user       = get_user_by( 'id', $user_id );
		$sso_params = self::get_sso_params(
			$user, array(
				'remove_groups' => $group_names,
			)
		);

		return self::sync_sso_record( $sso_params );
	}

	/**
	 * Get a Discourse user object.
	 *
	 * @param int  $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function get_discourse_user( $user_id, $match_by_email = false ) {
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse connection options are not properly configured.' );
		}

		$external_user_url = "{$api_credentials['url']}/users/by-external/{$user_id}.json";
		$external_user_url = esc_url_raw(
			add_query_arg(
				array(
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				), $external_user_url
			)
		);

		$response = wp_remote_get( $external_user_url );

		if ( self::validate( $response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $body->user ) ) {

				return $body->user;
			}
		}

		if ( $match_by_email ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {

				return self::get_discourse_user_by_email( $user->user_email );
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
	public static function get_discourse_user_by_email( $email ) {
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$users_url = "{$api_credentials['url']}/admin/users/list/all.json";
		$users_url = esc_url_raw(
			add_query_arg(
				array(
					'email'        => rawurlencode_deep( $email ),
					'filter'       => rawurlencode_deep( $email ),
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				), $users_url
			)
		);

		$response = wp_remote_get( $users_url );
		if ( self::validate( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			// The reqest returns a valid response even if the user isn't found, so check for empty.
			if ( ! empty( $body ) && ! empty( $body[0] ) ) {

				return $body[0];
			} else {

				// A valid response was returned, but the user wasn't found.
				return new \WP_Error( 'wpdc_response_error', 'The user could not be retrieved by their email address.' );
			}
		} else {

			return new \WP_Error( 'wpdc_response_error', 'An invalid response was returned when trying to find the user by email address.' );
		}
	}

	/**
	 * Gets the Discourse API credentials from the options array.
	 *
	 * @return array|\WP_Error
	 */
	protected static function get_api_credentials() {
		$options      = self::get_options();
		$url          = ! empty( $options['url'] ) ? $options['url'] : null;
		$api_key      = ! empty( $options['api-key'] ) ? $options['api-key'] : null;
		$api_username = ! empty( $options['publish-username'] ) ? $options['publish-username'] : null;

		if ( ! ( $url && $api_key && $api_username ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse configuration options have not been set.' );
		}

		return array(
			'url'          => $url,
			'api_key'      => $api_key,
			'api_username' => $api_username,
		);
	}

	/**
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	public static function verify_discourse_webhook_request( $data ) {
		$options = self::get_options();
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
}
