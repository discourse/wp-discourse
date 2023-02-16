<?php
/**
 * Class SSOClientTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\SSOClient\Client;
use \WPDiscourse\SSOClient\Nonce;
use \WPDiscourse\Test\UnitTest;

/**
 * SSOClient test case.
 */
class SSOClientTest extends UnitTest {

  public static function setUpBeforeClass() {
		parent::initialize_shared_variables();
		wp_logout();

		if ( version_compare( get_bloginfo( 'version' ), '5.3', '<' ) ) {
			// See https://core.trac.wordpress.org/ticket/35488
			wp_set_current_user( 0 );
			}
  }

  public function setUp() {
		parent::setUp();

		$this->discourse_user_id = 5;
		$this->user_id           = self::factory()->user->create();
		$this->secret            = 'secret';
		$this->nonce             = 'abcd';
		$this->query_args        = array(
			'admin'          => true,
			'moderator'      => false,
			'avatar_url'     => 'http://localhost:3000/uploads/default/original/1X/62dda77e6e54c07af7475f57efd151c9689cbd05.png',
			'email'          => 'angus@test.com',
			'external_id'    => "$this->discourse_user_id",
			'groups'         => 'trust_level_0,admins,staff,trust_level_1',
			'name'           => 'Angus McLeod',
			'username'       => 'angus',
			'nonce'          => Nonce::get_instance()->create( '_discourse_sso' ),
			'return_sso_url' => utf8_uri_encode( 'http://localhost:8888/' ),
		);
		$this->payload           = base64_encode( http_build_query( $this->query_args ) );
		$this->signature         = hash_hmac( 'sha256', $this->payload, $this->secret );

		self::$plugin_options['sso-secret']         = $this->secret;
		self::$plugin_options['sso-client-enabled'] = true;

		$this->sso_client = \Mockery::mock( Client::class )->makePartial();
		$this->sso_client->shouldReceive( 'redirect_to' )->andReturnArg( 0 );
		$this->sso_client->setup_options( self::$plugin_options );
		$this->sso_client->setup_logger();

		$_GET['sso'] = $this->payload;
		$_GET['sig'] = rawurlencode( $this->signature );
  }

  public function tearDown() {
		parent::tearDown();

		$_GET['sso'] = null;
		$_GET['sig'] = null;
		delete_metadata( 'user', null, 'discourse_username', null, true );
		delete_metadata( 'user', null, 'discourse_sso_user_id', null, true );
		wp_logout();

		if ( version_compare( get_bloginfo( 'version' ), '5.3', '<' ) ) {
			// See https://core.trac.wordpress.org/ticket/35488
			wp_set_current_user( 0 );
			}
  }

  /**
   * parse_request authenticates a user and updates their metadata correctly.
   */
  public function test_parse_request() {
		add_user_meta( $this->user_id, 'discourse_sso_user_id', $this->discourse_user_id );

		$parse_result = $this->sso_client->parse_request();

		$user = wp_get_current_user();
		$this->assertEquals( $user->ID, $this->user_id );
		$this->assertEquals( get_user_meta( $user->ID, 'discourse_username', true ), $this->query_args['username'] );
		$this->assertEquals( get_user_meta( $user->ID, 'discourse_sso_user_id', true ), $this->query_args['external_id'] );
		$this->assertEquals( $parse_result, $this->query_args['return_sso_url'] );
  }

  /**
   * parse_request handles invalid signatures correctly.
   */
  public function test_parse_request_invalid_signature() {
		$_GET['sig']  = rawurlencode( hash_hmac( 'sha256', $this->payload, 'wrong-secret' ) );
		$parse_result = $this->sso_client->parse_request();

		$user = wp_get_current_user();
		$this->assertNotEquals( $user->ID, $this->user_id );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_client.ERROR: parse_request.invalid_signature/', $log );
  }

  /**
   * parse_request handles failure to get_user_id correctly
   */
  public function test_parse_request_get_user_failed() {
		$this->query_args['username'] = '';
		$this->query_args['email']    = '';
		$this->payload                = base64_encode( http_build_query( $this->query_args ) );
		$_GET['sso']                  = $this->payload;
		$_GET['sig']                  = rawurlencode( hash_hmac( 'sha256', $this->payload, $this->secret ) );

		$parse_result = $this->sso_client->parse_request();

		$user = wp_get_current_user();
		$this->assertNotEquals( $user->ID, $this->user_id );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_client.ERROR: parse_request.get_user_id/', $log );
  }

  /**
   * parse_request handles failure to update_user correctly
   */
  public function test_parse_request_update_user_failed() {
		add_filter( 'wpdc_sso_client_updated_user', array( $this, 'invalid_update_user_filter' ), 10, 2 );

		$parse_result = $this->sso_client->parse_request();

		$user = wp_get_current_user();
		$this->assertNotEquals( $user->ID, $this->user_id );

		$log = $this->get_last_log();
		$this->assertRegExp( '/sso_client.ERROR: parse_request.update_user/', $log );

		remove_filter( 'wpdc_sso_client_updated_user', array( $this, 'invalid_update_user_filter' ), 10 );
  }

  public function invalid_update_user_filter( $updated_user, $query ) {
		$updated_user['ID'] = 23;
		return $updated_user;
  }
}
