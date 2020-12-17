<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Logs;

use \Monolog\Handler\StreamHandler;

/**
 * Class FileHandler
 */
class FileHandler extends StreamHandler {
    
    public const DATE_FORMAT = 'Y-m-d';
    public const FILE_NAMESPACE = 'wp-discourse';
  
    /**
     * Flag to determine whether file handler can be used.
     *  
     * @access protected
     * @var null|FileHandler
     */
    protected $enabled;
    
    /**
     * Maximum number of log files
     *  
     * @access protected
     * @var null|FileHandler
     */
    protected $max_files;
    
    /**
     * Flag to determine when to rotate log files
     *  
     * @access protected
     * @var null|FileHandler
     */
    protected $must_rotate;
    
    /**
     * FileHandler's instance of FileManager
     *
     * @access protected
     * @var null|FileHandler
     */
    protected $file_manager; 
    
    /**
     * Allows for custom datetime to be set for testing purposes
     *
     * @access protected
     * @var null|FileHandler
     */
    protected $datetime;
    
    /**
     * Size limit for log files
     *
     * @access protected
     * @var null|FileHandler
     */
    protected $file_size_limit;
    
    /**
     * Number to distinguish files with the same date
     *
     * @access protected
     * @var null|FileHandler
     */
    protected $file_number;
    
    /**
     * FileHandler constructor
     */
    public function __construct( $file_manager, $file_size_limit = (5 * 1024 * 1024), $max_files = 10, $datetime = null ) {
        $file_manager->validate();
        $this->enabled = $file_manager->ready();
                
        if ( !$this->enabled ) {
            return;
        }
        
        $this->file_manager = $file_manager;
        $this->max_files = $max_files;
        $this->file_size_limit = $file_size_limit;
        $this->file_number = $this->currentFileNumber();
        $this->datetime = $datetime;
        
        // Arguments for StreamHandler
        $current_url = $this->currentFileUrl();
        $url = $current_url ? $current_url : $this->buildNewFileUrl();
        $level = Logger::DEBUG; // we want this handlers for all levels
        $bubble = false; // we currently have only one handler, so no bubbling
        $file_permission = null; // we handle permissions in file manager
        $use_locking = true; // we want a log file lock if possible
        
        StreamHandler::__construct( $url, $level, $bubble, $file_permission, $use_locking );
    }
    
    /**
    * Public method to determine whether file handler is enabled
    */
    public function enabled() {
        return $this->enabled;
    }
    
    /**
     * {@inheritdoc}
     */
    public function close(): void {
        parent::close();

        if (true === $this->must_rotate) {
            $this->rotate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset() {
        parent::reset();

        if (true === $this->must_rotate) {
            $this->rotate();
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void {
        if ( null === $this->must_rotate ) {
            $this->must_rotate = !file_exists( $this->url );
        }
        
        $date = $record['datetime']->format( static::DATE_FORMAT );
                        
        if ( !preg_match( '/' . $date . '/', $this->url ) ) {
            $files_for_date = $this->listFiles( "*$date*" );
            
            if ( count( $files_for_date ) > 0 ) {
                $this->url = $files_for_date[0];
            } else {
                $this->must_rotate = true;
                $this->close();
            }
        }
        
        if ( file_exists( $this->url ) && !$this->validateSize() ) {
            $this->file_number++;
            $this->must_rotate = true;
            $this->close();
        }

        StreamHandler::write( $record );
    }
    
    /**
    * Returns the log file size limit
    */
    public function getFileSizeLimit() {
        return $this->file_size_limit;
    }
    
    /**
     * Lists log files in descending order by date and number
     *
     * @param string $filter optional. Regex pattern for filename
     */
    public function listFiles( $filter = '*' ) {
      $files = glob( $this->file_manager->logs_dir . "/$filter.log" );
      
      usort($files, function ($a, $b) {
          $a_date = $this->getDateFromUrl( $a );
          $b_date = $this->getDateFromUrl( $b );
          
          if ($a_date > $b_date) {
            return -1;
          }
  				if ($a_date < $b_date) {
            return 1;
          }
          
          $a_number = $this->getNumberFromUrl( $a );
          $b_number = $this->getNumberFromUrl( $b );
          
          if ($a_number > $b_number) {
            return -1;
          }
  				if ($a_number < $b_number) {
            return 1;
          }
          
          return strcmp($a, $b);
  		});
      
      return $files;
    }
    
    /**
     * Returns the url of the current log file
     */
    public function currentFileUrl() {
        $date = $this->getDate();
        $files = $this->listFiles( "*$date*" );
        
        if ( count( $files ) > 0 ) {
            return $files[0];
        } else {
            return false;
        }
    }
    
    /**
     * Returns the current log file number
     */
    public function currentFileNumber() {
        $file_url = $this->currentFileUrl();
        
        if ( $file_url ) {
            return $this->getNumberFromUrl( $file_url );
        } else {
            return 1;
        }
    }
    
    /**
     * Handles log rotation
     */
    protected function rotate() {
        $this->url = $this->buildNewFileUrl();
        $files = $this->listFiles();
                
        if ( count( $files ) >= ( $this->max_files - 1 ) ) {
          foreach ( array_slice( $files, ( $this->max_files - 1 )) as $file ) {
              if ( is_writable( $file ) ) {
                  // suppress errors here as unlink() might fail if two processes
                  // are cleaning up/rotating at the same time
                  set_error_handler(function () {
                      return false;
                  });
                  unlink( $file );
                  restore_error_handler();
              }
          }
        }
        
        $this->must_rotate = false;
    }
    
    /**
     * Builds a new log file url
     */
    protected function buildNewFileUrl() {
        $dir_path = $this->file_manager->logs_dir;
        $name = $this->fileName();
        $hash = wp_hash( $name , "nonce");
        $extension = 'log';

        return "$dir_path/$name-$hash.$extension";
    }
    
    /**
     * Returns date used by file handler
     */
    protected function getDate() {
        if ( isset($this->datetime) ) {
            return $this->datetime->format( static::DATE_FORMAT );
        } else {
            return date( static::DATE_FORMAT );
        }
    }
    
    /**
     * Validates size of current log file against size limit
     */
    protected function validateSize() {
        $handle = fopen( $this->url, 'r+' );
        $stat = fstat( $handle );
        $last_line_byte_buffer = 100;
        return $stat['size'] <= ( $this->file_size_limit - $last_line_byte_buffer );
    }
    
    /**
     * Builds current log file name
     */
    protected function fileName() {
        $namespace = static::FILE_NAMESPACE;
        $date = $this->getDate();
        $number = $this->file_number;
        return "$namespace-$date-$number";
    }
    
    /**
     * Retrieves file number from file url
     */
    public function getNumberFromUrl( $file_url ) {
      $parts = explode( '-', $file_url );
      end($parts);
      return (int)prev( $parts );
    }
    
    /**
     * Retrieves file date from file url
     */
    public function getDateFromUrl( $file_url ) {
      $parts = explode( '-', $file_url );
      $date_parts = array_slice( array_slice($parts, -5) , 0, 3);
      return implode( '-', $date_parts);
    }
}