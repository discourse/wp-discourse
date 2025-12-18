<?php
/**
 * Class WPNewUserNotificationTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use WPDiscourse\Test\UnitTest;

/**
 * WP New User Notification test case.
 */
class WPNewUserNotificationTest extends UnitTest {

	/**
	 * User id
	 *
	 * @access protected
	 * @var int
	 */
	protected $user_id;

	public function setUp(): void {
		parent::setUp();

		update_option( 'discourse_sso_provider', array( 'enable-sso' => 1 ) );

		$this->user_id = self::factory()->user->create();
	}

	public function tearDown(): void {
		reset_phpmailer_instance();
	}

	public function test_send_wp_new_user_notification() {
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );

		wp_new_user_notification( $this->user_id, null, 'user' );

		$this->assertNotEmpty( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	/**
	 * Test that wp_send_new_user_notification_to_user filter is respected.
	 */
	public function test_disable_wp_new_user_notification() {
		add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );

		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );

		wp_new_user_notification( $this->user_id, null, 'user' );

		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );

		remove_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
	}
}
