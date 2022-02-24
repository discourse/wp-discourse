<?php
/**
 * Class \Test\Multisite
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

/**
 * Multisite methods for WPDiscourse unit tests
 */
trait Multisite {

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
   * Create topic_blog_table if it doesn't exist.
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
   * Clear topic_blog_table.
   */
  protected function clear_topic_blog_table() {
		global $wpdb;
		$table  = $wpdb->base_prefix . 'wpdc_topic_blog';
		$result = $wpdb->query( "TRUNCATE TABLE $table" );
  }

  /**
   * Switch to blog for discourse topic.
   */
  protected function switch_to_blog_for_topic( $topic_id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
		$query      = "SELECT blog_id FROM $table_name WHERE topic_id = %d";
		$blog_id    = $wpdb->get_var( $wpdb->prepare( $query, $topic_id ) );
		switch_to_blog( $blog_id );
  }
}
