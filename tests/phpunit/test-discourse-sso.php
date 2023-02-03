<?php
/**
 * Class DiscourseSSOTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\DiscourseSSO\DiscourseSSO;
use \WPDiscourse\Test\UnitTest;

/**
 * DiscourseSSO test case.
 */
class DiscourseSSOTest extends UnitTest {

  public function setUp() {
		$this->secret = 'secret';
		$this->nonce = 'abcd';
		$this->payload = base64_encode( "nonce={$this->nonce}" );
		$this->signature = hash_hmac( 'sha256', $this->payload, $this->secret );

		self::$plugin_options['sso-secret'] = $this->secret;

		$this->discourse_sso = \Mockery::mock( DiscourseSSO::class )->makePartial();
		$this->discourse_sso->shouldReceive( 'redirect_to' )->andReturnArg( 0 );
		$this->discourse_sso->setup_options( array_merge( self::$plugin_options, array( 'enable-sso' => true ) ) );
		$this->discourse_sso->setup_logger();

		$this->query_vars = array(
			'sso' => $this->payload,
			'sig' => rawurlencode( $this->signature ),
		);
		$this->user_id = self::factory()->user->create();
  }

  public function tearDown() {
		parent::tearDown();

		$_GET['request'] = null;
		delete_user_meta( $this->user_id, 'discourse_sso_user_id', true );
  }

  /**
   * sso_parse_request without a user redirects to login with correct redirect_to url.
   *
   */
  public function test_sso_parse_request_no_user() {
		$parse_result = $this->discourse_sso->sso_parse_request( (object) array( 'query_vars' => $this->query_vars ) );
		$wp_redirect_url = add_query_arg( $this->payload, rawurlencode( $this->signature ) );
		$wp_login_url = wp_login_url( esc_url_raw( $wp_redirect_url ) );

		$this->assertEquals( $parse_result, $wp_login_url );
  }

  /**
   * sso_parse_request with a user validates payload and redirects to Discourse.
   *
   */
  public function test_sso_parse_request_user() {
		$user = wp_set_current_user( $this->user_id );
		$parse_result = $this->discourse_sso->sso_parse_request( (object) array( 'query_vars' => $this->query_vars ) );

		$params = array_merge( $this->discourse_sso->get_sso_params( $user ), array( 'nonce' => $this->nonce ) );
		$payload = base64_encode( http_build_query( $params ) );
		$signature = hash_hmac( 'sha256', $payload, $this->secret );
		$query_vars = array(
			'sso' => $payload,
			'sig' => $signature,
		);
		$discourse_url = self::$plugin_options['url'] . '/session/sso_login?' . http_build_query( $query_vars );

		$this->assertEquals( $parse_result, $discourse_url );
  }

  /**
   * sso_parse_request handles logout requests from Discourse
   *
   */
  public function test_sso_parse_request_logout() {
		$user = wp_set_current_user( $this->user_id );

		$_GET['request'] = 'logout';
		$this->discourse_sso->sso_parse_request( (object) array( 'query_vars' => array() ) );

		$this->assertEquals( is_user_logged_in(), false );
  }

  /**
   * sso_parse_request handles invalid signature
   *
   */
  public function test_sso_parse_request_invalid_signature() {
		$user = wp_set_current_user( $this->user_id );

		$query_vars = array(
			'sso' => $this->query_vars['sso'],
			'sig' => 'i2v0a8l9i6d',
		);
		$parse_result = $this->discourse_sso->sso_parse_request( (object) array( 'query_vars' => $query_vars ) );

		$this->assertTrue( is_wp_error( $parse_result ) );
		$this->assertEquals( $parse_result->get_error_message(), 'SSO error' );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_provider.ERROR: parse_request.invalid_sso/', $log );
  }

  /**
   * sso_parse_request handles invalid nonce
   *
   */
  public function test_sso_parse_request_invalid_nonce() {
		$user = wp_set_current_user( $this->user_id );

		$payload = base64_encode( "not_nonce={$this->nonce}" );
		$query_vars = array(
			'sso' => $payload,
			'sig' => hash_hmac( 'sha256', $payload, $this->secret ),
		);
		$parse_result = $this->discourse_sso->sso_parse_request( (object) array( 'query_vars' => $query_vars ) );

		$error_message = 'Nonce not found in payload!';
		$this->assertTrue( is_wp_error( $parse_result ) );
		$this->assertEquals( $parse_result->get_error_message(), $error_message );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_provider.ERROR: parse_request.invalid_sso/', $log );
		$this->assertRegExp( '/"message":"' . $error_message . '"/', $log );
  }

  /**
   * logout_from_discourse logs out user from Discourse
   *
   */
  public function test_logout_from_discourse() {
		$user = wp_set_current_user( $this->user_id );

		$second_request = array(
			'url'      => 'log_out',
			'method'   => 'POST',
			'response' => $this->build_response( 'success' ),
		);
		$discourse_user = $this->mock_remote_post_success( 'user', 'GET', $second_request );

		$logout_result = $this->discourse_sso->logout_from_discourse();
		$this->assertTrue( $logout_result );

		$discourse_user_id = get_user_meta( $user->ID, 'discourse_sso_user_id', true );
		$this->assertEquals( $discourse_user_id, $discourse_user->user->id );
  }

  /**
   * logout_from_discourse handles failure to get Discourse user
   *
   */
  public function test_logout_from_discourse_failed_to_get_discourse_user() {
		$user = wp_set_current_user( $this->user_id );

		$request = array(
			'method'   => 'POST',
			'response' => $this->build_response( 'invalid_parameters' ),
		);
		$this->mock_remote_post( $request );

		$logout_result = $this->discourse_sso->logout_from_discourse();

		$error_message = 'The Discourse user_id could not be returned when trying to logout the user.';
		$this->assertTrue( is_wp_error( $logout_result ) );
		$this->assertEquals( $logout_result->get_error_message(), $error_message );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_provider.ERROR: logout.discourse_user/', $log );
		$this->assertRegExp( '/"message":"' . $error_message . '"/', $log );
  }

  /**
   * logout_from_discourse handles failure to logout
   *
   */
  public function test_logout_from_discourse_failed_to_logout() {
		$user = wp_set_current_user( $this->user_id );
		$response = $this->build_response( 'not_found' );
		$second_request = array(
			'url'      => 'log_out',
			'method'   => 'POST',
			'response' => $response,
		);
		$discourse_user = $this->mock_remote_post_success( 'user', 'GET', $second_request );

		$logout_result = $this->discourse_sso->logout_from_discourse();

		$error_message = 'There was an error in logging out the user from Discourse.';
		$this->assertTrue( is_wp_error( $logout_result ) );
		$this->assertEquals( $logout_result->get_error_message(), $error_message );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_provider.ERROR: logout.response_error/', $log );
		$this->assertRegExp( '/"message":"' . $error_message . '"/', $log );
  }
}
