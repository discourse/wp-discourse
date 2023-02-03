<?php
/**
 * Class SyncDiscourseTopicMultisiteTest
 *
 * @package WPDiscourse
 */

use WPDiscourse\Test\Multisite;
use WPDiscourse\Test\SyncDiscourseTopicTest;
use \WPDiscourse\DiscoursePublish\DiscoursePublish;

/**
 * SyncDiscourseTopicMultisiteTest test case.
 */
class SyncDiscourseTopicMultisiteTest extends SyncDiscourseTopicTest {
  use Multisite;

  /**
   * update_topic_content handles webhook results correctly in multisite.
   */
  public function test_update_topic_content() {
		// Set as multisite.
		self::$plugin_options['multisite-configuration-enabled'] = 1;
		$this->sync_topic->setup_options( self::$plugin_options );

		// Setup the posts
		$post_id        = wp_insert_post( self::$post_atts, false, false );
		$discourse_post = json_decode( $this->payload )->post;

		// Setup the post meta
		$discourse_topic_id = $discourse_post->topic_id;
		update_post_meta( $post_id, 'discourse_topic_id', $discourse_topic_id );

		// Setup multisite data
		$blog_id = intval( get_current_blog_id() );
		$this->sync_topic->save_topic_blog_id( $discourse_topic_id, $blog_id );

		// Perform update
		$this->sync_topic->update_topic_content( $this->request );

		// Ensure the post meta is updated correctly on the right site.
		$this->switch_to_blog_for_topic( $discourse_topic_id );
		$this->assertEquals( get_post_meta( $post_id, 'wpdc_sync_post_comments', true ), 1 );
		$this->assertEquals( get_post_meta( $post_id, 'discourse_comments_count', true ), '2' );

		// Cleanup
		wp_delete_post( $post_id );
		restore_current_blog();
  }
}
