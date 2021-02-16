<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

require_once WPDISCOURSE_PATH . 'lib/discourse-publish.php';

use \WPDiscourse\EmailNotification\EmailNotification;
use \WPDiscourse\DiscoursePublish\DiscoursePublish;
use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\FileHandler;

/**
 * DiscoursePublish test case.
 */
class DiscoursePublishTest extends WP_UnitTestCase {
  
  /*
   * Remote post variables
   */
  public static $discourse_url;
  public static $success_response;
  public static $forbidden_response;
  public static $unprocessable_response;
  public static $failed_to_connect_response;
  public static $remote_post_options;
  public static $remote_post_params;
  
  /*
   * WP_Post attributes
   */
  public static $post_atts;
  
  /*
   * Plugin options
   */
  public static $plugin_options;
  
  /*
   * Instance of DiscoursePublish
   */
  protected $publish;
  
  /*
   * Setup test class
   */
  public static function setUpBeforeClass() {
    self::initialize_static_variables();
  }
  
  /*
   * Setup test
   */
  public function setUp() {
    $register_actions = false;
    $this->publish = new DiscoursePublish( new EmailNotification(), $register_actions );
    $this->publish->setup_logger();
    $this->publish->setup_options( self::$plugin_options );
	}
  
  /*
   * publish_post_after_save handles new posts correctly
   */
  public function test_publish_post_after_save_when_creating() {
    // Set up a response body for creating a new post
    $body = $this->mock_remote_post_success_body( "create_post_response_body" );
    $discourse_post_id = $body->id;
    $discourse_topic_id = $body->topic_id;
    $discourse_permalink = self::$discourse_url . '/t/' . $body->topic_slug . '/' . $body->topic_id;
    $discourse_category = self::$post_atts['meta_input']['publish_post_category'];
    
    // Add the post
    $post_id = wp_insert_post( self::$post_atts, false, false );
    
    // Run the publication
    $this->publish->publish_post_after_save( $post_id, get_post( $post_id ) );
    
    // Ensure the right post meta is created
    $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
    $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
    $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
    $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
    $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );
    
