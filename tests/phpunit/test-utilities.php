<?php
/**
 * Class UtilitiesTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use WPDiscourse\Utilities\Utilities;

/**
 * SyncDiscourseUser test case.
 */
class UtilitiesTest extends UnitTest {

  public function setUp() {
		$connection_options = get_option( 'discourse_connect' );
		$connection_options = array_merge( $connection_options, self::$connection_options );
		update_option( 'discourse_connect', $connection_options );

		$publish_options = get_option( 'discourse_publish' );
		$publish_options = array_merge( $publish_options, self::$publish_options );
		update_option( 'discourse_publish', $publish_options );

		$this->user_id = self::factory()->user->create();
  }

  /**
   * get_options returns the plugin options.
   */
  public function test_get_options() {
		$options = Utilities::get_options();
		$this->assertTrue( array_key_exists( 'api-key', $options ) );
  }

  /**
   * validate validates http respsonses.
   */
  public function test_validate() {
		$this->assertEquals( Utilities::validate( null ), 0 );
		$this->assertEquals( Utilities::validate( new \WP_Error( 'test_error', 'Test' ) ), 0 );
		$this->assertEquals( Utilities::validate( $this->build_response( 'forbidden' ) ), 0 );
		$this->assertEquals( Utilities::validate( $this->build_response( 'success' ) ), 1 );
  }

  /**
   * get_discourse_categories gets the discourse categories.
   */
  public function test_get_discourse_categories() {
		$this->mock_remote_post_success( 'site' );
		$categories = Utilities::get_discourse_categories();
		$this->assertCount( 6, $categories );
  }

  /**
   * get_discourse_user gets the discourse user.
   */
  public function test_get_discourse_user() {
		$this->mock_remote_post_success( 'user' );
		$discourse_user = Utilities::get_discourse_user( 1 );
		$this->assertEquals( $discourse_user->username, 'angus' );
  }

  /**
   * get_discourse_user_by_email gets the discourse user by email.
   */
  public function test_get_discourse_user_by_email() {
		$this->mock_remote_post_success( 'users' );
		$discourse_user = Utilities::get_discourse_user_by_email( 'angus@test.com' );
		$this->assertEquals( $discourse_user->username, 'angus' );
  }

  /**
   * sync_sso_record syncs the sso record.
   */
  public function test_sync_sso_record() {
		update_option( 'discourse_sso_common', array( 'sso-secret' => '12345678910' ) );
		update_option( 'discourse_sso_provider', array( 'enable-sso' => 1 ) );

		$this->mock_remote_post_success( 'sync_sso', 'POST' );
		$user = get_user_by( 'id', $this->user_id );
		Utilities::sync_sso_record( Utilities::get_sso_params( $user ) );
		$this->assertEquals( get_user_meta( $this->user_id, 'discourse_username', true ), 'angus' );
  }

  /**
   * get_sso_params gets the sso params.
   */
  public function test_get_sso_params() {
		$user       = get_user_by( 'id', $this->user_id );
		$sso_params = Utilities::get_sso_params( $user );
		$this->assertTrue( array_key_exists( 'username', $sso_params ) );
  }

  /**
   * verify_discourse_webhook_request verifies a webhook request.
   */
  public function test_verify_discourse_webhook_request() {
		$webhook_secret = '1234567891011';
		update_option( 'discourse_webhook', array( 'webhook-secret' => $webhook_secret ) );

		$payload   = $this->response_body_file( 'webhook_post' );
		$signature = hash_hmac( 'sha256', $payload, $webhook_secret );
		$request   = new \WP_REST_Request();
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'X-Discourse-Event-Signature', "sha256={$signature}" );
		$request->set_body( $payload );

		$response = Utilities::verify_discourse_webhook_request( $request );
		$this->assertEquals( get_class( $response ), 'WP_REST_Request' );
  }

  /**
   * get_discourse_groups gets non-automatic discourse groups.
   */
  public function test_get_discourse_groups() {
		$this->mock_remote_post_success( 'groups' );
		$groups = Utilities::get_discourse_groups();
		$this->assertCount( 2, $groups );
  }

  /**
   * create_discourse_user creates a Discourse user.
   */
  public function test_create_discourse_user() {
		$this->mock_remote_post_success( 'user_create', 'POST' );
		$user     = get_user_by( 'id', $this->user_id );
		$response = Utilities::create_discourse_user( $user );
		$this->assertEquals( $response, 1 );
  }

  /**
   * add_user_to_discourse_group adds a user to a Discourse group.
   */
  public function test_add_user_to_discourse_group() {
		update_option( 'discourse_sso_common', array( 'sso-secret' => '12345678910' ) );
		update_option( 'discourse_sso_provider', array( 'enable-sso' => 1 ) );

		$this->mock_remote_post_success( 'sync_sso', 'POST' );
		$response = Utilities::add_user_to_discourse_group( $this->user_id, 'test_group' );
		$this->assertTrue( $response );
  }

  /**
   * remove_user_from_discourse_group removes a user from a Discourse group.
   */
  public function test_remove_user_from_discourse_group() {
		update_option( 'discourse_sso_common', array( 'sso-secret' => '12345678910' ) );
		update_option( 'discourse_sso_provider', array( 'enable-sso' => 1 ) );

		$this->mock_remote_post_success( 'sync_sso', 'POST' );
		$response = Utilities::remove_user_from_discourse_group( $this->user_id, 'test_group' );
		$this->assertTrue( $response );
  }

  /**
   * get_discourse_comments gets discourse comment html.
   * test would be exect duplicate of test-discourse-comment-formatter test_format.
   */

  /**
   * discourse_request performs a discourse request.
   * test would be duplicative of other tests.
   */
}
