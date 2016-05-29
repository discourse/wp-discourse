<?php
namespace WPDiscourse\ConnectionStatus;

/**
 * A class to hold the current connection status.
 * 
 * Class ConnectionStatus
 * @package WPDiscourse\ConnectionStatus
 */
class ConnectionStatus {
  private static $instance = null;
  protected $status;

  /**
   * @return null|ConnectionStatus
   */
  public static function get_instance() {
    if ( null === self::$instance ) {
      self::$instance = new self();
    }
    
    return self::$instance;
  }

  /**
   * ConnectionStatus constructor.
   */
  private function __construct() {
    $this->status = 0;
  }

  /**
   * @param $status
   */
  public function set_status( $status ) {
    $this->status = $status;
  }

  /**
   * @return bool
   */
  public function get_status() {
    return $this->status;
  }
}