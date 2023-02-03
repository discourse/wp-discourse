<?php
/**
 * Class DiscoursePublishMultisiteTest
 *
 * @package WPDiscourse
 */

use WPDiscourse\Test\Multisite;
use WPDiscourse\Test\DiscoursePublishTest;

/**
 * DiscoursePublishMultisite test case.
 */
class DiscoursePublishMultisiteTest extends DiscoursePublishTest {
  use Multisite;

    /**
     * Sync_to_discourse handles new posts correctly in multisite
     */
    public function test_sync_to_discourse_when_creating_in_multisite() {
        // Set as multisite.
        self::$plugin_options['multisite-configuration-enabled'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for creating a new post.
        $body               = $this->mock_remote_post_success( 'post_create', 'POST' );
        $discourse_topic_id = $body->topic_id;

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->sync_to_discourse_without_lock( $post_id, $post->title, $post->post_content );

        // Ensure the topic blog id is created properly.
        $this->assertTrue( $this->publish->topic_blog_id_exists( $body->topic_id ) );

        // cleanup.
        wp_delete_post( $post_id );
    }
}
