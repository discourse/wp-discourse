<?php
/**
 * Class \Test\Shared
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use WPDiscourse\Test\Logging;
use WPDiscourse\Test\RemotePost;

/**
 * Base class for WPDiscourse unit tests
 */
class UnitTest extends \WP_UnitTestCase {
  use Logging;
  use RemotePost;

  /**
   * Plugin options.
   *
   * @access public
   * @var object
   */
  public static $plugin_options;

  /**
   * Params used in remote posts.
   *
   * @access public
   * @var object
   */
  public static $remote_post_params;

  /**
   * URL of mock discourse instance.
   *
   * @access public
   * @var string
   */
  public static $discourse_url;

  /**
   * Setup test class
   */
  public static function setUpBeforeClass() {
      self::initialize_shared_variables();
  }

  /**
   * Teardown each test.
   */
  public function setUp() {
  }

  /**
   * Teardown each test.
   */
  public function tearDown() {
      $this->clear_logs();
      remove_all_filters( 'pre_http_request' );
      \Mockery::close();
  }

  /**
   * Initialize shared tests.
   */
  public static function initialize_shared_variables() {
      self::$discourse_url = 'http://meta.discourse.org';
      self::$plugin_options = array(
          'url'                => self::$discourse_url,
          'api-key'            => '1235567',
          'publish-username'   => 'angus'
      );
  }
}