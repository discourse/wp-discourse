<?php

class TestDiscourse extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

	}
	public function test_sample_string() {
		$string = 'this is a test';
		$this->assertEquals( 'this is a test', $string );
	}

}