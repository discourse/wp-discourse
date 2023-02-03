<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter;
use \WPDiscourse\Test\UnitTest;

/**
 * DiscourseComment test case.
 */
class DiscourseCommentFormatterTest extends UnitTest {

    /**
     * Instance of DiscourseCommentFormatter.
     *
     * @access protected
     * @var \WPDiscourse\DiscourseComment\DiscourseCommentFormatter
     */
    protected $comment_formatter;

    /**
     * Setup each test.
     */
    public function setUp() {
        parent::setUp();

        $this->comment_formatter = new DiscourseCommentFormatter();
        $this->comment_formatter->setup_options( self::$plugin_options );
        $this->comment_formatter->setup_logger();

        // Mock objects and endpoints.
        $this->discourse_post = json_decode( $this->response_body_file( 'post_create' ) );
        $this->post_id        = wp_insert_post( self::$post_atts, false, false );
        $comments_json        = $this->response_body_file( 'comments' );
        $comments             = json_decode( $comments_json );

        // Setup the post meta.
        $this->discourse_topic_id  = $this->discourse_post->topic_id;
        $this->discourse_permalink = self::$discourse_url . '/t/' . $this->discourse_post->topic_slug . '/' . $this->discourse_post->topic_id;
        update_post_meta( $this->post_id, 'discourse_comments_count', intval( $comments->filtered_posts_count ) - 1 );
        update_post_meta( $this->post_id, 'publish_post_category', intval( $comments->category_id ) );
        update_post_meta( $this->post_id, 'discourse_comments_raw', esc_sql( $comments_json ) );
        update_post_meta( $this->post_id, 'discourse_permalink', $this->discourse_permalink );
        update_post_meta( $this->post_id, 'discourse_topic_id', $this->discourse_topic_id );
  	}

    public function tearDown() {
        parent::tearDown();

        // Cleanup.
        wp_delete_post( $this->post_id );
    }

    public function test_format() {
        // Perform format.
        $result_html = $this->comment_formatter->format( $this->post_id, false );

        // Ensure right html is returned.
        libxml_use_internal_errors( true );
        $expected_dom = new \DomDocument();
        $expected_dom->loadHTMLFile( __DIR__ . '/../fixtures/templates/comment.html', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $expected_html = $this->sanitize_html( $expected_dom->saveHTML() );

        $actual_dom = new \DomDocument();
        $actual_dom->loadHTML( $result_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $actual_html = $this->sanitize_html( $actual_dom->saveHTML() );
        libxml_clear_errors();

        $this->assertEquals( $expected_html, $actual_html );
    }

    public function test_comment_cache() {
        self::$plugin_options['cache-html'] = true;
        $this->comment_formatter->setup_options( self::$plugin_options );

        // Perform format.
        $this->comment_formatter->format( $this->post_id, false );

        // Get cached.
        $cached_html = get_transient( "wpdc_comment_html_{$this->discourse_topic_id}" );

        // Ensure right html is cached.
        libxml_use_internal_errors( true );
        $expected_dom = new \DomDocument();
        $expected_dom->loadHTMLFile( __DIR__ . '/../fixtures/templates/comment.html', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $expected_html = $this->sanitize_html( $expected_dom->saveHTML() );

        $actual_dom = new \DomDocument();
        $actual_dom->loadHTML( $cached_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $actual_html = $this->sanitize_html( $actual_dom->saveHTML() );
        libxml_clear_errors();

        $this->assertEquals( $expected_html, $actual_html );
    }

    public function test_missing_post_meta() {
        $deleted_required_meta_key = 'discourse_permalink';
        delete_post_meta( $this->post_id, $deleted_required_meta_key );

        // Perform format.
        $result_html = $this->comment_formatter->format( $this->post_id, false );

        // Ensure right html is returned.
        libxml_use_internal_errors( true );
        $expected_dom = new \DomDocument();
        $expected_dom->loadHTMLFile( __DIR__ . '/../fixtures/templates/bad_response.html', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $expected_html = $this->sanitize_html( $expected_dom->saveHTML() );

        $actual_dom = new \DomDocument();
        $actual_dom->loadHTML( $result_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS );
        $actual_html = $this->sanitize_html( $actual_dom->saveHTML() );
        libxml_clear_errors();

        $this->assertEquals( $expected_html, $actual_html );

        // TO FIX. Ensure we've made the right logs.
        // $log = $this->get_last_log();
        // $this->assertRegExp( '/comment_formatter.ERROR: format.missing_post_data/', $log );
        // $this->assertRegExp( '/"keys":"' . $deleted_required_meta_key . '"/', $log );
    }

    protected function sanitize_html( $buffer ) {
        $search  = array(
            '/\>[^\S]+/s',      // strip whitespaces after tags.
            '/[^\S]+\</s',      // strip whitespaces before tags.
            '/(\s)+/s',         // shorten multiple whitespace sequences.
            '/<!--(.|\s)*?-->/', // Remove HTML comments.
        );
        $replace = array(
            '>',
            '<',
            '\\1',
            '',
        );
        $buffer  = preg_replace( $search, $replace, $buffer );
        return $buffer;
    }
}
