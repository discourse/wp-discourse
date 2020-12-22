<?php
/**
 * Log Viewer.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Logs\FileManager;
use WPDiscourse\Logs\FileHandler;

/**
 * Class LogViewer
 */
class LogViewer {
	
	protected $file_handler;
	protected $logs;
	protected $selected_log;
	protected $metafile_name;
	protected $file_namespace;

	/**
	 * LogViewer constructor.
	 *
	 */
	public function __construct() {
		$this->file_handler = new FileHandler( new FileManager() );
		$this->file_namespace = "wp-discourse";
		$this->metafile_name = "logs-metafile";
				
		if ( $this->file_handler->enabled ) {
				add_action( 'admin_init', array( $this, 'setup_logs' ));
				add_action( 'admin_init', array( $this, 'update_meta_file' ));
				
				add_action( "wp_ajax_wpdc_view_log", array( $this, 'log_file_contents' ));
				add_action( "wp_ajax_wpdc_view_logs_metafile", array( $this, 'meta_file_contents' ));
				add_action( "wp_ajax_wpdc_download_logs", array( $this, 'download_logs' ));
		}
		
		add_action( 'admin_init', array( $this, 'register_log_viewer' ));
	}

	/**
	 * Add settings section and register the setting.
	 */
	public function register_log_viewer() {
		add_settings_section(
			'discourse_log_viewer',
			__( 'Log Viewer', 'wp-discourse' ),
			array(
				$this,
				'log_viewer_markup',
			),
			'discourse_logs'
		);
		register_setting('discourse_logs', 'discourse_logs');
	}
	
	/**
	 * Setup logs
	 */
	public function setup_logs() {
			$this->retrieve_logs();
			
			if ( !empty( $this->logs ) && empty( $this->selected_log ) ) {
					$this->selected_log = reset( $this->logs );
			}
	}

