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
			}
		}

		return apply_filters( 'wpdc_utilities_options_array', $options );
	}

	/**
	 * Checks the connection status to Discourse.
	 *
	 * @return int
	 */
	public static function check_connection_status() {
		// Todo: don't run the function before the options have been set. Don't use array_key_exists.
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

		if ( isset( $options['publish-category-update'] ) && 1 === intval( $options['publish-category-update'] ) ||
		     ! $categories
		) {
			$force_update = true;
		}

		if ( $force_update ) {

			$url    = add_query_arg( array(
				'api_key'      => $options['api-key'],
				'api_username' => $options['publish-username'],
			), $options['url'] . '/site.json' );
			$remote = wp_remote_get( $url );
			if ( ! self::validate( $remote ) ) {

				return new \WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'categories', $remote ) ) {
				$categories = $remote['categories'];
				if ( ! isset( $options['display-subcategories'] ) || 0 === intval( $options['display-subcategories'] ) ) {
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

	public static function get_updated_topic_data( $sync_period ) {
		$options      = self::get_options();
		$api_key      = $options['api-key'];
		$api_username = $options['publish-username'];
		$base_url     = $options['url'];

		if ( empty( $base_url ) || empty( $api_key ) || empty( $api_username ) ) {

			return new \WP_Error( 'discourse_connection_settings_not_configured',
				_( 'You need to configure the wp-discourse connection settings.', 'wp-discourse' ) );
		}

		$site_url           = urlencode( site_url() );
		$discourse_date_url = esc_url( $base_url . "/discourse-updated-topics/topic-data/$sync_period.json" );
		$discourse_date_url = add_query_arg( array(
			'api_key'      => $api_key,
			'api_username' => $api_username,
			'site_url'     => $site_url,
		), $discourse_date_url );

		$response = wp_remote_get( $discourse_date_url );

		if ( ! self::validate( $response ) ) {
			return new \WP_Error( 'discourse_invalid_response',
				__( 'An invalid response was returned from Discourse while attempting to sync the data.', 'wp-discourse' ) );
		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( property_exists( $response, 'updated_topics' ) ) {

			return $response->updated_topics;
		} else {

			return new \WP_Error( 'discourse_invalid_response',
				__( 'An invalid response was returned from Discourse while attempting to sync the updated_topics data', 'wp-discourse' ) );
		}
	}

	public static function get_post_id_by_topic_id( $topic_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value = %d", $topic_id );

		$post_id = $wpdb->get_var( $query );

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
}
