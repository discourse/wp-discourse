<?php
/**
 * Class SyncDiscourseUserTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use WPDiscourse\SyncDiscourseUser\SyncDiscourseUser;
use \WPDiscourse\Test\UnitTest;

/**
 * SyncDiscourseUser test case.
 */
class SyncDiscourseUserTest extends UnitTest {

    /**
     * Instance of SyncDiscourseUser.
     *
     * @access protected
     * @var \WPDiscourse\SyncDiscourseUser\SyncDiscourseUser
     */
    protected $sync_user;

    /**
     * Setup each test.
     */
    public function setUp() {
      parent::setUp();

      self::$plugin_options['webhook-secret']             = '1234567891011';
      self::$plugin_options['use-discourse-user-webhook'] = 1;
      self::$plugin_options['enable-sso']                 = 1;

      $this->sync_user = new SyncDiscourseUser();
      $this->sync_user->setup_options( self::$plugin_options );
      $this->sync_user->setup_logger();

      $this->payload   = $this->response_body_file( 'webhook_user' );
      $this->signature = hash_hmac( 'sha256', $this->payload, self::$plugin_options['webhook-secret'] );

      $this->request = new \WP_REST_Request();
      $this->request->set_header( 'Content-Type', 'application/json' );
      $this->request->set_header( 'X-Discourse-Event-Signature', "sha256={$this->signature}" );
      $this->request->set_header( 'X-Discourse-Event-Type', 'user' );
      $this->request->set_header( 'X-Discourse-Event', 'user_updated' );
      $this->request->set_body( $this->payload );
  	}

    public function tearDown() {
      parent::tearDown();

      $payload = json_decode( $this->payload );
      delete_metadata( 'user', null, 'discourse_username', null, true );
      delete_metadata( 'user', null, 'discourse_sso_user_id', null, true );
    }

    /**
     * update_user handles webhook results correctly.
     */
    public function test_update_user() {
      // Setup the user
      $payload        = json_decode( $this->payload );
      $discourse_user = $payload->user;
      $user           = wp_set_current_user( $discourse_user->external_id );

      // Perform update
      $result = $this->sync_user->update_user( $this->request );

      // Ensure the post meta is updated correctly.
      $this->assertEquals( get_user_meta( $user->ID, 'discourse_username', true ), $discourse_user->username );
      $this->assertEquals( get_user_meta( $user->ID, 'discourse_sso_user_id', true ), $discourse_user->id );
    }

    /**
     * update_user handles webhook results correctly when using discourse_sso_user_id.
     */
    public function test_update_user_using_discourse_sso_user_id() {
      // Setup request
      $this->payload              = $this->response_body_file( 'webhook_user' );
      $payload                    = json_decode( $this->payload );
      $payload->user->external_id = null;
      $this->payload              = json_encode( $payload );
      $this->signature            = hash_hmac( 'sha256', $this->payload, self::$plugin_options['webhook-secret'] );
      $this->request->set_header( 'X-Discourse-Event-Signature', "sha256={$this->signature}" );
      $this->request->set_body( $this->payload );

      // Setup the user
      $user_id        = self::factory()->user->create();
      $discourse_user = $payload->user;
      $user           = wp_set_current_user( $user_id );
      add_user_meta( $user->ID, 'discourse_sso_user_id', $discourse_user->id, true );

      // Perform update
      $result = $this->sync_user->update_user( $this->request );

      // Ensure the post meta is updated correctly.
      $this->assertEquals( get_user_meta( $user->ID, 'discourse_username', true ), $discourse_user->username );
    }

    /**
     * update_user handles webhook results correctly when using email.
     */
    public function test_update_user_using_email() {
      // Setup options
      self::$plugin_options['webhook-match-user-email'] = 1;
      $this->sync_user->setup_options( self::$plugin_options );

      // Setup request
      $this->payload              = $this->response_body_file( 'webhook_user' );
      $payload                    = json_decode( $this->payload );
      $payload->user->external_id = null;
      $this->payload              = json_encode( $payload );
      $this->signature            = hash_hmac( 'sha256', $this->payload, self::$plugin_options['webhook-secret'] );
      $this->request->set_header( 'X-Discourse-Event-Signature', "sha256={$this->signature}" );
      $this->request->set_body( $this->payload );

      // Setup the user
      $user_id        = self::factory()->user->create( array( 'user_email' => $payload->user->email ) );
      $discourse_user = $payload->user;
      $user           = wp_set_current_user( $user_id );

      // Perform update
      $result = $this->sync_user->update_user( $this->request );

      // Ensure the post meta is updated correctly.
      $this->assertEquals( get_user_meta( $user->ID, 'discourse_username', true ), $discourse_user->username );
    }

    /**
     * update_user creates the right logs if unable to find user.
     */
    public function test_update_user_unable_to_find_user() {
      // Setup request
      $this->payload              = $this->response_body_file( 'webhook_user' );
      $payload                    = json_decode( $this->payload );
      $payload->user->external_id = 7;
      $this->payload              = json_encode( $payload );
      $this->signature            = hash_hmac( 'sha256', $this->payload, self::$plugin_options['webhook-secret'] );
      $this->request->set_header( 'X-Discourse-Event-Signature', "sha256={$this->signature}" );
      $this->request->set_body( $this->payload );

      // Setup the user
      $user_id        = self::factory()->user->create();
      $discourse_user = $payload->user;
      $user           = wp_set_current_user( $user_id );

      // Perform update
      $result = $this->sync_user->update_user( $this->request );

      // Ensure the right log is created.
      $log = $this->get_last_log();
      $this->assertRegExp( '/webhook_user.WARNING: update_user.user_not_found/', $log );
    }
}

