<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

/**
 * Class LogsFolderManager. Manages creation, access and validation of logs directory.
 */
class LogsFolderManager {
  
  /**
	 * wp-discourse uploads directory
	 *
	 * @var null|LogsFolderManager
	 */
  public $upload_dir;
  
  /**
	 * wp-discourse logs directory
	 *
	 * @var null|LogsFolderManager
	 */
  public $logs_dir;
  
  /**
	 * Flag to determine whether logs folder is ready to be used.
	 *
	 * @var null|LogsFolderManager
	 */
  public $ready;
  
  /**
	 * LogsFolderManager constructor
	 */
  public function __construct() {
      $this->ready = false;
      $this->upload_dir = wp_upload_dir()['basedir'] . "/discourse";
      $this->logs_dir = $this->upload_dir . '/logs';
  }
  
  /**
  * Validates that all log folders and access control files are created.
  * Sets $ready to true if validation passes.
  */
  public function validate() {
      $files = array(
        array(
          'base'    => $this->upload_dir,
          'file'    => 'index.html',
          'content' => '',
        ),
        array(
          'base'    => $this->upload_dir,
          'file'    => '.htaccess',
          'content' => 'deny from all',
        ),
        array(
          'base'    => $this->logs_dir,
          'file'    => 'index.html',
          'content' => '',
        ),
        array(
          'base'    => $this->logs_dir,
          'file'    => '.htaccess',
          'content' => 'deny from all',
        )
      );
      
      $this->create_files($files);
      
      if ( $this->all_files_exist($files) ) {
          $this->ready = true;
      }
  }
  
  /**
	 * Creates directories and files if they don't exist 
	 *
   * @access protected
	 * @param string $files List of files
	 */
  protected function create_files( $files ) {
      foreach ( $files as $file ) {
    			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
      				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' );
      				
              if ( $file_handle ) {
        					fwrite( $file_handle, $file['content'] );
        					fclose( $file_handle );
      				}
    			}
  		}
  }
  
  /**
	 * Checks if all directories and files exist
	 *
   * @access protected
	 * @param string $files List of files
	 */
  protected function all_files_exist( $files ) {
      foreach ( $files as $file ) {
          if ( ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
              return false;
          }
      }
      return true;
  }
}