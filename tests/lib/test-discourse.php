<?php

require_once( __DIR__ . '/../../lib/discourse.php' );


class TestDiscourse extends WP_UnitTestCase {

	protected $discourse;

	public function setUp() {
		$this->discourse = new \WPDiscourse\Discourse\Discourse();
	}
}