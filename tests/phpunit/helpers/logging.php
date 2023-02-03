<?php
/**
 * Class \Test\Logging
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Logs\FileManager;

/**
 * Logging methods for WPDiscourse unit tests
 */
trait Logging {
  /**
   * Get last line in latest log file.
   */
  protected function get_last_log() {
		$manager   = new FileManager();
		$log_files = glob( $manager->logs_dir . '/*.log' );
		if ( empty( $log_files ) ) {
		  return '';
			}
		$log_file = $log_files[0];
		return shell_exec( "tail -n 1 $log_file" );
  }

  /**
   * Clear all logs.
   */
  protected function clear_logs() {
		$manager   = new FileManager();
		$log_files = glob( $manager->logs_dir . '/*.log' );

		foreach ( $log_files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
			}
  }
}
