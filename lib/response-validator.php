<?php
/**
 * Provides methods to validate the response from Discourse.
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/response-validator.php
 * @package WPDiscourse
 */

namespace WPDiscourse\ResponseValidator;

/**
 * Class ResponseValidator
 */
class ResponseValidator {

	/**
	 * An instance of the ResponseValidator class.
	 *
	 * @access protected
	 * @var \WPDiscourse\ResponseValidator\ResponseValidator
	 */
	static protected $instance;

	/**
	 * Returns a single instance of the ResponseValidator class.
	 *
	 * @return ResponseValidator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * A private constructor, only called through `get_instance()`.
	 * ResponseValidator constructor.
	 */
	private function __construct() {
	}

	/**
	 * A function to check the connection status to Discourse.
	 *
	 * @return int
	 */
	public function check_connection_status() {
		$options = get_option( 'discourse' );
		$url     = array_key_exists( 'url', $options ) ? $options['url'] : '';
		$url     = add_query_arg( array(
			'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
			'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : '',
		), $url . '/users/' . $options['publish-username'] . '.json' );

		$url      = esc_url_raw( $url );
		$response = wp_remote_get( $url );

		return $this->validate( $response );
	}

	/**
	 * Validates the response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @param array $response The response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @return int
	 */
	public function validate( $response ) {
		// There will be a WP_Error if the server can't be accessed.
		if ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {
			$error_message = wp_remote_retrieve_response_code( $response );
			error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}
}
