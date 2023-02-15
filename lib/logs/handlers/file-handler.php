<?php
/**
 * Log handler that writes logs to a .log file
 *
 * @package WPDiscourse
 * @todo Review phpcs exclusions.
 */

namespace WPDiscourse\Logs;

use \WPDiscourse\Monolog\Handler\StreamHandler;

/**
 * Class FileHandler
 */
class FileHandler extends StreamHandler {

    const DATE_FORMAT    = 'Y-m-d';
    const FILE_NAMESPACE = 'wp-discourse';

    /**
     * Flag to determine whether file handler can be used.
     *
     * @var null|FileHandler
     */
    public $enabled;

    /**
     * FileHandler's instance of FileManager
     *
     * @var null|FileHandler
     */
    public $file_manager;

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
     *
     * @param object  $file_manager Instance of file manager.
     * @param integer $file_size_limit Size limit of .log file.
     * @param integer $max_files Maximum number of .log files.
     * @param string  $datetime Current datetime (for testing purposes).
     */
    public function __construct( $file_manager, $file_size_limit = ( 5 * 1024 * 1024 ), $max_files = 10, $datetime = null ) {
        $file_manager->validate();
        $this->enabled = $file_manager->ready();

        if ( ! $this->enabled ) {
            return;
        }

        $this->file_manager    = $file_manager;
        $this->max_files       = $max_files;
        $this->file_size_limit = $file_size_limit;
        $this->file_number     = $this->current_file_number();
        $this->datetime        = $datetime;

        // Arguments for StreamHandler.
        $current_url     = $this->current_file_url();
        $url             = $current_url ? $current_url : $this->build_new_file_url();
        $level           = Logger::DEBUG; // we want this handler for all levels.
        $bubble          = false; // we currently have only one handler, so no bubbling.
        $file_permission = null; // we handle permissions in the file manager.
        $use_locking     = true; // we want a log file lock if possible.

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
    public function close() {
        parent::close();

        if ( true === $this->must_rotate ) {
            $this->rotate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset() {
        parent::reset();

        if ( true === $this->must_rotate ) {
            $this->rotate();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record Log record being written.
     */
    protected function write( array $record ) {
        if ( null === $this->must_rotate ) {
            $this->must_rotate = ! file_exists( $this->url );
        }

        // Ensure we're writing to today's log file.
        $date = $record['datetime']->format( static::DATE_FORMAT );
        if ( ! preg_match( '/' . $date . '/', $this->url ) ) {
            $files_for_date = $this->list_files( "*$date*" );

            if ( count( $files_for_date ) > 0 ) {
                $this->url = $files_for_date[0];
            } else {
                $this->must_rotate = true;
                $this->close();
            }
        }

        // Ensure the log file is not too large.
        if ( file_exists( $this->url ) && ! $this->validate_size() ) {
            $this->file_number++;
            $this->must_rotate = true;
            $this->close();
        }

        StreamHandler::write( $record );
    }

    /**
     * Returns the log file size limit
     */
    public function get_file_size_limit() {
        return $this->file_size_limit;
    }

    /**
     * Lists log files in descending order by date and number
     *
     * @param string $filter optional. Regex pattern for filename.
     */
    public function list_files( $filter = '*' ) {
        $files = glob( $this->file_manager->logs_dir . "/$filter.log" );

        usort(
            $files,
            function ( $a, $b ) {
        				$a_date = $this->get_date_from_url( $a );
        				$b_date = $this->get_date_from_url( $b );

        				if ( $a_date > $b_date ) {
        				    return -1;
        				}

                if ( $a_date < $b_date ) {
                    return 1;
        				}

        				$a_number = $this->get_number_from_url( $a );
        				$b_number = $this->get_number_from_url( $b );

        				if ( $a_number > $b_number ) {
        				    return -1;
        				}

                if ( $a_number < $b_number ) {
                    return 1;
        				}

        				return strcmp( $a, $b );
        		}
        );

        return $files;
    }

    /**
     * Returns the url of the current log file
     */
    public function current_file_url() {
        $date  = $this->get_date();
        $files = $this->list_files( "*$date*" );

        if ( count( $files ) > 0 ) {
            return reset( $files );
        } else {
            return false;
        }
    }

    /**
     * Returns the current log file number
     */
    public function current_file_number() {
        $file_url = $this->current_file_url();

        if ( $file_url ) {
            return $this->get_number_from_url( $file_url );
        } else {
            return 1;
        }
    }

    /**
     * Handles log rotation
     */
    protected function rotate() {
        $this->url = $this->build_new_file_url();
        $files     = $this->list_files();

        if ( count( $files ) >= ( $this->max_files - 1 ) ) {
            foreach ( array_slice( $files, ( $this->max_files - 1 ) ) as $file ) {
        				if ( is_writable( $file ) ) {
                    // Note from monolog/monolog:
          					// "suppress errors here as unlink() might fail if two processes
          					// are cleaning up/rotating at the same time.".
                    // phpcs:disable WordPress.PHP.DevelopmentFunctions
          					set_error_handler(
                        function () {
              							return false;
              					}
                    );
          					unlink( $file );
          					restore_error_handler();
                    // phpcs:enabled WordPress.PHP.DevelopmentFunctions
        				}
            }
        }

        $this->must_rotate = false;
    }

    /**
     * Builds a new log file url
     */
    protected function build_new_file_url() {
        $dir_path  = $this->file_manager->logs_dir;
        $name      = $this->file_name();
        $hash      = wp_hash( $name, 'nonce' );
        $extension = 'log';
        return "$dir_path/$name-$hash.$extension";
    }

    /**
     * Returns date used by file handler
     */
    public function get_date() {
        if ( isset( $this->datetime ) ) {
            return $this->datetime->format( static::DATE_FORMAT );
        } else {
            return gmdate( static::DATE_FORMAT );
        }
    }

    /**
     * Validates size of current log file against size limit
     */
    protected function validate_size() {
        // Note https://github.com/WordPress/WordPress-Coding-Standards/pull/1265#issuecomment-405143028.
        // Note https://github.com/woocommerce/woocommerce/issues/6091.
        $handle                = fopen( $this->url, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        $stat                  = fstat( $handle );
        $last_line_byte_buffer = 100;
        return $stat['size'] <= ( $this->file_size_limit - $last_line_byte_buffer );
    }

    /**
     * Builds current log file name
     */
    protected function file_name() {
        $date   = $this->get_date();
        $number = $this->file_number;
        return $this->build_filename( $date, $number );
    }

    /**
     * Build file name
     *
     * @param string $date Log date.
     * @param string $number Log number.
     */
    protected function build_filename( $date, $number ) {
        $namespace = static::FILE_NAMESPACE;
        return "$namespace-$date-$number";
    }

    /**
     * Retrieves file number from file url
     *
     * @param string $file_url URL of log file.
     */
    public function get_number_from_url( $file_url ) {
        $parts = explode( '-', $file_url );
        end( $parts );
        return (int) prev( $parts );
    }

    /**
     * Retrieves file date from file url
     *
     * @param string $file_url URL of log file.
     */
    public function get_date_from_url( $file_url ) {
        $parts      = explode( '-', $file_url );
        $date_parts = array_slice( array_slice( $parts, -5 ), 0, 3 );
        return implode( '-', $date_parts );
    }

    /**
     * Retrieves file name from file url
     *
     * @param string $file_url URL of log file.
     */
    public function get_filename( $file_url ) {
        $date   = $this->get_date_from_url( $file_url );
        $number = $this->get_number_from_url( $file_url );
        return $this->build_filename( $date, $number );
    }
}
