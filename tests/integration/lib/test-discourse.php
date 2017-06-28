<?php

namespace WPDiscourse\Discourse;

class TestDiscourse extends \WP_UnitTestCase {

	protected $discourse;

	public function setUp() {
		parent::setUp();

		$this->discourse = new Discourse();
	}

	public function test_wpdc_discourse_domain_option_is_updated() {
		$discourse_connect = array(
			'url'              => 'http://forum.example.com',
			'api-key'          => 'testapikey',
			'publish-username' => 'admin_tester',
		);

		update_option( 'discourse_connect', $discourse_connect );

		$this->discourse->initialize_plugin();

		$wpdc_discourse_domain = get_option( 'wpdc_discourse_domain', false );

		$this->assertEquals( 'forum.example.com', $wpdc_discourse_domain );
	}

	public function test_discourse_sso_option_is_removed() {
		$discourse_sso = array(
			'sso-secret'             => 'thisisatest',
			'enable-sso'             => 1,
			'redirect-without-login' => 1,
		);

		update_option( 'discourse_sso', $discourse_sso );

		$this->discourse->initialize_plugin();

		$this->assertFalse( get_option( 'discourse_sso' ) );
	}

	public function test_sso_options_are_transferred() {
		$discourse_sso = array(
			'sso-secret'             => 'thisisatest',
			'enable-sso'             => 1,
			'redirect-without-login' => 1,
		);

		update_option( 'discourse_sso', $discourse_sso );

		delete_option( 'discourse_sso_common' );

		$this->discourse->initialize_plugin();

		$discourse_sso_common = get_option( 'discourse_sso_common' );

		$this->assertEquals( 'thisisatest', $discourse_sso_common['sso-secret'] );
	}

	public function test_saved_options_are_not_overwritten() {
		$discourse_connect = array(
			'url'              => 'http://forum.example.com',
			'api-key'          => 'testapikey',
			'publish-username' => 'admin_tester',
		);

		update_option( 'discourse_connect', $discourse_connect );

		$this->discourse->initialize_plugin();

		$this->assertEquals( $discourse_connect, get_option( 'discourse_connect' ) );
	}

	public function test_new_default_values_can_be_added_to_discourse_configurable_text_options() {
		$discourse_configurable_text = array(
			'discourse-link-text'         => 'http://forum.example.com',
			'start-discussion-text'       => 'Start the discussion!',
			'continue-discussion-text'    => 'Join the discussion',
			'notable-replies-text'        => 'Notable Replies',
			'comments-not-available-text' => 'Comments are not currently available for this post.',
			'participants-text'           => 'Participants',
			'published-at-text'           => 'Originally published at:',
			'single-reply-text'           => 'Reply',
			'many-replies-text'           => 'Replies',
			'more-replies-more-text'      => 'more',
		);

		update_option( 'discourse_configurable_text', $discourse_configurable_text );

		$this->discourse->initialize_plugin();

		$discourse_configurable_text = get_option( 'discourse_configurable_text' );

		$this->assertEquals( 'http://forum.example.com', $discourse_configurable_text['discourse-link-text'] );
		$this->assertEquals( 'Link your account to Discourse', $discourse_configurable_text['link-to-discourse-text'] );
	}

	public function test_discourse_version_option_is_set() {
		$this->assertEquals( WPDISCOURSE_VERSION, get_option( 'discourse_version', false ) );
	}

	public function test_discourse_domain_is_added_to_allowed_redirect_hosts() {
		$discourse_connect = array(
			'url'              => 'http://forum.example.com',
			'api-key'          => 'testapikey',
			'publish-username' => 'admin_tester',
		);

		update_option( 'discourse_connect', $discourse_connect );

		$this->discourse->initialize_plugin();

		$hosts = $this->discourse->allow_discourse_redirect( array() );

		$this->assertTrue( is_array( $hosts ) );
		$this->assertContains( 'forum.example.com', $hosts );
	}
}
