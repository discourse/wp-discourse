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
		  $this->form_helper                       = FormHelper::get_instance();
		  self::$plugin_options['connection-logs'] = 1;
		  $this->form_helper->setup_options( self::$plugin_options );
		}

		/**
		 * check_connection_status checks discourse connection and api key scopes.
		 */
		public function test_check_connection_status() {
		  $this->mock_remote_post_success( 'scopes' );
		  $result = $this->form_helper->check_connection_status();
		  $this->assertTrue( $result );
		}

		/**
		 * check_connection_status checks discourse connection when discourse < 2.9.0.beta5 .
		 */
		public function test_check_connection_status_legacy() {
			$first_request         = array(
				'url'      => '/session/scopes.json',
				'method'   => 'GET',
				'response' => $this->build_response( 'not_found' ),
			);
			$user_response         = $this->build_response( 'success' );
			$user_response['body'] = $this->response_body_json( 'user' );
			$username              = self::$connection_options['publish-username'];
			$second_request        = array(
				'url'      => "/users/$username.json",
				'method'   => 'GET',
				'response' => $user_response,
			);
			$this->mock_remote_post( $first_request, $second_request );
		  $result = $this->form_helper->check_connection_status();
		  $this->assertTrue( $result );
		}

		/**
		 * check_connection_status fails when connection fails.
		 */
		public function test_check_connection_status_response_error() {
			$request = array(
				'response' => $this->build_response( 'forbidden' ),
				'method'   => 'GET',
			);
		  $this->mock_remote_post( $request );

		  $result = $this->form_helper->check_connection_status();
		  $this->assertFalse( $result );

		  $log = $this->get_last_log();
		  $this->assertRegExp( '/connection.INFO: check_connection_status.failed_to_connect/', $log );
		}

		/**
		 * check_connection_status fails when connection fails with discourse < 2.9.0.beta5
		 */
		public function test_check_connection_status_response_error_legacy() {
			$request        = array(
				'response' => $this->build_response( 'not_found' ),
				'method'   => 'GET',
			);
			$username       = self::$connection_options['publish-username'];
			$second_request = array(
				'url'      => "/users/$username.json",
				'response' => $this->build_response( 'forbidden' ),
				'method'   => 'GET',
			);
		  $this->mock_remote_post( $request, $second_request );

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

		  $raw_body         = $this->response_body_json( 'scopes' );
		  $body             = json_decode( $raw_body );
			$scopes         = array_filter(
		 		$body->scopes, function( $scope ) {
					return 'commenting' !== $scope->key;
				}
			);
		  $response         = $this->build_response( 'success' );
		  $response['body'] = json_encode( array( 'scopes' => array_values( $scopes ) ) );
			$request        = array(
				'response' => $response,
				'method'   => 'GET',
			);
		  $this->mock_remote_post( $request );

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
