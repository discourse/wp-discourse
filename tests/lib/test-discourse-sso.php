<?php

require_once( __DIR__ . '/../../lib/discourse-sso.php' );


class TestDiscourseSSO extends WP_UnitTestCase {
	
	protected $discourse_sso;

	public function setUp() {
		$this->discourse_sso = new \WPDiscourse\DiscourseSSO\DiscourseSSO();
		$options = array(
			'enable-sso' => 1,
			'url' => 'http://forum.example.com',
		);
		update_option( 'discourse', $options );
	}

	public function test_constructor_hooks_into_correct_filter_and_actions() {
		$this->assertEquals( 10, has_filter( 'query_vars', array( $this->discourse_sso, 'sso_add_query_vars' ) ) );
		$this->assertEquals( 10, has_filter( 'login_url', array( $this->discourse_sso, 'set_login_url' ) ) );
		$this->assertEquals( 10, has_action( 'parse_query', array( $this->discourse_sso, 'sso_parse_request' ) ) );
	}

	public function test_set_login_url_returns_wp_login_url_if_login_path_not_set() {
		$options = array(
			'login-path' => '',
		);
		update_option( 'discourse', $options );

		$wp_login = 'wp-login.php';
		$redirect = '/';

		$returned_path = explode( '?', $this->discourse_sso->set_login_url( $wp_login, $redirect ) )[0];

		$this->assertEquals( $wp_login, $returned_path );
	}

	public function test_set_login_url_returns_supplied_login_path() {
		$options = array(
			'login-path' => '/',
		);
		update_option( 'discourse', $options );

		$wp_login = 'wp-login.php';
		$redirect = '/welcome';

		$returned_path = explode( '?', $this->discourse_sso->set_login_url( $wp_login, $redirect ) )[0];

		$this->assertEquals( get_option( 'discourse' )['login-path'], $returned_path );
	}
}