<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter;
use \WPDiscourse\DiscourseComment\DiscourseComment;
use \WPDiscourse\Test\UnitTest;

/**
 * DiscourseComment test case.
 */
class DiscourseCommentTest extends UnitTest {

    /**
     * Instance of DiscourseComment.
     *
     * @access protected
     * @var \WPDiscourse\DiscourseComment\DiscourseComment
     */
    protected $comment;

    /**
     * Setup each test.
     */
    public function setUp() {
        parent::setUp();

        $comment_formatter = new DiscourseCommentFormatter();
        $this->comment     = new DiscourseComment( $comment_formatter );
        $this->comment->setup_logger();

        self::$plugin_options[ 'enable-discourse-comments' ] = true;
        $this->comment->setup_options( self::$plugin_options );
  	}

    /**
     * Get comment type for posts works with display_public_comments_only.
     */
    public function test_get_comment_type_for_post_display_public_comments_only() {
        // Setup plugin options
        self::$plugin_options[ "comment-type" ] = "display-public-comments-only";
        $this->comment->setup_options( self::$plugin_options );

        // Setup the categories response
        $site_json         = $this->response_body_file( 'site' );
        $response          = $this->build_response( 'success' );
        $response['body']  = $site_json;
        $this->mock_remote_post( $response );

        // Setup the category ids.
        $site = json_decode( $site_json );
        $categories = $site->categories;
        $public_category_id = null;
        $private_category_id = null;

        foreach( $categories as $category ) {
          if ( $category->read_restricted === false) {
            $public_category_id = $category->id;
          }
          if ( $category->read_restricted === true) {
            $private_category_id = $category->id;
          }
        }

        // Add the posts.
        self::$post_atts['meta_input']['discourse_post_id'] = 1;
        self::$post_atts['meta_input']['publish_post_category'] =  $public_category_id;
        $public_post_id = wp_insert_post( self::$post_atts, false, false );

        self::$post_atts['meta_input']['discourse_post_id'] = 2;
        self::$post_atts['meta_input']['publish_post_category'] =  $private_category_id;
        $private_post_id = wp_insert_post( self::$post_atts, false, false );

        // Get the comment types.
        $context = 'test';
        $public_comment_type = $this->comment->get_comment_type_for_post( $public_post_id, $context );
        $private_comment_type = $this->comment->get_comment_type_for_post( $private_post_id, $context );

        // Ensure we got the right types.
        $this->assertEquals( $public_comment_type, 'display-comments' );
        $this->assertEquals( $private_comment_type, 'display-comments-link' );

        // Cleanup.
        wp_delete_post( $public_post_id );
        wp_delete_post( $private_post_id );
    }

    /**
     * Get comment type for posts works with display_public_comments_only.
     */
    public function test_get_comment_type_for_post_display_public_comments_only_when_connection_fails() {
        $response_error = 'forbidden';
        $response_message = 'There was an error establishing a connection with Discourse';

        // Setup plugin options
        self::$plugin_options[ "comment-type" ] = "display-public-comments-only";
        $this->comment->setup_options( self::$plugin_options );

        // Setup the categories response
        $response = $this->build_response( $response_error );
        $this->mock_remote_post( $response );

        // Setup the category ids.
        $site = json_decode( $this->response_body_file( 'site' ) );
        $categories = $site->categories;
        $public_category_id = null;
        $private_category_id = null;

        foreach( $categories as $category ) {
          if ( $category->read_restricted === false) {
            $public_category_id = $category->id;
          }
          if ( $category->read_restricted === true) {
            $private_category_id = $category->id;
          }
        }

        // Add the posts.
        self::$post_atts['meta_input']['discourse_post_id'] = 1;
        self::$post_atts['meta_input']['publish_post_category'] =  $public_category_id;
        $public_post_id = wp_insert_post( self::$post_atts, false, false );

        self::$post_atts['meta_input']['discourse_post_id'] = 2;
        self::$post_atts['meta_input']['publish_post_category'] =  $private_category_id;
        $private_post_id = wp_insert_post( self::$post_atts, false, false );

        // Get the comment types.
        $context = 'test';
        $public_comment_type = $this->comment->get_comment_type_for_post( $public_post_id, $context );
        $private_comment_type = $this->comment->get_comment_type_for_post( $private_post_id, $context );

        // Ensure we got the right types.
        $this->assertEquals( $public_comment_type, 'display-comments-link' );
        $this->assertEquals( $private_comment_type, 'display-comments-link' );

        // Ensure we've made the right logs
        $log = $this->get_last_log();
        $this->assertRegExp( '/comment.ERROR: test.get_discourse_category/', $log );
        $this->assertRegExp( '/"message":"' . $response_message . '"/', $log );

        // Cleanup.
        wp_delete_post( $public_post_id );
        wp_delete_post( $private_post_id );
    }
}

