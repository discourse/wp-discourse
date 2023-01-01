<?php
/**
 * Log Viewer.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Logs\FileManager;
use WPDiscourse\Logs\FileHandler;
use WPDiscourse\Shared\PluginUtilities;

/**
 * Class LogViewer
 */
class LogViewer {
		use PluginUtilities;

		/**
		 * Flag to determine whether LogViewer is enabled.
		 *
		 * @var bool
		 */
		protected $enabled;

		/**
		 * An instance of the FormHelper class.
		 *
		 * @access protected
		 * @var \WPDiscourse\Admin\FormHelper
		 */
		protected $form_helper;

		/**
		 * LogViewer's instance of FileHandler
		 *
		 * @var \WPDiscourse\Logs\FileHandler
		 */
		protected $file_handler;

		/**
		 * LogViewer's log list
		 *
		 * @var mixed
		 */
		protected $logs;

		/**
		 * Current log in LogViewer
		 *
		 * @var object
		 */
		protected $selected_log;

		/**
		 * Metafile name
		 *
		 * @var string
		 */
		protected $metafile_name;

		/**
		 * Gives access to the plugin options.
		 *
		 * @access protected
		 * @var mixed|void
		 */
		protected $options;

		/**
		 * LogViewer constructor.
		 *
		 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
		 */
		public function __construct( $form_helper ) {
				$this->metafile_name = 'logs-metafile';
				$this->form_helper   = $form_helper;

				add_action( 'admin_init', array( $this, 'setup_options' ) );
				add_action( 'admin_init', array( $this, 'setup_log_viewer' ) );
		}

		/**
		 * Sets the plugin options.
		 *
		 * @param object $extra_options Extra options used for testing.
		 */
		public function setup_options( $extra_options = null ) {
			$this->options = $this->get_options();

			if ( ! empty( $extra_options ) ) {
				foreach ( $extra_options as $key => $value ) {
					$this->options[ $key ] = $value;
				}
			}
		}

		/**
		 * Run LogViewer setup tasks.
		 *
		 * @param object $file_handler Instance of \WPDiscourse\Logs\FileHandler.
		 */
		public function setup_log_viewer( $file_handler = null ) {
				if ( $file_handler ) {
						$this->file_handler = $file_handler;
				} else {
						$this->file_handler = new FileHandler( new FileManager() );
				}

				$handler_enabled = $this->file_handler->enabled();
				$this->enabled = ! empty( $this->options['logs-enabled'] ) && $handler_enabled;

				if ( $this->enabled ) {
						$this->setup_logs();

						add_action( 'wp_ajax_wpdc_view_log', array( $this, 'log_file_contents' ) );
						add_action( 'wp_ajax_wpdc_view_logs_metafile', array( $this, 'meta_file_contents' ) );
						add_action( 'wp_ajax_wpdc_download_logs', array( $this, 'download_logs' ) );
				}

				$this->register_log_viewer();
		}

		/**
		 * Add settings section and register the setting.
		 */
		public function register_log_viewer() {
				add_settings_section(
					'discourse_log_viewer',
					__( 'Logs', 'wp-discourse' ),
					array(
						$this,
						'log_viewer_markup',
					),
					'discourse_logs'
				);

				add_settings_field(
					'discourse_logs_enabled',
					__( 'Logging enabled', 'wp-discourse' ),
					array(
						$this,
						'logs_enabled',
					),
					'discourse_logs',
					'discourse_log_viewer'
				);

				register_setting( 'discourse_logs', 'discourse_logs' );
		}

		/**
		 * Outputs markup for the discourse_logs checkbox.
		 */
		public function logs_enabled() {
			$this->form_helper->checkbox_input(
				'logs-enabled',
				'discourse_logs',
				__(
					'Enable WP Discourse logs.',
					'wp-discourse'
				)
			);
		}

		/**
		 * Setup logs
		 */
		public function setup_logs() {
				$this->retrieve_logs();

				if ( ! empty( $this->logs ) && empty( $this->selected_log ) ) {
						$this->selected_log = reset( $this->logs );
				}
		}

