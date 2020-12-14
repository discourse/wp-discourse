<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

require_once __DIR__ . '/handlers/wpdc-null-handler.php';
require_once __DIR__ . '/handlers/wpdc-file-handler.php';
require_once __DIR__ . '/formatters/wpdc-file-formatter.php';

use \Monolog\Logger;
use \WPDiscourse\Admin\WPDCNullHandler;
use \WPDiscourse\Admin\WPDCLogFileHandler;
use \WPDiscourse\Admin\WPDCLogFileFormatter;

/**
 * Class WPDCLogger.
 */
class WPDCLogger extends Logger {
  
  /**
	 * Creates an instance of WPDCLogger for a particular context with the 
   * default file handler. If the file handler cannot be used, a null handler
   * will be used, which throws records away.
	 *
	 * @param string $context The context for the logs, e.g. 'publish'.
	 * 
   * @return WPDCLogger
	 */
  public static function create( $context ) {
    $logger = new WPDCLogger( $context );
    $handler = new WPDCLogFileHandler();
    
    if ( $handler ) {
      $formatter = new WPDCLogFileFormatter();
      $handler->setFormatter( $formatter );
    } else {
      $handler = new WPDCNullHandler();
    }
    
    $logger->pushHandler( $handler );
    
    return $logger;
  }
};