<?php

require_once( __DIR__ . '/../../lib/discourse-publish.php' );

class TestDiscoursePublish extends WP_UnitTestCase {
	protected $publisher;

	public function setUp() {
		$this->publisher = new \WPDiscourse\DiscoursePublish\DiscoursePublish();
		$options         = array(
			'publish-category'   => '',
			'publish-username'   => 'system',
			'allowed_post_types' => array( 'post' ),
		);
		update_option( 'discourse', $options );
	}

	public function test_constructor_hooks_into_correct_actions() {
		$this->assertEquals( 13, has_action( 'save_post', array( $this->publisher, 'publish_post_after_save' ) ) );
		$this->assertEquals( 10, has_action( 'transition_post_status', array(
			$this->publisher,
			'publish_post_after_transition'
		) ) );
		$this->assertEquals( 10, has_action( 'xmlrpc_publish_post', array(
			$this->publisher,
			'xmlrpc_publish_post_to_discourse'
		) ) );
	}

	public function test_publish_post_after_transition_calls_sync_to_discourse_when_valid_post_type_and_publish_to_discourse_is_set() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );
		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->once() )
		             ->method( 'sync_to_discourse' )
		             ->with(
			             $post_id,
			             $post->post_title,
			             $post->post_content
		             );

		$publish_mock->publish_post_after_transition( 'publish', 'pending', $post );
	}

	public function test_publish_post_after_transition_does_not_call_sync_to_discourse_when_transitioning_to_private() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );
		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->publish_post_after_transition( 'private', 'publish', $post );
	}

	public function test_publish_post_after_transition_does_not_call_sync_to_discourse_when_publish_to_discourse_not_set() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->publish_post_after_transition( 'publish', 'publish', $post );
	}

	public function test_publish_post_after_transition_does_not_call_sync_to_discourse_when_not_valid_post_type() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
			'post_type'  => 'page',
		) );
		$post    = get_post( $post_id );
		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->publish_post_after_transition( 'publish', 'publish', $post );
	}

	public function test_xmlrpc_publish_post_to_discourse_calls_sync_to_discourse_when_valid_post_type_and_publish_to_discourse_is_set() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
			'post_type'  => 'page',
		) );
		$post    = get_post( $post_id );
		$options = array(
			'publish-category'   => 'uncategorized',
			'publish-username'   => 'system',
			'allowed_post_types' => array( 'page' ),
		);

		update_option( 'discourse', $options );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->once() )
		             ->method( 'sync_to_discourse' )
		             ->with( $post_id, $post->post_title, $post->post_content );

		$publish_mock->xmlrpc_publish_post_to_discourse( $post_id );
	}

	public function test_xmlrpc_publish_post_to_discourse_sets_publish_to_discourse_metadata() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );

		update_post_meta( $post_id, 'publish_to_discourse', 0 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );


		$publish_mock->xmlrpc_publish_post_to_discourse( $post_id );

		$this->assertEquals( 1, get_post_meta( $post_id, 'publish_to_discourse', true ) );
	}

	public function test_xmlrpc_publish_post_to_discourse_does_not_call_sync_to_discourse_for_wrong_post_type() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
			'post_type'  => 'page',
		) );
		$post    = get_post( $post_id );
		$options = array(
			'publish-category'   => 'uncategorized',
			'publish-username'   => 'system',
			'allowed_post_types' => array( 'post' ),
		);

		update_option( 'discourse', $options );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->xmlrpc_publish_post_to_discourse( $post_id );
	}

	public function test_publish_post_after_save_publishes_post_when_post_is_set_to_be_published() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post = get_post( $post_id );

		update_post_meta( $post_id,'publish_to_discourse', 1 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->once() )
			->method( 'sync_to_discourse' )
			->with( $post_id, $post->post_title, $post->post_content );

		$publish_mock->publish_post_after_save( $post_id, $post );
	}

	public function test_publish_post_after_save_does_not_publish_draft() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post = get_post( $post_id );

		update_post_meta( $post_id,'publish_to_discourse', 1 );
		
		$post_data = array(
			'ID' => $post_id,
			'post_status' => 'draft',
		);
		wp_update_post( $post_data );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->publish_post_after_save( $post_id, $post );
	}

	public function test_publish_post_after_save_does_not_publish_wrong_post_type() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
			'post_type' => 'page',
		) );
		$post = get_post( $post_id );

		update_post_meta( $post_id,'publish_to_discourse', 1 );

		$publish_mock = $this->getMock( '\WPDiscourse\DiscoursePublish\DiscoursePublish',
			array( 'sync_to_discourse' ) );

		$publish_mock->expects( $this->never() )
		             ->method( 'sync_to_discourse' );

		$publish_mock->publish_post_after_save( $post_id, $post );
	}
	
}
