<?php
/**
 * Base WPDiscourse Class
 *
 * @package WPDiscourse
 */

namespace WPDiscourse;

use WPDiscourse\Shared\PluginUtilities;
use WPDiscourse\Logs\Logger;

/**
 * Class DiscourseComment
 */
class DiscourseBase {
	use PluginUtilities;

  /**
   * Gives access to the plugin options.
   *
   * @access protected
   * @var mixed|void
   */
	protected $options;

  /**
   * Instance of Logger
   *
   * @access protected
   * @var \WPDiscourse\Logs\Logger
   */
  protected $logger;

  /**
   * Logger context
   *
   * @access protected
   * @var string
   */
  protected $logger_context = 'base';

  /**
   * Setup options.
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
   * Setup Logger for the context.
   */
  public function setup_logger() {
		$this->logger = Logger::create( $this->logger_context, $this->options );
  }
}
