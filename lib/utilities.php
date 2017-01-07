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
		$options = [];

		$discourse_option_groups = get_option( 'discourse_option_groups' );
		if ( $discourse_option_groups ) {
			foreach ( $discourse_option_groups as $option_name ) {
				if ( get_option( $option_name ) ) {
					$option  = get_option( $option_name );
					$options = array_merge( $options, $option );
				}
			}
		}

		return $options;
	}

	/**
	 * Checks the connection status to Discourse.
	 *
	 * @return int
	 */
	public static function check_connection_status() {
		$options = self::get_options();
		$url     = array_key_exists( 'url', $options ) ? $options['url'] : '';
		$url     = add_query_arg( array(
			'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
			'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : '',
		), $url . '/users/' . $options['publish-username'] . '.json' );

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
			error_log( 'Discourse has returned an empty response.' );

			return 0;
		} elseif ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}

	/**
	 * Gets the Discourse categories.
	 *
	 * @return array|mixed|object|\WP_Error|WP_Error
	 */
	public static function get_discourse_categories() {
		$options = self::get_options();

		$url          = add_query_arg( array(
			'api_key'      => $options['api-key'],
			'api_username' => $options['publish-username'],
		), $options['url'] . '/site.json' );
		$force_update = isset( $options['publish-category-update'] ) ? $options['publish-category-update'] : '0';
		$remote       = get_transient( 'discourse_settings_categories_cache' );
		$cache        = $remote;
		if ( empty( $remote ) || $force_update ) {
			$remote = wp_remote_get( $url );
			if ( ! self::validate( $remote ) ) {
				if ( ! empty( $cache ) ) {
					return $cache;
				}

				return new \WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
			}
			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'categories', $remote ) ) {
				$remote = $remote['categories'];
				if ( ! isset( $options['display-subcategories'] ) || 0 === intval( $options['display-subcategories'] ) ) {
					foreach ( $remote as $category => $values ) {
						if ( array_key_exists( 'parent_category_id', $values ) ) {
							unset( $remote[ $category ] );
						}
					}
				}
				set_transient( 'discourse_settings_categories_cache', $remote, HOUR_IN_SECONDS );
			} else {
				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}

		return $remote;
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
	 * Get the time-dependent variable for nonce creation.
	 *
	 * Overrides the default WP nonce_tick function to allow smaller lifespan.
	 *
	 * @method nonce_tick
	 *
	 * @return integer
	 */
	private static function nonce_tick() {
		/**
		 * One can override the default nonce life.
		 *
		 * The default is set to 10 minutes, which is plenty for most of the cases
		 *
		 * @var int
		 */
		$nonce_life = apply_filters( 'discourse/nonce_life', 600 );

		return ceil( time() / ( $nonce_life / 2 ) );
	}

	/**
	 * Provides a wrapper of the default WP nonce system, one that allows setting an expiring time
	 *
	 * @method create_nonce
	 *
	 * @param string|int $action Scalar value to add context to the nonce.
	 *
	 * @return string
	 */
	public static function create_nonce( $action = -1 ) {
		$user = wp_get_current_user();
		$uid = (int) $user->ID;
		if ( ! $uid ) {
			/** This filter is documented in wp-includes/pluggable.php */
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		$token = wp_get_session_token();
		$i = self::nonce_tick();

		return substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
	}

	/**
	 * Verify that correct nonce was used with time limit.
	 *
	 * The user is given an amount of time to use the token, so therefore, since the
	 * UID and $action remain the same, the independent variable is the time.
	 *
	 * @param string     $nonce  Nonce that was used in the form to verify.
	 * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
	 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated in the
	 *                   first half of the nonce_tick, 2 if the nonce is valid and generated in the second half of the nonce_tick.
	 */
	public static function verify_nonce( $nonce, $action = -1 ) {
		$nonce = (string) $nonce;
		$user = wp_get_current_user();
		$uid = (int) $user->ID;
		if ( ! $uid ) {
			/** This filter is documented in wp-includes/pluggable.php */
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		$token = wp_get_session_token();
		$i = self::nonce_tick();

		// Nonce generated in the first half of the time returned by `nonce_tick` passed.
		$expected = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 1;
		}

		// Nonce generated in the second half of the time returned by `nonce_tick` passed.
		$expected = substr( wp_hash( ( $i - 1 ) . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
		if ( hash_equals( $expected, $nonce ) ) {
			return 2;
		}

		/** This filter is documented in wp-includes/pluggable.php */
		do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );

		// Invalid nonce.
		return false;
	}
}
