<?php
/**
 * Class LoggerTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Logs\Logger;
use \WPDiscourse\Logs\NullHandler;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Logs\LineFormatter;
use \WPDiscourse\Test\UnitTest;

/**
 * Logger test case.
 */
class LoggerTest extends UnitTest {

		/**
		 * Teardown each test.
		 */
		public function tearDown() {
			parent::tearDown();

			self::$plugin_options['logs-enabled'] = 1;
		}

		/**
		 * It creates an instance of Logger
		 */
		public function test_create() {
				$logger = Logger::create( 'test', self::$plugin_options );
				$this->assertInstanceOf( Logger::class, $logger );

				return $logger;
		}

		/**
		 * It attaches FileHandler as the default handler
		 *
		 * @param object $logger Instance of \WPDiscourse\Logs\Logger.
		 * @depends test_create
		 */
		public function test_create_handler( $logger ) {
				$handlers = $logger->getHandlers();
				$this->assertCount( 1, $handlers );

				$file_handler = reset( $handlers );
				$this->assertInstanceOf( FileHandler::class, $file_handler );

				return $file_handler;
		}

		/**
		 * It attaches LineFormatter as the default formatter
		 *
		 * @param object $file_handler Instance of \WPDiscourse\Logs\FileHandler.
		 * @depends test_create_handler
		 */
		public function test_create_handler_formatter( $file_handler ) {
				$this->assertInstanceOf( LineFormatter::class, $file_handler->getFormatter() );
		}

		/**
		 * It attaches NullHandler if FileHandler is not enabled
		 */
		public function test_create_file_handler_not_enabled() {
				$file_handler_double = \Mockery::mock( FileHandler::class )->makePartial();
				$file_handler_double->shouldReceive( 'enabled' )->andReturn( false );

				$logger   = Logger::create( 'test', self::$plugin_options, $file_handler_double );
				$handlers = $logger->getHandlers();

				$this->assertCount( 1, $handlers );
				$this->assertContainsOnlyInstancesOf( NullHandler::class, $handlers );
		}

		/**
		 * It attaches NullHandler if logs are not enabled
		 */
		public function test_create_logs_not_enabled() {
				self::$plugin_options['logs-enabled'] = 0;
				$logger   = Logger::create( 'test', self::$plugin_options );
				$handlers = $logger->getHandlers();

				$this->assertCount( 1, $handlers );
				$this->assertContainsOnlyInstancesOf( NullHandler::class, $handlers );
		}
}
