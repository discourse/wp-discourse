<?php
/**
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

require_once __DIR__ . '/folder-manager.php';

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\RotatingFileHandler;

/**
 * Class WPDCLogFileHandler
 * 
 */
class WPDCLogFileHandler extends RotatingFileHandler {
    
    /**
     *
     * Replaces RotatingFileHandler constructor completely, adding
     * folder management, and static handler options.
     * RotatingFileHandler's constructor is intentionally omitted as options
     * passed to the StreamHandler should remain constant in our implementation.
     *
     */
    public function __construct() {
        $folder_manager = new LogsFolderManager();
        $folder_manager->validate();
        
        if ( !$folder_manager->ready ) {
            return false;
        }
        
        $this->filename = $folder_manager->logs_dir . '/wp-discourse';
        $this->maxFiles = 0;
        $this->nextRotation = new \DateTimeImmutable('tomorrow');
        $this->filenameFormat = '{filename}-{date}-{hash}';
        $this->dateFormat = static::FILE_PER_DAY;
        
        $timedFilename = $this->getTimedFilename();

        StreamHandler::__construct( $timedFilename, Logger::DEBUG, true, null, true );
    }
    
    /**
     *
     * Replaces RotatingFileHandler getTimedFiledName().
     * Adds handling for {hash} in filename.
     *
     */
    protected function getTimedFilename(): string {
        $fileInfo = pathinfo( $this->filename );
        $date = date( $this->dateFormat );
        $hash = wp_hash( "{$fileInfo['filename']}-$date" );
                
        $timedFilename = str_replace(
            [ '{filename}', '{date}', '{hash}' ],
            [ $fileInfo['filename'], $date, $hash ],
            $fileInfo['dirname'] . '/' . $this->filenameFormat
        );

        if ( !empty($fileInfo['extension']) ) {
            $timedFilename .= '.' .$fileInfo['extension'];
        }

        return $timedFilename;
    }
}