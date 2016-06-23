<?php

require_once( __DIR__ . '/../../lib/meta-box.php' );

use phpmock\phpunit\PHPMock;

class TestMetaBox extends \PHPUnit_Framework_TestCase {
	use PHPMock;

	protected $metabox;

	public function setUp() {
		$this->metabox = new \WPDiscourse\MetaBox\MetaBox();
		$options = array(
			'allowed_post_types' => array( 'post', 'page' ),
		);
		update_option( 'discourse', $options );

		parent::setUp();
	}

	public function test_constructor_adds_correct_actions() {
		$this->assertEquals( 10, has_action( 'add_meta_boxes', array( $this->metabox, 'add_meta_box' ) ) );
		$this->assertEquals( 10, has_action( 'save_post', array( $this->metabox, 'save_meta_box' ) ) );
	}

	public function test_add_meta_box_does_not_add_box_to_wrong_post_type() {
		$add_meta_box = $this->getFunctionMock( 'WPDiscourse\MetaBox', 'add_meta_box' );
		$add_meta_box->expects( $this->never() );

		$this->metabox->add_meta_box( 'product' );
	}

	public function test_add_meta_box_adds_meta_box_to_correct_post_type() {
		$add_meta_box = $this->getFunctionMock( 'WPDiscourse\MetaBox', 'add_meta_box' );
		$add_meta_box->expects( $this->once() );

		$this->metabox->add_meta_box( 'page' );
	}
}