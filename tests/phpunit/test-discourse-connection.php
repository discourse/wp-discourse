<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\Test\UnitTest;
use \WPDiscourse\Admin\FormHelper;

/**
 * DiscourseComment test case.
 */
class DiscourseConnectionTest extends UnitTest {

  /**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

  /**
   * Setup each test.
   */
  public function setUp() {
    parent::setUp();
    $this->form_helper = FormHelper::get_instance();
    self::$plugin_options['connection-logs'] = 1;
    $this->form_helper->setup_options( self::$plugin_options );
  }

  /**
   * check_connection_status checks discourse connection and api key scopes.
   */
  public function test_check_connection_status() {
    $this->mock_remote_post_success( "scopes" );
    $result = $this->form_helper->check_connection_status();
    $this->assertTrue( $result );
  }

  /**
   * check_connection_status fails when connection fails.
   */
  public function test_check_connection_status_response_error() {
    $response = $this->build_response( 'not_found' );
    $this->mock_remote_post( $response );

    $result = $this->form_helper->check_connection_status();
    $this->assertFalse( $result );

    $log = $this->get_last_log();
    $this->assertRegExp( '/connection.INFO: check_connection_status.failed_to_connect/', $log );
  }

  /**
   * check_connection_status fails when scopes are invalid.
   */
  public function test_check_connection_status_scopes_invalid() {
    self::$plugin_options['enable-discourse-comments'] = 1;
    $this->form_helper->setup_options( self::$plugin_options );

    $raw_body  = $this->response_body_json( 'scopes' );
    $body = json_decode( $raw_body );
    $scopes = array_filter( $body->scopes, function( $scope ) { return $scope->key !== 'commenting'; });
    $response = $this->build_response( 'success' );
    $response['body'] = json_encode( array( "scopes" => array_values( $scopes ) ) );
    $this->mock_remote_post( $response );

    $result = $this->form_helper->check_connection_status();
    $this->assertFalse( $result );

    $log = $this->get_last_log();
    $this->assertRegExp( '/connection.INFO: check_connection_status.invalid_scopes/', $log );

    self::$plugin_options['enable-discourse-comments'] = 0;
    $this->form_helper->setup_options( self::$plugin_options );

    $result = $this->form_helper->check_connection_status();
    $this->assertTrue( $result );

    $log = $this->get_last_log();
    $this->assertRegExp( '/connection.INFO: check_connection_status.valid_scopes/', $log );
  }
}

