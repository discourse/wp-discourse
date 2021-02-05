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
  
  protected $success_response;
  
  protected $forbidden_response;
  
  protected $unprocessable_response;
  
  protected $failed_to_connect_response;
  
  protected $post_options;
  
  protected $publish;
  
  public function setUp() {
    $this->publish = new DiscoursePublish( new EmailNotification() );
    $this->publish->setup_logger();
    $this->publish->post = new \WP_Post( (object) array( 'ID' => 1 ) );
    $this->build_responses();
	}
  
  /* 
   * Successful request returns original response
   */
  public function test_remote_post_success() {
    $this->mock_remote_post_return( $this->success_response );
    $response = $this->publish->remote_post(
      'https://meta.discourse.org',
      $this->post_options,
      "create_post"
    );
    $this->assertEquals( $response, $this->success_response );
  }
  
  /* 
   * Forbidden request returns standardised WP_Error and creates correct log
   */
  public function test_remote_post_forbidden() {
    $this->mock_remote_post_return( $this->forbidden_response );
    
    $response = $this->publish->remote_post(
      'https://meta.discourse.org',
      $this->post_options,
      "create_post"
    );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post Forbidden/', $log );
    $this->assertRegExp('/http_code":403/', $log);
  }
  
  /* 
   * Unprocessable request returns standardised WP_Error and creates correct log
   */
  public function test_remote_post_unprocessable() {
    $this->mock_remote_post_return( $this->unprocessable_response );
    
    $response = $this->publish->remote_post(
      'https://meta.discourse.org',
      $this->post_options,
      "create_post"
    );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post Title seems unclear, most of the words contain the same letters over and over?/', $log);
    $this->assertRegExp('/http_code":422/', $log);
  }
  
  /* 
   * Forbidden request returns standardised WP_Error and creates correct log
   */
  public function test_remote_failed_to_connect() {
    $this->mock_remote_post_return( $this->failed_to_connect_response );
    
    $response = $this->publish->remote_post(
      'https://meta.discourse.org',
      $this->post_options,
      "create_post"
    );
    $this->assertEquals( $response, $this->standardised_error( "create_post" ) );
    
    $log = $this->get_last_log();
    $this->assertRegExp('/publish.ERROR: create_post cURL error 7: Failed to connect to localhost port 3000: Connection refused/', $log );
    $this->assertRegExp('/http_code":null/', $log);
  }
  
  public function tearDown() {
    $this->clear_logs();
  }
  
  protected function mock_remote_post_return( $response ) {
    add_filter( 'pre_http_request', function() use( $response ) {
      return $response;
    } );
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
  
  protected function build_responses() {
    $this->success_response = array(
      'headers'   => array(),
      'body'      => json_decode(
        file_get_contents( __DIR__ . "/fixtures/create_post_response.json" ), true
      ),
      'response'  => array(
        'code'      => 200,
        'message'   => 'OK',
      )
    );

    $this->forbidden_response = array(
      'headers'   => array(),
      'body'      => 'You are not permitted to view the requested resource. The API username or key is invalid.',
      'response'  => array(
        'code'      => 403,
        'message'   => 'Forbidden',
      )
    );

    $this->unprocessable_response = array(
      'headers'   => array(),
      'body'      => json_encode(array( "action" => "create_post", "errors" => ["Title seems unclear, most of the words contain the same letters over and over?"])),
      'response'  => array(
        'code'      => 422,
        'message'   => 'Unprocessable Entity',
      )
    );

    $this->failed_to_connect_response = new WP_Error(
      'http_request_failed',
      'cURL error 7: Failed to connect to localhost port 3000: Connection refused'
    );

    $this->post_options = array(
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
}
		