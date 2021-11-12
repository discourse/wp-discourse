<?php
/**
 * Class LoggerTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Test\UnitTest;

/**
 * Logger test case.
 */
class FileManagerTest extends UnitTest {
		/**
		 * Setup test class.
		 */
		public function setUp() {
				parent::setUp();
				static::reset_permissions();
		}

		/**
		 * Teardown test class.
		 */
		public function tearDown() {
				parent::tearDown();
				static::reset_permissions();
		}

		/**
		 * Validation creates discourse uploads folder and .htaccess file if they don't exist.
		 */
		public function test_validation_uploads_creation() {
				$file_manager = new FileManager();

				$this->recursive_rmdir( $file_manager->upload_dir );
				$this->assertDirectoryNotExists( $file_manager->upload_dir );

				$file_manager->validate();

				$this->assertDirectoryExists( $file_manager->upload_dir );
				$this->assertFileExists( $file_manager->upload_dir . '/.htaccess' );
		}

		/**
		 * Validation creates discourse logs folder and .htaccess file if they don't exist.
		 */
		public function test_validation_logs_creation() {
				$file_manager = new FileManager();

				$this->recursive_rmdir( $file_manager->logs_dir );
				$this->assertDirectoryNotExists( $file_manager->logs_dir );

				$file_manager->validate();

				$this->assertDirectoryExists( $file_manager->logs_dir );
				$this->assertFileExists( $file_manager->logs_dir . '/.htaccess' );
		}

		/**
		 * It is ready if validation passes.
		 */
		public function test_validation_ready() {
				$file_manager = new FileManager();
				$this->assertTrue( $file_manager->validate() );
				$this->assertTrue( $file_manager->ready() );
		}

		/**
		 * It is not ready if validation is not run
		 */
		public function test_validation_not_ready() {
				$file_manager = new FileManager();
				$this->assertFalse( $file_manager->ready() );
		}

		/**
		 * Validation will not pass if wp uploads directory is not writable
		 */
		public function test_validation_when_wp_uploads_not_writable() {
				$file_manager = new FileManager();

				chmod( wp_upload_dir()['basedir'], 0444 );

				$this->assertFalse( $file_manager->validate() );
				$this->assertFalse( $file_manager->ready() );
		}

		/**
		 * Validation will not pass if all necessary folders and files are not present and writable.
		 */
		public function test_validation_when_folders_partially_restricted() {
				$file_manager = new FileManager();

				$this->assertTrue( $file_manager->validate() );
				$this->assertTrue( $file_manager->ready() );

				chmod( $file_manager->logs_dir, 0444 );

				$this->assertFalse( $file_manager->validate() );
				$this->assertFalse( $file_manager->ready() );
		}

		/**
		 * Reset directory permissions.
		 */
		protected function reset_permissions() {
				$file_manager = new FileManager();

				chmod( wp_upload_dir()['basedir'], 0744 );

				if ( is_dir( $file_manager->upload_dir ) ) {
						chmod( $file_manager->upload_dir, 0744 );
				}

				if ( is_dir( $file_manager->logs_dir ) ) {
						chmod( $file_manager->logs_dir, 0744 );
				}
		}

		/**
		 * Recursively remove directory.
		 *
		 * @param string $dir Path of directory to remove.
		 */
		protected function recursive_rmdir( $dir ) {
				if ( is_dir( $dir ) ) {
						$objects = scandir( $dir );

						foreach ( $objects as $object ) {
								if ( '.' !== $object && '..' !== $object ) {
										if ( is_dir( $dir . DIRECTORY_SEPARATOR . $object ) ) {
												$this->recursive_rmdir( $dir . DIRECTORY_SEPARATOR . $object );
										} else {
												unlink( $dir . DIRECTORY_SEPARATOR . $object );
										}
								}
						}

						rmdir( $dir );
				}
		}
}