		/**
		 * Outputs the markup for the log viewer.
		 */
		public function log_viewer_markup() {
				$selected_log_key = null;

				if ( ! empty( $this->selected_log ) ) {
						$selected_log_key = $this->build_log_key( $this->selected_log );
				}

				?>
				<?php if ( $this->enabled ) : ?>
					<?php /* translators: placeholder interpolates url to documentation on meta.discourse.org */ ?>
					<p><?php printf( esc_html__( 'Please see %s for details.', 'wp-discourse' ), sprintf( '<a href="%s">%s</a>', esc_url( 'https://meta.discourse.org/t/190745' ), esc_html__( 'WP Discourse Logging', 'text-domain' ) ) ); ?></p>
					<?php if ( ! empty( $this->logs ) ) : ?>
						<div id="wpdc-log-viewer-controls">
							<div class="name">
								<h3>
									<?php esc_html_e( 'Log for ', 'wp-discourse' ); ?>
									<?php echo esc_html( $this->file_name( $this->selected_log ) ); ?>
								</h3>
								<a class="load-log">
									<span class="refresh"><?php esc_html_e( 'Refresh', 'wp-discourse' ); ?></span>
									<span class="return-to"><?php esc_html_e( 'Return to log', 'wp-discourse' ); ?></span>
								</a>
							</div>
							<div class="select">
								<select>
									<?php foreach ( $this->logs as $log_key => $log_info ) : ?>
										<option value="<?php echo esc_attr( $log_key ); ?>">
											<?php echo esc_attr( $this->file_name( $log_info ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<div class="view-meta button"><?php esc_html_e( 'View Meta', 'wp-discourse' ); ?></div>
								<div class="download-logs button"><?php esc_html_e( 'Download', 'wp-discourse' ); ?></div>
							</div>
						</div>
						<div id="wpdc-log-viewer" data-log-key="<?php echo esc_attr( $selected_log_key ); ?>">
							<span class="spinner"></span>
							<pre><?php echo esc_html( file_get_contents( $this->selected_log['file'] ) ); ?></pre>
						</div>
					<?php else : ?>
						<div class="inline"><p><?php esc_html_e( 'There are currently no logs to view.', 'wp-discourse' ); ?></p></div>
					<?php endif; ?>
					<?php else : ?>
						<div class="inline"><p><?php esc_html_e( 'Logs are disabled.', 'wp-discourse' ); ?></p></div>
					<?php endif; ?>
				<?php
		}

		/**
		 * Retrieve log files.
		 */
		public function retrieve_logs() {
				$file_handler = $this->file_handler;
				$log_files    = $file_handler->list_files();

				$this->logs = array_reduce(
						$log_files,
						function ( $result, $log_file ) use ( $file_handler ) {
								$date                                   = $file_handler->get_date_from_url( $log_file );
								$number                                 = $file_handler->get_number_from_url( $log_file );
								$log                                    = array(
									'date'   => $date,
									'number' => $number,
									'file'   => $log_file,
								);
								$result[ $this->build_log_key( $log ) ] = $log;
								return $result;
						},
						array()
				);
		}

		/**
		 * Return log file contents for selected key.
		 */
		public function log_file_contents() {
				// See further https://github.com/WordPress/WordPress-Coding-Standards/issues/869.
				if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'admin-ajax-nonce' ) || ! isset( $_POST['key'] ) ) {
						wp_send_json_error();
						return;
				}

				$log_key = sanitize_text_field( wp_unslash( $_POST ['key'] ) );
				$log     = $this->logs[ $log_key ];

				if ( $log ) {
						$this->selected_log = $log;

						$response = array(
							'contents' => file_get_contents( $this->selected_log['file'] ),
							'name'     => $this->file_name( $this->selected_log ),
						);

						wp_send_json_success( $response );
				} else {
						wp_send_json_error();
				}
		}

		/**
		 * Return log meta file contents.
		 */
		public function meta_file_contents() {
				$metafile_contents = $this->build_metafile_contents();

				$response = array(
					'contents' => $metafile_contents,
					'name'     => 'Log Meta File',
				);
				wp_send_json_success( $response );
		}

		/**
		 * Download bundled log files.
		 */
		public function download_logs() {
				$log_files  = $this->file_handler->list_files();
				$date_range = $this->build_date_range( $log_files );

				$plugin_data = get_plugin_data( WPDISCOURSE_PATH . 'wp-discourse.php' );
				$plugin_name = $plugin_data['TextDomain'];
				$site_title  = str_replace( ' ', '-', strtolower( get_bloginfo( 'name' ) ) );

				$filename = "{$site_title}-{$plugin_name}-logs-$date_range.zip";
				$file     = wp_tempnam( 'tmp', $filename );
				$zip      = new \ZipArchive();
				$zip->open( $file, \ZipArchive::OVERWRITE );

				foreach ( $log_files as $log_file ) {
						$name = $this->file_handler->get_filename( $log_file );
						$zip->addFile( $log_file, "$name.log" );
				}

				$metafile_name     = $this->metafile_name;
				$metafile_filename = "{$plugin_name}-{$metafile_name}-{$date_range}.txt";
				$metafile_path     = $this->get_metafile_path( $metafile_filename );

				$this->update_meta_file( $metafile_path );

				$zip->addFile( $metafile_path, $metafile_filename );
				$zip->close();

				header( 'Content-type:  application/zip' );
				header( 'Content-Length: ' . filesize( $file ) );
				header( "Content-Disposition: attachment; filename=$filename" );
				readfile( $file );
				unlink( $file );

				wp_die();
		}

