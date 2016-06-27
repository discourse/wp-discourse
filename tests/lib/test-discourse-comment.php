<?php

require_once( __DIR__ . '/../../lib/discourse-comment.php' );

class TestDiscourseComment extends WP_UnitTestCase {
	public $comment;

	public function setUp() {
		$this->comment = new \WPDiscourse\DiscourseComment\DiscourseComment();

		$options       = array(
			'url'                    => 'http://forum.example.com',
			'use-discourse-comments' => 1,
			'show-existing-comments' => 0,
		);

		update_option( 'discourse', $options );
	}

	public function test_constructor_hooks_into_required_filters_and_actions() {
		$this->assertEquals( 10, has_filter( 'comments_number', array( $this->comment, 'comments_number' ) ) );
		$this->assertEquals( 20, has_filter( 'comments_template', array( $this->comment, 'comments_template' ) ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_scripts', array(
			$this->comment,
			'discourse_comments_js'
		) ) );
	}

	public function test_comments_template_syncs_comments_if_post_is_published_to_discourse() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );

		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$old = 'http://example.com/wordpress/wp-content/themes/twentysixteen/comments.php';

		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array( 'sync_comments' ) );

		$comment_mock->expects( $this->once() )
		             ->method( 'sync_comments' )
		             ->with( $post_id );

		$comment_mock->comments_template( $old );
	}

	public function test_comments_template_does_not_syncs_comments_if_post_is_not_published_to_discourse() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );

		$old = 'http://example.com/wordpress/wp-content/themes/twentysixteen/comments.php';

		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array( 'sync_comments' ) );

		$comment_mock->expects( $this->never() )
		             ->method( 'sync_comments' );

		$comment_mock->comments_template( $old );
	}

	public function test_comments_template_returns_discourse_template_when_post_published_to_discourse_and_there_are_no_wp_comments() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post    = get_post( $post_id );

		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$old = 'http://example.com/wordpress/wp-content/themes/twentysixteen/comments.php';
		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array(
			'sync_comments',
		) );
		$template = $comment_mock->comments_template( $old );
		$regex = '/wp-discourse\/templates\/comments.php$/';

		$this->assertEquals( 1, preg_match( $regex, $template ) );
	}

	public function test_comments_template_returns_wp_template_when_post_not_published_to_discourse() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post = get_post( $post_id );
		$old = 'http://example.com/wordpress/wp-content/themes/twentysixteen/comments.php';
		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array(
			'sync_comments',
		) );
		$template = $comment_mock->comments_template( $old );

		$this->assertEquals( $old, $template );

	}

	public function test_comments_template_returns_wp_template_when_show_existing_comments_is_true_and_there_are_comments() {
		global $post;

		$options       = array(
			'use-discourse-comments' => 1,
			'show-existing-comments' => 1,
			'existing-comments-heading' => 'Old comments',
		);
		update_option( 'discourse', $options );

		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post = get_post( $post_id );

		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
		) );

		$old = 'http://example.com/wordpress/wp-content/themes/twentysixteen/comments.php';
		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array(
			'sync_comments',
		) );

		$template = $comment_mock->comments_template( $old );

		$this->markTestIncomplete(
			'There needs to be a way to update the comment count property of the post for this test to work.'
		);

	}

	public function test_comments_number_returns_wp_comments_number_if_dicourse_comments_are_not_being_used() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_title' => 'This is a test',
		) );
		$post = get_post( $post_id );

		$this->assertEquals( '3 Replies', $this->comment->comments_number( '3 Replies' ) );
	}

	public function test_comments_number_returns_discourse_comments_number_when_discourse_comments_are_being_used() {
		global $post;
		$post_id = $this->factory->post->create( array(
			'post_tite' => 'This is a test',
		) );
		$post = get_post( $post_id );

		update_post_meta( $post_id, 'publish_to_discourse', 1 );
		update_post_meta( $post_id, 'discourse_comments_count', 5 );

		$comment_mock = $this->getMock( '\WPDiscourse\DiscourseComment\DiscourseComment', array(
			'sync_comments',
		) );

		$this->assertEquals( '5 Replies', $comment_mock->comments_number( '3 Replies' ) );
	}

	public function test_sync_comments_updates_post_metadata() {
		$this->markTestIncomplete(
			'It would be nice to be able to test this.'
		);
	}

}