    // cleanup
    wp_delete_post( $post_id );
  }
  
  /*
   * publish_post_after_save handles post updates correctly
   */
  public function test_publish_post_after_save_when_updating() {
    // Set up a response body for updating an existing post
    $body = $this->mock_remote_post_success_body( "update_post_response_body" );
    $post = $body->post;
    
    $discourse_post_id = $post->id;
    $discourse_topic_id = $post->topic_id;
    $discourse_permalink = self::$discourse_url . '/t/' . $post->topic_slug . '/' . $post->topic_id;
    $discourse_category = self::$post_atts['meta_input']['publish_post_category'];
        
    // Add a post that's already been published to Discourse
    $post_atts = self::$post_atts;
    $post_atts['meta_input']['discourse_post_id'] = $discourse_post_id;
    $post_id = wp_insert_post( $post_atts, false, false );
    
    // Run the update
    update_post_meta( $post_id, 'update_discourse_topic', 1 );
    $this->publish->publish_post_after_save( $post_id, get_post( $post_id ) );
    
    // Ensure the right post meta still exists
    $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
    $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
    $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
    $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
    $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );
    
    // Cleanup
    wp_delete_post( $post_id );
  }
  
  /* 
   * Successful remote post request returns original response
   */
  public function test_remote_post_success() {
    $this->mock_remote_post_return( self::$success_response );
    $response = $this->publish->remote_post( ...self::$remote_post_params );
    $this->assertEquals( $response, self::$success_response );
  }
  
  /* 
   * Forbidden remote post request returns standardised WP_Error and creates correct log
   */
  public function test_remote_post_forbidden() {
    $this->mock_remote_post_return( self::$forbidden_response );
    
    $response = $this->publish->remote_post( ...self::$remote_post_params );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post Forbidden/', $log );
    $this->assertRegExp('/http_code":403/', $log);
  }
  
  /* 
   * Unprocessable remote post request returns standardised WP_Error and creates correct log
   */
  public function test_remote_post_unprocessable() {
    $this->mock_remote_post_return( self::$unprocessable_response );
    
    $response = $this->publish->remote_post( ...self::$remote_post_params );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post Title seems unclear, most of the words contain the same letters over and over?/', $log);
    $this->assertRegExp('/http_code":422/', $log);
  }
  
  /* 
   * Forbidden remote post request returns standardised WP_Error and creates correct log
   */
  public function test_remote_post_failed_to_connect() {
    $this->mock_remote_post_return( self::$failed_to_connect_response );
    
    $response = $this->publish->remote_post( ...self::$remote_post_params );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post cURL error 7: Failed to connect to localhost port 3000: Connection refused/', $log );
    $this->assertRegExp('/http_code":null/', $log);
  }
  
  public function tearDown() {
    $this->clear_logs();
    remove_all_filters( 'pre_http_request' );
  }
  
  protected function mock_remote_post_return( $response ) {
    add_filter( 'pre_http_request', function() use( $response ) {
      return $response;
    } );
  }
  
  protected function mock_remote_post_success_body( $body_json_file ) {
    $response = self::$success_response;
    $body = json_decode(file_get_contents( __DIR__ . "/fixtures/$body_json_file.json" ));
    $response['body'] = json_encode( $body );
    $this->mock_remote_post_return( $response );
    return $body;
  }
  
  protected function standardised_error( $type ) {
    return new WP_Error( 'discourse_publishing_response_error', $this->publish::ERROR_MESSAGES[ $type ] );
  }
  
  protected function get_last_log() {
		$manager = new FileManager();
		$log_files = glob( $manager->logs_dir . "/*.log" );		
		$log_file = $log_files[0];
    return `tail -n 1 $log_file`;
  }
  
  private function clear_logs() {
		$manager = new FileManager();
		$log_files = glob( $manager->logs_dir . "/*.log" );
		
		foreach( $log_files as $file ){
		  if ( is_file( $file ) ) {
		    unlink( $file );
		  }
		}
	}
  
  public static function initialize_static_variables() {
    self::$discourse_url = "http://meta.discourse.org";
    
    self::$success_response = array(
      'headers'   => array(),
      'body'      => "{}",
      'response'  => array(
        'code'      => 200,
        'message'   => 'OK',
      )
    );

    self::$forbidden_response = array(
      'headers'   => array(),
      'body'      => 'You are not permitted to view the requested resource. The API username or key is invalid.',
      'response'  => array(
        'code'      => 403,
        'message'   => 'Forbidden',
      )
    );

    self::$unprocessable_response = array(
      'headers'   => array(),
      'body'      => json_encode(array( "action" => "create_post", "errors" => ["Title seems unclear, most of the words contain the same letters over and over?"])),
      'response'  => array(
        'code'      => 422,
        'message'   => 'Unprocessable Entity',
      )
    );

    self::$failed_to_connect_response = new WP_Error(
      'http_request_failed',
      'cURL error 7: Failed to connect to localhost port 3000: Connection refused'
    );

    self::$remote_post_options = array(
      'timeout' => 30,
      'method'  => 'POST',
      'headers' => array(
        'Api-Key'      => '1234',
        'Api-Username' => 'angus',
      ),
      'body'    => http_build_query( array(
        'embed_url'        => 'https://wordpress.org/post.php',
        'featured_link'    => null,
        'title'            => 'New Topic Title',
        'raw'              => 'Post content',
        'category'         => 3,
        'skip_validations' => 'true',
        'auto_track'       => 'false',
        'visible'          => 'true',
      ) )
    );
    
    self::$remote_post_params = array(
      self::$discourse_url,
      self::$remote_post_options,
      "create_post",
      1 // dummy post_id
    );
        
    self::$post_atts = array(
      'post_author'   => 0,
      'post_content'  => 'This is a new post',
      'post_title'    => 'This is the post title',
      'meta_input'    => array(
        "wpdc_auto_publish_overridden"  => 0,
        "publish_to_discourse"          => 1,
        "publish_post_category"         => 1
      ),
      'post_status'   => 'publish'
    );
    
    self::$plugin_options = array(
      'url' => self::$discourse_url,
      'api-key' => '1235567',
      'publish-username' => 'angus',
      'allowed_post_types' => array( 'post' )
    );
  }
}
		