		/**
		 * Update meta file.
		 *
		 * @param string $metafile_path Metafile path.
		 */
		public function update_meta_file( $metafile_path ) {
				$this->remove_meta_files();
				$metafile_contents = $this->build_metafile_contents();
				file_put_contents( $metafile_path, $metafile_contents );
		}

		/**
		 * Remove meta files.
		 */
		public function remove_meta_files() {
			$metafile_name = $this->metafile_name;
			$metafiles     = glob( $this->file_handler->file_manager->upload_dir . "/*{$metafile_name}*.txt" );

			foreach ( $metafiles as $metafile ) {
					if ( is_writable( $metafile ) ) {
							// phpcs:disable WordPress.PHP.DevelopmentFunctions
							set_error_handler(
									function () {
											return false;
									}
							);
							unlink( $metafile );
							restore_error_handler();
							// phpcs:enabled WordPress.PHP.DevelopmentFunctions
					}
			}
		}

		/**
		 * Retrieve logs.
		 */
		public function get_logs() {
				return $this->logs;
		}

		/**
		 * Retrieve enabled state.
		 */
		public function is_enabled() {
				return $this->enabled;
		}

		/**
		 * Generate file name.
		 *
		 * @param array $log_info Log info array.
		 */
		protected function file_name( $log_info ) {
				$date   = gmdate( get_option( 'date_format' ), strtotime( $log_info['date'] ) );
				$number = $log_info['number'];
				$name   = esc_html( $date );
				if ( $number > 1 ) {
						$name .= ' (' . esc_html( $number ) . ')';
				}
				return $name;
		}

		/**
		 * Build log key from log in logs list.
		 *
		 * @param object $item Log object.
		 */
		protected function build_log_key( $item ) {
				$date   = $item['date'];
				$number = $item['number'];
				return "$date-$number";
		}

		/**
		 * Generate server statistics file.
		 */
		protected function build_metafile_contents() {
				$contents = "### This file is included in log downloads ###\n\n";

				global $wpdb;
				global $wp_version;

				if ( method_exists( $wpdb, 'db_version' ) ) {
						$mysql = preg_replace( '/[^0-9.].*/', '', $wpdb->db_version() );
				} else {
						$mysql = 'N/A';
				}
				$wp        = $wp_version;
				$php       = phpversion();
				$multisite = is_multisite();

				$contents .= "### Server ###\n\n";
				$contents .= "WordPress - $wp\n";
				$contents .= "PHP - $php\n";
				$contents .= "MySQL - $mysql\n\n";

				$active_plugins = get_option( 'active_plugins' );
				$all_plugins    = get_plugins();
				$plugins        = array();

				$contents .= "### Active Plugins ###\n\n";

				foreach ( $all_plugins as $plugin_folder => $plugin_data ) {
						if ( in_array( $plugin_folder, $active_plugins, true ) ) {
								$contents .= "{$plugin_data["Name"]} - {$plugin_data["Version"]}\n";
						}
				}

				$contents     .= "\n### WP Discourse Settings (Secrets Excluded) ###\n\n";
				$excluded_keys = array(
					'url',
					'key',
					'secret',
					'text',
					'publish-username',
					'publish-category',
					'publish-failure-email',
					'login-path',
					'existing-comments-heading',
					'sso-client-login-form-redirect',
				);

				foreach ( $this->get_options() as $key => $value ) {
						$exclude = false;

						foreach ( $excluded_keys as $excluded_key ) {
								if ( strpos( $key, $excluded_key ) !== false ) {
										$exclude = true;
								}
						}

						if ( ! $exclude ) {
								if ( is_array( $value ) ) {
										$value = implode( ',', $value );
								}
								$contents .= "$key - $value\n";
						}
				}

				return $contents;
		}

		/**
		 * Get metafile name.
		 *
		 * @param string $filename Metafile filename.
		 */
		protected function get_metafile_path( $filename ) {
				$metafile_dir = $this->file_handler->file_manager->upload_dir;
				return "$metafile_dir/{$filename}";
		}

		/**
		 * Build date range.
		 *
		 * @param array $log_files List of log files.
		 */
		protected function build_date_range( $log_files ) {
				$log_values  = array_values( $log_files );
				$newest_file = reset( $log_files );
				$oldest_file = end( $log_values );
				$date_end    = $this->file_handler->get_date_from_url( $newest_file );
				$date_start  = $this->file_handler->get_date_from_url( $oldest_file );
				return "$date_start-$date_end";
		}
}
