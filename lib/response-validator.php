<?php
namespace WPDiscourse\ResponseValidator;

/**
 * Class ResponseValidator
 *
 * Validates the response from `wp_remote_get` and `wp_remote_post`.
 * Sets and gets the status of the connection to Discourse.
 *
 */
class ResponseValidator {
  static protected $instance;

  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  private function __construct() {
  }

  public function check_connection_status() {
    $options = get_option( 'discourse' );
    $url     = array_key_exists( 'url', $options ) ? $options['url'] : '';
    $url     = add_query_arg( array(
      'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
      'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : ''
    ), $url . '/users/' . $options['publish-username'] . '.json' );

    $url      = esc_url_raw( $url );
    $response = wp_remote_get( $url );

    return $this->validate( $response );
  }

  public function validate( $response ) {

    // There will be a WP_Error if the server can't be accessed
    if ( is_wp_error( $response ) ) {
      error_log( $response->get_error_message() );

      return 0;

      // There is a response from the server, but it's not what we're looking for.
    } elseif ( wp_remote_retrieve_response_code( $response ) != 200 ) {
      $error_message = wp_remote_retrieve_response_code( $response );
      error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );

      return 0;
    } else {
      // valid response
      return 1;
    }

  }
}