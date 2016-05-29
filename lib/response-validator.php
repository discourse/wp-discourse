<?php
namespace WPDiscourse\ResponseValidator;

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

  public function set_status( $status ) {
    set_transient( 'discourse_connection_status', $status );
  }

  public function get_status() {
    return get_transient( 'discourse_connection_status' );
  }

  // Checks the connection status. If a period is given, only checks once per period.
  public function check_connection_status( $period = null ) {
    $options = get_option( 'discourse' );
    $url     = array_key_exists( 'url', $options ) ? $options['url'] : '';
    $url     = add_query_arg( array(
      'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
      'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : ''
    ), $url . '/users/' . $options['publish-username'] .'.json' );
    
    $url = esc_url_raw( $url );
    $time = date_create()->format( 'U' );

    if ( $period ) {
      if ( get_transient( 'discourse_last_status_update' ) === false ||
           ( ( get_transient( 'discourse_last_status_update' ) + $period ) < $time ) ) {
        $response = wp_remote_get( $url );
        set_transient( 'discourse_last_status_update', $time );

        return $this->validate( $response );
      } else {
        // It's not time to update yet, return the saved status.
        return $this->get_status();
      }

    } else {
      $response = wp_remote_get( $url );
      set_transient( 'discourse_last_status_update', $time );

      return $this->validate( $response );
    }
  }

  public function validate( $response, $update_status = true ) {
    if ( is_wp_error( $response ) ) {
      error_log( $response->get_error_message() );
      if ( $update_status ) {
        $this->set_status( 0 );
      }

      return 0;

    } elseif ( wp_remote_retrieve_response_code( $response ) != 200 ) {
      $error_message = wp_remote_retrieve_response_code( $response );
      error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );
      if ( $update_status ) {
        $this->set_status( 0 );
      }

      return 0;
    }
    // valid response
    if ( $update_status ) {
      $this->set_status( 1 );
    }

    return 1;
  }
}