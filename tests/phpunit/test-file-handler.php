<?php
/**
 * Class FileHandlerTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Logs\Logger;
use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Logs\LineFormatter;
use \WPDiscourse\Test\UnitTest;

/**
 * FileHandler test case.
 */
class FileHandlerTest extends UnitTest {
		/**
		 * It creates an instance of FileHandler
		 */
		public function test_construct() {
		    $file_handler = new FileHandler( new FileManager() );
				$this->assertInstanceOf( FileHandler::class, $file_handler );
		}

		/**
		 * It is enabled if the File Manager is ready
		 */
		public function test_enabled() {
				$file_handler = new FileHandler( new FileManager() );
				$this->assertTrue( $file_handler->enabled() );
		}

		/**
		 * It is not enabled if the File Manager is not ready
		 */
		public function test_not_enabled() {
		    $file_manager_double = \Mockery::mock( FileManager::class )->makePartial();
		    $file_manager_double->shouldReceive( 'ready' )->andReturn( false );
		    $file_handler = new FileHandler( $file_manager_double );
		    $this->assertFalse( $file_handler->enabled() );
		}

		/**
		 * It creates log files to write logs to
		 */
		public function test_log_file_create() {
				$file_handler = new FileHandler( new FileManager() );
				$logger       = Logger::create( 'test', self::$plugin_options, $file_handler );
				$logger->info( 'New Log' );

				$manager   = new FileManager();
				$log_files = glob( $manager->logs_dir . '/*.log' );
				$this->assertCount( 1, $log_files );

				$log_file = $log_files[0];
				$this->assertFileExists( $log_file );
	 	}

		/**
		 * It writes logs to a file it has created
		 */
		public function test_log_file_write() {
				$file_handler = new FileHandler( new FileManager() );
				$logger       = Logger::create( 'test', self::$plugin_options, $file_handler );
				$logger->info( 'New Log' );

				$manager   = new FileManager();
				$log_files = glob( $manager->logs_dir . '/*.log' );
				$this->assertCount( 1, $log_files );

				$log_file = $log_files[0];
		    $last_entry   = shell_exec( "tail -n 1 $log_file" );
		    $this->assertRegExp( '/New Log/', $last_entry );
		}

		/**
		 * It writes multiple logs to the same file
		 */
		public function test_log_file_multiple() {
				$file_manager = new FileManager();
				$file_handler = new FileHandler( $file_manager );

				$logger = Logger::create( 'test', self::$plugin_options, $file_handler );
				for ( $i = 1; $i <= 10; $i++ ) {
				$logger->warning( "Multi Log $i" );
				}

				$log_files = glob( $file_manager->logs_dir . '/*.log' );
				$this->assertCount( 1, $log_files );

				$matching_line_count = 0;
				$handle              = fopen( $log_files[0], 'r' );
				while ( ! feof( $handle ) ) {
				$line = fgets( $handle );

				if ( strpos( $line, 'Multi Log' ) !== false ) {
							$matching_line_count++;
					}
				}
				fclose( $handle );

				$this->assertEquals( 10, $matching_line_count );
		}

		/**
		 * It rotates log files every day.
		 */
		public function test_log_file_date_rotation() {
				$file_manager = new FileManager();
				$file_handler = new FileHandler( $file_manager );

				$logger = Logger::create( 'test', self::$plugin_options, $file_handler );
				$logger->warning( "Today's Log" );

				$todays_datetime    = new \DateTimeImmutable( 'now' );
				$tomorrows_datetime = new \DateTimeImmutable( 'tomorrow' );

				// Make file handler think it's tomorrow.
				$tomorrows_file_handler = new FileHandler( $file_manager, null, null, $tomorrows_datetime );

				// Make logger think it's tomorrow.
				$tomorrows_logger = Logger::create( 'test', self::$plugin_options, $tomorrows_file_handler );
				$tomorrows_logger->pushProcessor(
	      		function ( $record ) use ( $tomorrows_datetime ) {
				   			$record['datetime'] = $tomorrows_datetime;
				   			return $record;
						}
				);

				$tomorrows_logger->warning( "Tomorrow's Log" );

				$tomorrows_date = $tomorrows_datetime->format( FileHandler::DATE_FORMAT );
				$todays_date    = $todays_datetime->format( FileHandler::DATE_FORMAT );

				$files = $file_handler->list_files();
				$this->assertRegExp( '/' . $tomorrows_date . '/', $files[0] );
				$this->assertRegExp( '/' . $todays_date . '/', $files[1] );
		}

		/**
		 * It rotates logs when size limit is reached.
		 */
		public function test_log_file_size_limit_rotation() {
				$file_manager = new FileManager();
				$file_handler = new FileHandler( $file_manager );

				$logger = Logger::create( 'high-volume', self::$plugin_options, $file_handler );
				$logger->warning( 'High volume log' );

				// It's inefficient to create a large file via individual logs, so we're
				// stuffing the log file with filler data so it's almost up to the limit
				// then taking it over the limit with normal logs.

				$handle = fopen( $file_handler->getUrl(), 'wb' );
				$limit  = $file_handler->get_file_size_limit();

				while ( fstat( $handle )['size'] < ( $limit - ( 1024 * 30 * 1 ) ) ) {
				fwrite( $handle, str_repeat( "filler line taking up 30 bts\n", 1024 ) );
				}

				for ( $i = 1; $i <= 300; $i++ ) {
				$logger->warning( 'High volume log' );
				}

				$this->assertLessThanOrEqual( $limit, fstat( $handle )['size'] );
				$this->assertCount( 2, $file_handler->list_files() );
		}

		/**
		 * It increments file numbers on each rotation.
		 */
		public function test_log_file_number() {
				$file_manager = new FileManager();

				// Size limit to restrict each file to a single line.
				$low_limit_file_handler = new FileHandler( $file_manager, 200 );

				$logger = Logger::create( 'one-log-per-file', self::$plugin_options, $low_limit_file_handler );

				for ( $i = 1; $i <= 7; $i++ ) {
				$logger->warning( 'A line long enough to take it over 100 bytes with log metadata' );
				}

				$this->assertCount( 7, $low_limit_file_handler->list_files() );
				$this->assertEquals( 7, $low_limit_file_handler->current_file_number() );
		}

		/**
		 * It respects the max_files limit.
		 */
		public function test_log_max_files() {
				$file_manager = new FileManager();

				// Size limit to restrict each file to a single line.
				$handler = new FileHandler( $file_manager, 200 );
				$logger  = Logger::create( 'one-log-per-file', self::$plugin_options, $handler );

				for ( $i = 1; $i <= 15; $i++ ) {
				$logger->warning( 'A line long enough to take it over 100 bytes with log metadata' );
				}

				$files = $handler->list_files();

				$this->assertCount( 10, $files );

				// Ensure the right files have been removed.
				$this->assertEquals( 15, $handler->get_number_from_url( $files[0] ) );
				$this->assertEquals( 6, $handler->get_number_from_url( end( $files ) ) );
		}
}