	/**
	 * Outputs the markup for the log viewer
	 */
	public function log_viewer_markup() {
		?>
		<?php if ( !empty( $this->file_handler->enabled ) ) : ?>
			<?php if ( !empty( $this->logs ) ) : ?>
				<div id="wpdc-log-viewer-controls">
					<div class="name">
						<h3>
							<?php esc_html_e( 'Log for ', 'wp-discourse' ); ?>
							<?php echo esc_html( $this->file_name( $this->selected_log ) ); ?>
						</h3>
					</div>
					<div class="select">
						<select>
							<?php foreach ( $this->logs as $log_key => $log_info ) : ?>
								<option value="<?php echo esc_attr( $log_key ); ?>">
									<?php echo $this->file_name( $log_info ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="view-meta button"><?php esc_html_e( 'View Meta', 'wp-discourse' ); ?></div>
						<div class="download-logs button"><?php esc_html_e( 'Download All', 'wp-discourse' ); ?></div>
					</div>
				</div>
				<div id="wpdc-log-viewer">
					<span class="spinner"></span>
					<pre><?php echo esc_html( file_get_contents( $this->selected_log['file'] ) ); ?></pre>
				</div>
			<?php else: ?>
				<div class="inline"><p><?php esc_html_e( 'There are currently no logs to view.', 'wp-discourse' ); ?></p></div>
			<?php endif; ?>
		<?php else: ?>
			<div class="inline"><p><?php esc_html_e( 'Logs are disabled.', 'wp-discourse' ); ?></p></div>
		<?php endif; ?>
		<?php
	}
	
	/**
	 * Retrieve log files
	 */
	public function retrieve_logs() {
			$file_handler = $this->file_handler;
			$log_files = $file_handler->listFiles();
						
			$this->logs = array_reduce( $log_files, function ( $result, $log_file ) use ( $file_handler ) {
					$date = $file_handler->getDateFromUrl( $log_file );
					$number = $file_handler->getNumberFromUrl( $log_file );
					$result["$date-$number"] = array(
							"date" => $date,
							"number" => $number,
							"file" => $log_file
					);
			    return $result;
			}, array());
	}
	
	/**
	 * Return log file contents for selected key
	 */
	public function log_file_contents() {			
			$log_key = sanitize_text_field( $_POST [ 'key' ] );
			$log = $this->logs[ $log_key ];
			
			if ( $log ) {
					$this->selected_log = $log;
					
					$response = array(
							'contents' => file_get_contents( $this->selected_log['file']  ),
							'name' => $this->file_name( $this->selected_log )
					);
					
					wp_send_json_success( $response );
			} else {
					wp_send_json_error();
			}
	}
	
	/**
	 * Return log meta file contents
	 */
	public function meta_file_contents() {
			$response = array(
					'contents' => file_get_contents( $this->get_metafile_path() ),
					'name' => "Log Meta File"
			);
			wp_send_json_success( $response );
	}
	
	/**
	 * Download bundled log files
	 */
	public function download_logs() {
			$file_handler = $this->file_handler;
			$log_files = $file_handler->listFiles();
			$date_end = $file_handler->getDateFromUrl( reset( $log_files ) );
			$date_start = $file_handler->getDateFromUrl( end( array_values( $log_files ) ) );
			$date_range = "$date_start-$date_end";
			$filename = "{$this->file_namespace}-logs-$date_range.zip";
			
			$file = tempnam("tmp", $filename);
			$zip = new \ZipArchive();
			$zip->open( $file, \ZipArchive::OVERWRITE );
			
			foreach ( $log_files as $log_file ) {
					$name = $file_handler->getFilename( $log_file );
	        $zip->addFile( $log_file, "$name.log" );
			}
			
			$metafile_path = $this->get_metafile_path();
			$metafile_name = "{$this->file_namespace}-{$this->metafile_name}-{$date_range}.txt";
			$zip->addFile( $metafile_path, $metafile_name );
			$zip->close();
						
			header( "Content-type:  application/zip" );
			header(	"Content-Length: " . filesize( $file ));
			header( "Content-Disposition: attachment; filename=$filename" );
			readfile( $file );
			unlink( $file );
			
			wp_die();
	}
	
	/**
	 * Update meta file
	 */
	public function update_meta_file() {
			$filename = $this->get_metafile_path();
			$contents = $this->build_metafile_contents();
			file_put_contents( $filename, $contents );
	}
	
	/**
	 * Generate file name 
	 */
	protected function file_name( $log_info ) {
			$date = date( get_option( 'date_format' ), strtotime( $log_info['date'] ));
			$number = $log_info['number'];
			$name = esc_html( $date );
			if ($number > 1) {
				$name .= " (" . esc_html( $number ) . ")";
			}
			return $name;
	}
	
	/**
	 * Generate server statistics file
	 */
	protected function build_metafile_contents() {
		$contents = "### This file is included in log downloads ###\n\n";
		
		// Server //
		global $wpdb;
		global $wp_version;
		
		if ( method_exists( $wpdb, 'db_version' ) ) {
        $mysql = preg_replace( '/[^0-9.].*/', '', $wpdb->db_version() );
    } else {
        $mysql = 'N/A';
    }
		$wp = $wp_version;
		$php = phpversion();
		$multisite = is_multisite();
		
		$contents .= "### Server ###\n";
		$contents .= "Wordpress - $wp\n";
		$contents .= "PHP - $php\n";
		$contents .= "MySQL - $mysql\n\n";
		
		// Plugins //
		$active_plugins = get_option('active_plugins');
		$all_plugins = get_plugins();
		$plugins = array();
		
		$contents .= "### Active Plugins ###\n";
		
		foreach( $all_plugins as $plugin_folder => $plugin_data ) {
				if ( in_array( $plugin_folder, $active_plugins ) ) {
						$contents .= "{$plugin_data["Name"]} - {$plugin_data["Version"]}\n";
				}
		}
		
		return $contents;
	}
	
	/**
	 * Get metafile name
	 */
	protected function get_metafile_path() {
			$metafile_dir = $this->file_handler->file_manager->upload_dir;
			return "$metafile_dir/{$this->metafile_name}.txt";
	}
}
