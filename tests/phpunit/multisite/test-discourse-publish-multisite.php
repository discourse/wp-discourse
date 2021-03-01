<?php
/**
 * Class DiscoursePublishMultisiteTest
 *
 * @package WPDiscourse
 */

/**
 * DiscoursePublishMultisite test case.
 */
class DiscoursePublishMultisiteTest extends DiscoursePublishTest {

    /**
     * Setup multisite tests
     */
    public function setUp() {
        parent::setUp();
        $this->create_topic_blog_table();
    }


    /**
     * Teardown multisite tests
     */
    public function tearDown() {
        parent::tearDown();
        $this->clear_topic_blog_table();
    }

    /**
     * Sync_to_discourse handles new posts correctly in multisite
     */
    public function test_sync_to_discourse_when_creating_in_multisite() {
        // Set as multisite.
        self::$plugin_options['multisite-configuration-enabled'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for creating a new post.
        $body               = $this->mock_remote_post_success( 'post_create' );
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

    /**
     * Create topic_blog_table is it doesn't exist.
     */
    protected function create_topic_blog_table() {
        global $wpdb;

        $table = $wpdb->base_prefix . 'wpdc_topic_blog';
        $sql   = sprintf(
            'CREATE TABLE %s (
              topic_id mediumint(9) NOT NULL,
              blog_id mediumint(9) NOT NULL,
              PRIMARY KEY  (topic_id)
            ) %s;',
            $table,
            $wpdb->get_charset_collate()
        );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        maybe_create_table( $table, $sql );
    }

    /**
     * Clear topic_blog_table
     */
    protected function clear_topic_blog_table() {
        global $wpdb;
        $table  = $wpdb->base_prefix . 'wpdc_topic_blog';
        $result = $wpdb->query( "TRUNCATE TABLE $table" );
    }
}
