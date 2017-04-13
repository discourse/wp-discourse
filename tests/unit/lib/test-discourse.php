<?php

require_once( __DIR__ . '/../../../lib/discourse.php' );

class DiscourseTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		\WP_Mock::setUp();
	}

	public function tearDown() {
		\WP_Mock::tearDown();
	}

	public function test_wp_mock_setup() {
		$str = 'this is a test';

		$this->assertEquals( $str, 'this is a test' );
	}
}
