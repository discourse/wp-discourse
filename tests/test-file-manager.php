<?php
/**
 * Class LoggerTest
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Logs\FileManager;

/**
 * Logger test case.
 */
class FileManagerTest extends WP_UnitTestCase {
	
	/**
	 * Validation creates discourse uploads folder and .htaccess file if they don't exist
	 */
	public function test_validation_uploads_creation() {
		$file_manager = new FileManager();
		
		$this->recursive_rmdir( $file_manager->upload_dir );
		$this->assertDirectoryNotExists( $file_manager->upload_dir );
		
		$file_manager->validate();
		
    $this->assertDirectoryExists( $file_manager->upload_dir );
		$this->assertFileExists( $file_manager->upload_dir . "/.htaccess" );
	}
	
	/**
	 * Validation creates discourse logs folder and .htaccess file if they don't exist
	 */
	public function test_validation_logs_creation() {
		$file_manager = new FileManager();
	
		$this->recursive_rmdir( $file_manager->logs_dir );
		$this->assertDirectoryNotExists( $file_manager->logs_dir );
		
		$file_manager->validate();
		
    $this->assertDirectoryExists( $file_manager->logs_dir );
		$this->assertFileExists( $file_manager->logs_dir . "/.htaccess" );
	}
	
	/**
	 * It is ready if validation passes
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
		
		chmod(wp_upload_dir()['basedir'], 0444);
    
		$this->assertFalse( $file_manager->validate() );
		$this->assertFalse( $file_manager->ready() );
	}
	
	/**
	 * Validation will not pass if all necessary folders and files are not present and writable
	 */
	public function test_validation_when_folders_partially_restricted() {
		$file_manager = new FileManager();
		
		$this->assertTrue( $file_manager->validate() );
		$this->assertTrue( $file_manager->ready() );
		
		chmod($file_manager->logs_dir, 0444);
				
		$this->assertFalse( $file_manager->validate() );
		$this->assertFalse( $file_manager->ready() );
	}
		
	public static function reset_permissions() {
		$file_manager = new FileManager();
		
		chmod( wp_upload_dir()['basedir'], 0744) ;
		
		if ( is_dir( $file_manager->upload_dir ) ) {
			chmod( $file_manager->upload_dir, 0744 );
		}
		
		if ( is_dir( $file_manager->logs_dir ) ) {
			chmod( $file_manager->logs_dir, 0744 );
		}
	}
	
	public static function setUpBeforeClass() {
		static::reset_permissions();
	}
	
	public function tearDown() {
		static::reset_permissions();
		\Mockery::close();
	}
	
	public function recursive_rmdir( $dir ) { 
		if ( is_dir( $dir ) ) { 
			$objects = scandir( $dir );

			foreach ( $objects as $object ) { 
				if ( $object != "." && $object != ".." ) { 
					if ( is_dir($dir. DIRECTORY_SEPARATOR .$object ) ) {
						$this->recursive_rmdir($dir. DIRECTORY_SEPARATOR .$object);
					} else {
						unlink($dir. DIRECTORY_SEPARATOR .$object);
					}
				} 
			}

			rmdir( $dir ); 
		} 
	}
}
