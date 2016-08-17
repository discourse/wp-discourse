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
	 * Checks the connection status to Discourse.
	 *
	 * @return int
	 */
	public static function check_connection_status() {
		$options = get_option( 'discourse' );
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
		// There will be a WP_Error if the server can't be accessed.
		if ( is_wp_error( $response ) ) {
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
	 * Returns the user's Discourse homepage.
	 *
	 * @param string $url The base URL of the Discourse forum.
	 * @param object $post The Post object.
	 *
	 * @return string
	 */
	public static function homepage( $url, $post ) {
		return $url . '/users/' . strtolower( $post->username );
	}

	/**
	 * Substitutes the value for `$size` into the template.
	 *
	 * @param string $template The avatar template.
	 * @param int    $size The size of the avarar.
	 *
	 * @return mixed
	 */
	public static function avatar( $template, $size ) {
		return str_replace( '{size}', $size, $template );
	}

	/**
	 * Replaces relative image src with absolute.
	 *
	 * This function may not be required anymore.
	 * See: https://meta.discourse.org/t/can-emoji-be-rendered-with-absolute-urls/47250
	 *
	 * @param string $url The base url of the forum.
	 * @param string $content The content to be checked.
	 *
	 * @return mixed
	 */
	public static function convert_relative_img_src_to_absolute( $url, $content ) {
		if ( preg_match( "/<img\s*src\s*=\s*[\'\"]?(https?:)?\/\//i", $content ) ) {
			return $content;
		}

		$search  = '#<img src="((?!\s*[\'"]?(?:https?:)?\/\/)\s*([\'"]))?#';
		$replace = "<img src=\"{$url}$1";

		return preg_replace( $search, $replace, $content );
	}

	/**
	 * Gets the Discourse categories.
	 *
	 * @return array|mixed|object|\WP_Error|WP_Error
	 */
	public static function get_discourse_categories() {
		$options = get_option( 'discourse' );
		$url = add_query_arg( array(
			'api_key' => $options['api-key'],
			'api_username' => $options['publish-username'],
		), $options['url'] . '/site.json' );
		$force_update = isset( $options['publish-category-update'] ) ? $options['publish-category-update'] : '0';
		$remote = get_transient( 'discourse_settings_categories_cache' );
		$cache = $remote;
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
	 * This function allows string to pass through unsanitized when in the development environment.
	 *
	 * This is a temporary work around for handling protocol relative image src urls.
	 * see http://wordpress.stackexchange.com/questions/232420/getting-wp-kses-post-to-handle-protocol-relative-image-src-urls-that-include-a-p.
	 *
	 * TODO: Find a better way to do this.
	 *
	 * @param string $string The string to be sanitized.
	 *
	 * @return mixed
	 */
	public static function sanitize_for_environment( $string ) {
		if ( defined( 'WP_ENV' ) && 'development' === WP_ENV ) {
			return $string;
		} else {
			return wp_kses_post( $string );
		}
	}
}
