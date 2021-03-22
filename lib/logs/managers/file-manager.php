<?php
/**
 * Manages log folders and files.
 *
 * @package WPDiscourse
 * @todo Review phpcs exclusions.
 */

namespace WPDiscourse\Logs;

/**
 * Class FileManager. Manages creation, access and validation of logs directory.
 */
class FileManager {

    /**
     * Name of uploads folder.
     *
     * @var null|FileManager
     */
    public $upload_folder = 'wp-discourse';

    /**
     * Name of logs folder.
     *
     * @var null|FileManager
     */
    public $logs_folder = 'logs';

    /**
     * Absolute path to uploads directory.
     *
     * @var null|FileManager
     */
    public $upload_dir;

    /**
     * Absolute path to logs directory.
     *
     * @var null|FileManager
     */
    public $logs_dir;

    /**
     * Flag to determine whether files are ready.
     *
     * @access protected
     * @var null|FileManager
     */
    protected $ready;

    /**
     * LogsFileManager constructor
     */
    public function __construct() {
    		$this->ready      = false;
    		$this->upload_dir = wp_upload_dir()['basedir'] . '/' . $this->upload_folder;
    		$this->logs_dir   = $this->upload_dir . '/' . $this->logs_folder;
    }

    /**
     * Validates that all necessary files are ready.
     * Sets $ready to true if validation passes.
     */
    public function validate() {
  		if ( ! is_writable( wp_upload_dir()['basedir'] ) ) {
  		    return false;
  		}

  		$files = array(
  			array(
  				'base'    => $this->upload_dir,
  				'file'    => 'index.html',
  				'content' => '',
  			),
  			array(
  				'base'    => $this->upload_dir,
  				'file'    => '.htaccess',
  				'content' => $this->htaccess_content(),
  			),
  			array(
  				'base'    => $this->logs_dir,
  				'file'    => 'index.html',
  				'content' => '',
  			),
  			array(
  				'base'    => $this->logs_dir,
  				'file'    => '.htaccess',
  				'content' => $this->htaccess_content(),
  			),
  		);

  		$this->create_files( $files );

  		$ready       = $this->files_are_ready( $files );
  		$this->ready = $ready;

  		return $ready;
    }

    /**
     * Public method to determine whether file manager is ready
     */
    public function ready() {
        return $this->ready;
    }

    /**
     * Creates files if they don't exist
     *
     * @access protected
     * @param string $files List of files.
     */
    protected function create_files( $files ) {
        foreach ( $files as $file ) {
    		    $file_path = trailingslashit( $file['base'] ) . $file['file'];
            $dir_exists    = wp_mkdir_p( $file['base'] );
            $dir_writable  = is_writable( $file['base'] );

            // Note https://github.com/WordPress/WordPress-Coding-Standards/pull/1265#issuecomment-405143028.
            // Note https://github.com/woocommerce/woocommerce/issues/6091.
            // phpcs:disable WordPress.WP.AlternativeFunctions
        		if ( $dir_exists && $dir_writable && ! file_exists( $file_path ) ) {
                $file_handle = fopen( $file_path, 'wb' );

                if ( $file_handle ) {
          					fwrite( $file_handle, $file['content'] );
          					fclose( $file_handle );
        				}
        		}
            // phpcs:enable Wordpress.WP.AlternativeFunctions
    		}
    }

    /**
     * Checks if all files exist and are writable
     *
     * @access protected
     * @param string $files List of files.
     */
    protected function files_are_ready( $files ) {
    		foreach ( $files as $file ) {
            $directory_path = trailingslashit( $file['base'] );
            $file_path      = $directory_path . $file['file'];

            if ( ! is_writable( $directory_path ) || ! file_exists( $file_path ) ) {
                return false;
            }
    		}

    		return true;
    }

    /**
     * Returns content of htaccess files
     *
     * @access protected
     */
    protected function htaccess_content() {
        return 'deny from all';
    }
}
