<?php
namespace WPDiscourse\ResponseValidator;

class ResponseValidator {
  static protected $instance;
  protected $connection_status;
  
  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self();
    }
    
    return self::$instance;
  }
  
  
  private function __construct() {
    $this->connection_status = 0;
  }
  
  public function set_status( $status ) {
    $this->connection_status = $status;
  }
  
  public function get_status() {
    return $this->connection_status;
  }
  
  
  function validate( $response, $set_status = true ) {
    if ( is_wp_error( $response ) ) {
      error_log( $response->get_error_message() );
      if ( $set_status ) {
        var_dump('setting status wp error');
        $this->set_status( 0 );
      }
      return 0;
      
    } elseif ( wp_remote_retrieve_response_code( $response ) != 200 ) {
      $error_message = wp_remote_retrieve_response_code( $response );
      error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );
      if ( $set_status ) {
        $this->set_status( 0 );
      }
      var_dump( 'setting status error' );
      return 0;
    }
    // valid response 
    if ( $set_status ) {
      $this->set_status( 1 );
    }
    var_dump('its good');
    return 1;
  }
}