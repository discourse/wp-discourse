<?php

require_once( __DIR__ . '/../../lib/discourse.php' );

class TestDiscourse extends WP_UnitTestCase {
	protected $discourse;
	
	public function setUp() {
		$this->discourse = new \WPDiscourse\Discourse\Discourse();
	}
	
	public function test_constructor_hooks_into_correct_filters_and_actions() {
		$this->assertEquals( 10, has_filter( 'user_contactmethods', array( $this->discourse, 'extend_user_profile' ) ) );
	}
}