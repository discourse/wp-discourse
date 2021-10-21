<?php
/**
 * Class SyncDiscourseTopicTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use WPDiscourse\SyncDiscourseTopic\SyncDiscourseTopic;
use \WPDiscourse\Test\UnitTest;

/**
 * SyncDiscourseTopic test case.
 */
class SyncDiscourseTopicTest extends UnitTest {

    /**
     * Instance of SyncDiscourseTopic.
     *
     * @access protected
     * @var \WPDiscourse\SyncDiscourseTopic\SyncDiscourseTopic
     */
    protected $sync_topic;

    /**
     * Setup each test.
     */
    public function setUp() {
        parent::setUp();

        $this->sync_topic = new SyncDiscourseTopicTest();
        $this->sync_topic->setup_logger();
        $this->sync_topic->setup_options( self::$plugin_options );

        $this->secret = "secret";
        $this->nonce = "abcd";
        $this->payload = base64_encode( "nonce={$this->nonce}" );
        $this->signature = hash_hmac( 'sha256', $this->payload, $this->secret );
  	}

    public function test_update_topic_content() {
        // Mock objects and endpoints
        $discourse_post      = json_decode( $this->response_body_file( 'post_create' ) );
        $post_id             = wp_insert_post( self::$post_atts, false, false );
        $comments_response   = $this->mock_remote_post_success( 'comments' );

        // Setup the post meta
        $discourse_topic_id  = $discourse_post->topic_id;
        $discourse_permalink = self::$discourse_url . '/t/' . $discourse_post->topic_slug . '/' . $discourse_post->topic_id;
        update_post_meta( $post_id, 'discourse_permalink', $discourse_permalink );
        update_post_meta( $post_id, 'discourse_topic_id', $discourse_topic_id );

        // Setup transients
        set_transient( "wpdc_comment_html_$discourse_topic_id", $comments_response, 10 * MINUTE_IN_SECONDS );

        // Perform sync
        $this->comment->sync_comments( $post_id, true );

        // Ensure right comment json is saved
        $comments_raw = get_post_meta( $post_id, 'discourse_comments_raw' );
        $this->assertEquals( $comments_response, json_decode( $comments_raw[0] ) );

        // Ensure HTML transient is cleared
        $this->assertFalse( get_transient( "wpdc_comment_html_$discourse_topic_id" ) );

        // Cleanup
        wp_delete_post( $post_id );
    }
}

