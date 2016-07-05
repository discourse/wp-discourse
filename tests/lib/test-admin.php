<?php

require_once( __DIR__ . '/../../lib/admin.php' );

class TestAdmin extends WP_UnitTestCase {
	protected $admin;
	
	public function setUp() {
		$this->admin = new \WPDiscourse\DiscourseAdmin\DiscourseAdmin();
	}
	
	public function test_constructor_hooks_into_correct_filters_and_actions() {
		$this->assertEquals( 10, has_action( 'admin_init', array( $this->admin, 'admin_init' ) ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', array( $this->admin, 'admin_styles' ) ) );
		$this->assertEquals( 10, has_action( 'admin_menu', array( $this->admin, 'discourse_admin_menu' ) ) );
		$this->assertEquals( 10, has_action( 'load-settings_page_discourse', array( $this->admin, 'connection_status_notice' ) ) );
	}
}