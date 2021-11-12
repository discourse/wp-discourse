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
   * WP_Post attributes.
   *
   * @access public
   * @var object
   */
  public static $post_atts;

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
   * Setup each test.
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
          'publish-username'   => 'angus',
          'allowed_post_types' => array( 'post' )
      );
      self::$post_atts = array(
          'post_author'  => 0,
          'post_content' => 'This is a new post',
          'post_title'   => 'This is the post title',
          'meta_input'   => array(
      'wpdc_auto_publish_overridden' => 0,
      'publish_to_discourse'         => 1,
      'publish_post_category'        => 1,
          ),
          'post_status'  => 'publish',
      );
  }
}