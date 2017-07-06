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

				$use_multisite_configuration = get_site_option( 'wpdc_site_multisite_configuration' );
				if ( ! is_main_site() && 1 === intval( $use_multisite_configuration ) ) {
					$options['url']                      = get_site_option( 'wpdc_site_url' );
					$options['api-key']                  = get_site_option( 'wpdc_site_api_key' );
					$options['publish-username']         = get_site_option( 'wpdc_site_publish_username' );
					$options['use-discourse-webhook']    = get_site_option( 'wpdc_site_use_discourse_webhook' );
					$options['multisite-configuration']  = get_site_option( 'wpdc_site_multisite_configuration' );
					$options['webhook-match-old-topics'] = get_site_option( 'wpdc_site_webhook_match_old_topics' );

					$options['sso-secret']         = get_site_option( 'wpdc_site_sso_secret' );
					$options['enable-sso']         = get_site_option( 'wpdc_site_enable_sso' );
					$options['sso-client-enabled'] = get_site_option( 'wpdc_site_sso_client_enabled' );
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
	public static function check_connection_status() {
		$url = self::get_connection_option( 'url' );
		$api_key = self::get_connection_option( 'api-key' );
		$api_username = self::get_connection_option( 'publish-username' );

		if ( ! ( $url && $api_key && $api_username ) ) {

			return 0;
		}

		$url = add_query_arg( array(
			'api_key'      => $api_key,
			'api_username' => $api_username,
		), $url . '/users/' . $api_username . '.json' );

		$url      = esc_url_raw( $url );
		$response = wp_remote_get( $url );

		return self::validate( $response );
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
	 * @return array|\WP_Error
	 */
	public static function get_discourse_categories() {
		$options      = self::get_options();
		$force_update = false;

		$categories = get_option( 'wpdc_discourse_categories' );

		if ( ! empty( $options['publish-category-update'] ) || ! $categories ) {
			$force_update = true;
		}

		if ( $force_update ) {
			$base_url = self::get_connection_option( 'url' );
			$api_key = self::get_connection_option( 'api-key' );
			$api_username = self::get_connection_option( 'publish-username' );

			if ( ! ( $base_url && $api_key && $api_username ) ) {

				return new \WP_Error( 'discourse_configuration_error', 'The Discourse connection options have not been configured.' );
			}

			$site_url = esc_url_raw( "{$base_url}/site.json" );
			$site_url    = add_query_arg( array(
				'api_key'      => $api_key,
				'api_username' => $api_username,
			), $site_url );

			$remote = wp_remote_get( $site_url );

			if ( ! self::validate( $remote ) ) {

				return new \WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'categories', $remote ) ) {
				$categories = $remote['categories'];
				if ( empty( $options['display-subcategories'] ) ) {
					foreach ( $categories as $category => $values ) {
						if ( array_key_exists( 'parent_category_id', $values ) ) {
							unset( $categories[ $category ] );
						}
					}
				}
				update_option( 'wpdc_discourse_categories', $categories );
			} else {

				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}

		return $categories;
	}

	/**
	 * Tries to find a WordPress post that's associated with a Discourse topic_id.
	 *
	 * @param int $topic_id The topic_id to lookup.
	 *
	 * @return null|string
	 */
	public static function get_post_id_by_topic_id( $topic_id ) {
		global $wpdb;

		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'discourse_topic_id' AND meta_value = %d", $topic_id ) );

		return $post_id;
	}

	/**
	 * Check if an user is linked to a discourse instance
	 *
	 * @return boolean
	 */
	public static function user_is_linked_to_sso() {
		$user = wp_get_current_user();

		if ( ! $user ) {
			return false;
		}

		return get_user_meta( $user->ID, 'discourse_sso_user_id', true );
	}

	/**
	 * Get a Discourse user object.
	 *
	 * @param int $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function get_discourse_user( $user_id, $match_by_email = false ) {
		$url          = self::get_connection_option( 'url' );
		$api_key      = self::get_connection_option( 'api-key' );
		$api_username = self::get_connection_option( 'publish-username' );

		if ( ! ( $url && $api_key && $api_username ) ) {

			return new \WP_Error( 'discourse_configuration_error', 'The Discourse connection options have not been configured.' );
		}

		$external_user_url = esc_url_raw( "{$url}/users/by-external/{$user_id}.json" );
		$external_user_url = add_query_arg( array(
			'api_key'      => $api_key,
			'api_username' => $api_username,
		), $external_user_url );

		$response = wp_remote_get( $external_user_url );

		if ( self::validate( $response ) ) {

			return json_decode( wp_remote_retrieve_body( $response ) );

		} elseif ( $match_by_email ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$users_url = esc_url_raw( "{$url}/admin/users/list/active.json" );
				$users_url = add_query_arg( array(
					'filter'       => rawurlencode_deep( $user->user_email ),
					'api_key'      => $api_key,
					'api_username' => $api_username,
				), $users_url );

				$response = wp_remote_get( $users_url );
				if ( self::validate( $response ) ) {

					return json_decode( wp_remote_retrieve_body( $response ) );
				}
			}
		}

		return new \WP_Error( 'discourse_response_error', 'The Discourse user could not be retrieved.' );
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

	/**
	 * @param string $option The option to be returned.
	 *
	 * @return string|null
	 */
	protected static function get_connection_option( $option ) {
		static $connection_options = null;

		if ( ! $connection_options ) {
			$connection_options = get_option( 'discourse_connect' );
		}

		if ( isset( $connection_options[ $option ] ) ) {

			return $connection_options[ $option ];
		} else {

			return null;
		}
	}
}
