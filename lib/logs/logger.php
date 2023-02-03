<?php
/**
 * Creates logs for a context
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Logs;

require_once __DIR__ . '/managers/file-manager.php';
require_once __DIR__ . '/handlers/file-handler.php';
require_once __DIR__ . '/handlers/null-handler.php';
require_once __DIR__ . '/formatters/line-formatter.php';

use \WPDiscourse\Logs\NullHandler;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\LineFormatter;

/**
 * Class Logger.
 */
class Logger extends \WPDiscourse\Monolog\Logger {

    /**
     * Creates an instance of Logger for a particular context with the
     * default file handler. If the file handler cannot be used, a null handler
     * will be used, which throws records away.
     *
     * @param string $context The context for the logs, e.g. 'publish'.
     * @param object $options WP Discourse options.
     * @param object $handler optional. The handler for the logs.
     * @param object $formatter optional. The formatter for the handler.
     *
     * @return Logger
     */
    public static function create( $context, $options, $handler = null, $formatter = null ) {
    		$logger = new Logger( $context );

    		if ( ! $handler ) {
			$handler = new FileHandler( new FileManager() );
    		}

    		if ( ! $formatter ) {
			$formatter = new LineFormatter();
    		}

        $handler_enabled = $handler && $handler->enabled();
        $logs_enabled    = ! empty( $options['logs-enabled'] ) && $handler_enabled;

    		if ( $logs_enabled ) {
            if ( $formatter ) {
                $handler->setFormatter( $formatter );
            }
        } else {
            $handler = new NullHandler();
        }

    		$logger->pushHandler( $handler );

    		return $logger;
    }
};
