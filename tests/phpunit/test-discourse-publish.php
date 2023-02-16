<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

use \WPDiscourse\EmailNotification\EmailNotification;
use \WPDiscourse\DiscoursePublish\DiscoursePublish;
use \WPDiscourse\Logs\FileHandler;
use \WPDiscourse\Test\UnitTest;

/**
 * DiscoursePublish test case.
 */
class DiscoursePublishTest extends UnitTest {

    /**
     * Instance of DiscoursePublish.
     *
     * @access protected
     * @var \WPDiscourse\DiscoursePublish\DiscoursePublish
     */
    protected $publish;

    /**
     * Setup test class
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::initialize_variables();
    }

    /**
     * Setup each test.
     */
    public function setUp() {
        parent::setUp();

        $register_actions = false;
        $this->publish    = new DiscoursePublish( new EmailNotification(), $register_actions );
        $this->publish->setup_options( self::$plugin_options );
        $this->publish->setup_logger();
  	}

    /**
     * Sync_to_discourse handles new posts correctly.
     */
    public function test_sync_to_discourse_when_creating() {
        // Set up a response body for creating a new post.
        $body                = $this->mock_remote_post_success( 'post_create', 'POST' );
        $discourse_post_id   = $body->id;
        $discourse_topic_id  = $body->topic_id;
        $discourse_permalink = self::$discourse_url . '/t/' . $body->topic_slug . '/' . $body->topic_id;
        $discourse_category  = self::$post_atts['meta_input']['publish_post_category'];

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->sync_to_discourse_without_lock( $post_id, $post->title, $post->post_content );

        // Ensure the right post meta is created.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
        $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with embed error response.
     */
    public function test_sync_to_discourse_when_creating_with_embed_error() {
        // Set up the error responses.
        $raw_response  = $this->build_response( 'unprocessable', 'embed' );
        $error_message = json_decode( $raw_response['body'] )->errors[0];
        $request       = array(
			'response' => $raw_response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right error is returned.
        $this->assertEquals( $response, $this->build_post_error() );

        // Ensure the post meta is updated correctly.
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_auto_publish_overridden', true ), 1 );
        $this->assertEquals( get_post_meta( $post_id, 'publish_to_discourse', true ), '' );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_error', true ), $error_message );

        // Ensure the right log is created.
        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
        $this->assertRegExp( '/"http_code":' . $raw_response['response']['code'] . '/', $log );
        $this->assertRegExp( '/"response_message":"' . $error_message . '"/', $log );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with category error response.
     */
    public function test_sync_to_discourse_when_creating_with_category_error() {
        // Set up the error responses.
        $raw_response  = $this->build_response( 'invalid_parameters', 'category' );
        $error_message = json_decode( $raw_response['body'] )->errors[0];
        $request       = array(
			'response' => $raw_response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right error is returned.
        $this->assertEquals( $response, $this->build_post_error() );

        // Ensure the post meta is updated correctly.
        $this->assertEquals( get_post_meta( $post_id, 'publish_to_discourse', true ), '' );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_error', true ), $error_message );

        // Ensure the right log is created.
        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
        $this->assertRegExp( '/"http_code":' . $raw_response['response']['code'] . '/', $log );
        $this->assertRegExp( '/"response_message":"' . $error_message . '"/', $log );

        // cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with invalid body in response.
     */
    public function test_sync_to_discourse_when_creating_with_response_body_error() {
        // Setup the invalid respond body.
        $response         = $this->build_response( 'success' );
        $response['body'] = '{ "invalid_body" : true }';
        $request          = array(
			'response' => $response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right error is returned.
        $this->assertEquals( $response, $this->build_body_error() );

        // Ensure the post meta is updated correctly.
        $this->assertEquals( get_post_meta( $post_id, 'publish_to_discourse', true ), '' );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_error', true ), 'OK' );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'error' );

        // Ensure the right log is created.
        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.body_validation_error/', $log );

        // cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post and post is enqueued.
     */
    public function test_sync_to_discourse_when_creating_with_enqueued_post() {
        // Setup the enqueued response body.
        $response         = $this->build_response( 'success' );
        $response['body'] = '';
        $request          = array(
			'response' => $response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right error is returned.
        $message = __( 'The published post has been added to the Discourse approval queue', 'wp-discourse' );
        $this->assertEquals( $response, $this->build_notice( $message ) );

        // Ensure the post meta is updated correctly.
        $this->assertEquals( get_post_meta( $post_id, 'publish_to_discourse', true ), 1 );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), '' );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_error', true ), 'queued_topic' );

        // Ensure the right log is created.
        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.WARNING: create_post.queued_topic_notice/', $log );

        // cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with direct-db-publication-flags.
     */
    public function test_sync_to_discourse_when_creating_with_direct_db_publication_flags() {
        // Enable direct db pubilcation flags option.
        self::$plugin_options['direct-db-publication-flags'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for creating a new post.
        $body                = $this->mock_remote_post_success( 'post_create', 'POST' );
        $discourse_post_id   = $body->id;
        $discourse_topic_id  = $body->topic_id;
        $discourse_permalink = self::$discourse_url . '/t/' . $body->topic_slug . '/' . $body->topic_id;
        $discourse_category  = self::$post_atts['meta_input']['publish_post_category'];

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->sync_to_discourse_without_lock( $post_id, $post->title, $post->post_content );

        // Ensure the right post meta is created.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
        $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post and pinning topics.
     */
    public function test_sync_to_discourse_pin_topic() {
        // Set up a response body for creating a new post, with subsequent pin request.
        $discourse_post = json_decode( $this->response_body_file( 'post_create' ) );
        $pin_until      = '2021-02-17';
        $second_request = array(
            'url'      => self::$discourse_url . "/t/{$discourse_post->topic_id}/status",
            'method'   => 'PUT',
            'response' => $this->build_response( 'success' ),
        );
        $body           = $this->mock_remote_post_success( 'post_create', 'POST', $second_request );

        // Add a post that will be pinned.
        $post_atts                                 = self::$post_atts;
        $post_atts['meta_input']['wpdc_pin_until'] = $pin_until;
        $post_id                                   = wp_insert_post( $post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right result.
        $this->assertFalse( is_wp_error( $response ) );
        $this->assertTrue( empty( get_post_meta( $post_id, 'wpdc_pin_until', true ) ) );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with Discourse Username set.
     */
    public function test_sync_to_discourse_discourse_username() {
        $discourse_username = 'angus2';

        // Setup the user
        $user_id = self::factory()->user->create();
        add_user_meta( $user_id, 'discourse_username', $discourse_username, true );

        // Set up a response body for creating a new post
        $raw_body         = $this->response_body_file( 'post_create' );
        $discourse_post   = json_decode( $raw_body );
        $response         = $this->build_response( 'success' );
        $response['body'] = $raw_body;
        $request          = array(
			'headers'  => array(
				'Api-Key'      => self::$connection_options['api-key'],
				'Api-Username' => $discourse_username,
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'method'   => 'POST',
			'response' => $response,
        );
        $body             = $this->mock_remote_post( $request );

        // Add the post.
        $post_atts                = self::$post_atts;
        $post_atts['post_author'] = $user_id;
        $post_id                  = wp_insert_post( $post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right result.
        $this->assertFalse( is_wp_error( $response ) );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when creating a new post with Discourse Username set.
     */
    public function test_sync_to_discourse_discourse_username_with_single_user_api_key() {
        $discourse_username = 'angus';

        // Enable single user api key
        self::$plugin_options['single-user-api-key-publication'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Setup the user
        $user_id = self::factory()->user->create();
        add_user_meta( $user_id, 'discourse_username', $discourse_username, true );

        // Set up a response body for creating a new post
        $raw_body         = $this->response_body_file( 'post_create' );
        $discourse_post   = json_decode( $raw_body );
        $response         = $this->build_response( 'success' );
        $response['body'] = $raw_body;
        $first_request    = array(
			'headers'  => array(
				'Api-Key'      => self::$connection_options['api-key'],
				'Api-Username' => self::$connection_options['publish-username'],
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'method'   => 'POST',
			'response' => $response,
        );
        $second_request   = array(
            'url'      => self::$discourse_url . "/t/{$discourse_post->topic_id}/change-owner",
            'method'   => 'POST',
            'body'     => json_encode(
                array(
					'username' => $discourse_username,
					'post_ids' => array( "{$discourse_post->id}" ),
				)
                ),
            'response' => $this->build_response( 'success' ),
        );
        $body             = $this->mock_remote_post( $first_request, $second_request );

        // Add the post.
        $post_atts                = self::$post_atts;
        $post_atts['post_author'] = $user_id;
        $post_id                  = wp_insert_post( $post_atts, false, false );

        // Run the publication.
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right result.
        $this->assertFalse( is_wp_error( $response ) );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when updating a post.
     */
    public function test_sync_to_discourse_when_updating() {
        // Set up a response body for updating an existing post.
        $body = $this->mock_remote_post_success( 'post_update', 'PUT' );
        $post = $body->post;

        $discourse_post_id   = $post->id;
        $discourse_topic_id  = $post->topic_id;
        $discourse_permalink = self::$discourse_url . '/t/' . $post->topic_slug . '/' . $post->topic_id;
        $discourse_category  = self::$post_atts['meta_input']['publish_post_category'];

        // Add a post that's already been published to Discourse.
        $post_atts                                    = self::$post_atts;
        $post_atts['meta_input']['discourse_post_id'] = $discourse_post_id;
        $post_id                                      = wp_insert_post( $post_atts, false, false );

        // Run the update.
        update_post_meta( $post_id, 'update_discourse_topic', 1 );
        $post = get_post( $post_id );
        $this->publish->sync_to_discourse_without_lock( $post_id, $post->title, $post->post_content );

        // Ensure the right post meta still exists.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
        $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when updating a post and post is deleted.
     */
    public function test_sync_to_discourse_when_updating_with_deleted_topic() {
        // Setup the response body for an existing post that's been deleted.
        $response               = $this->build_response( 'success' );
        $raw_body               = $this->response_body_json( 'post_update' );
        $body                   = json_decode( $raw_body );
        $body->post->deleted_at = '2021-03-10T23:06:05.328Z';
        $response['body']       = json_encode( $body );
        $request                = array(
			'response' => $response,
			'method'   => 'PUT',
        );
        $this->mock_remote_post( $request );

        // Add a post that's already been published to Discourse.
        $discourse_post_id                            = $body->post->id;
        $post_atts                                    = self::$post_atts;
        $post_atts['meta_input']['discourse_post_id'] = $discourse_post_id;
        $post_id                                      = wp_insert_post( $post_atts, false, false );

        // Run the update.
        update_post_meta( $post_id, 'update_discourse_topic', 1 );
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
           $post_id,
           $post->title,
           $post->post_content
        );

        // Ensure the right error is returned.
        $message = __( 'The Discourse topic associated with this post has been deleted', 'wp-discourse' );
        $this->assertEquals( $response, $this->build_notice( $message ) );

        // Ensure the post meta is updated correctly.
        $this->assertEquals( get_post_meta( $post_id, 'publish_to_discourse', true ), 1 );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_error', true ), 'deleted_topic' );

        // Ensure the right log is created.
        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.WARNING: update_post.deleted_topic_notice/', $log );

        // cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when updating a post and adding featured link.
     */
    public function test_sync_to_discourse_when_updating_with_featured_link() {
        // Enable featured link option.
        self::$plugin_options['add-featured-link'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Add a post that's already been published to Discourse.
        $body           = json_decode( $this->response_body_json( 'post_update' ) );
        $discourse_post = $body->post;
        $post_atts      = self::$post_atts;
        $post_atts['meta_input']['discourse_post_id'] = $discourse_post->id;
        $post_id                                      = wp_insert_post( $post_atts, false, false );

        // Set up a response body for updating an existing post, and the featured link in the second request.
        $second_request = array(
            'url'      => self::$discourse_url . '/t/' . $discourse_post->topic_slug . '/' . $discourse_post->topic_id,
            'method'   => 'PUT',
            'response' => $this->build_response( 'success' ),
        );
        $body           = $this->mock_remote_post_success( 'post_update', 'PUT', $second_request );
        $post           = $body->post;

        // Run the update.
        update_post_meta( $post_id, 'update_discourse_topic', 1 );
        $post     = get_post( $post_id );
        $response = $this->publish->sync_to_discourse_without_lock(
            $post_id,
            $post->title,
            $post->post_content
        );

        // Ensure the right result.
        $this->assertFalse( is_wp_error( $response ) );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Sync_to_discourse when updating a post with direct-db-publication-flags.
     */
    public function test_sync_to_discourse_when_updating_with_direct_db_publication_flags() {
        // Enable direct db pubilcation flags option.
        self::$plugin_options['direct-db-publication-flags'] = 1;
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for updating an existing post.
        $body = $this->mock_remote_post_success( 'post_update', 'PUT' );
        $post = $body->post;

        $discourse_post_id   = $post->id;
        $discourse_topic_id  = $post->topic_id;
        $discourse_permalink = self::$discourse_url . '/t/' . $post->topic_slug . '/' . $post->topic_id;
        $discourse_category  = self::$post_atts['meta_input']['publish_post_category'];

        // Add a post that's already been published to Discourse.
        $post_atts                                    = self::$post_atts;
        $post_atts['meta_input']['discourse_post_id'] = $discourse_post_id;
        $post_id                                      = wp_insert_post( $post_atts, false, false );

        // Run the update.
        update_post_meta( $post_id, 'update_discourse_topic', 1 );
        $post   = get_post( $post_id );
        $result = $this->publish->sync_to_discourse_without_lock( $post_id, $post->title, $post->post_content );

        // Ensure the right post meta still exists.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_topic_id', true ), $discourse_topic_id );
        $this->assertEquals( get_post_meta( $post_id, 'discourse_permalink', true ), $discourse_permalink );
        $this->assertEquals( get_post_meta( $post_id, 'publish_post_category', true ), $discourse_category );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Exclude_tags prevents publication if excluded tag is present
     */
    public function test_exclude_tags_with_exclusionary_tag() {
        if ( version_compare( get_bloginfo( 'version' ), '5.6', '<' ) ) {
        $this->markTestSkipped(
            'Not supported on WordPress version.'
          );
        }

        // Create the exclusionary tag
        $excluded_term = term_exists( 'dont_publish', 'post_tag' );
        if ( ! $excluded_term ) {
          $excluded_term = wp_insert_term( 'dont_publish', 'post_tag' );
        }
        $excluded_term      = get_tag( $excluded_term['term_id'] );
        $excluded_term_slug = $excluded_term->slug;

        // Enable exclude_tags option.
        self::$plugin_options['exclude_tags'] = array( $excluded_term_slug );
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for creating a new post.
        $body                          = $this->mock_remote_post_success( 'post_create', 'POST' );
        self::$post_atts['tags_input'] = array( $excluded_term_slug );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->publish_post_after_save( $post_id, $post );

        // Ensure no publication occurs.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), null );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), null );

        // Cleanup.
        wp_delete_post( $post_id );
        wp_delete_term( $excluded_term->ID, 'post_tags' );
    }

    /**
     * Exclude_tags does not prevent publication if excluded tag is not present
     */
    public function test_exclude_tags_with_non_exclusionary_tag() {
        // Create a non exclusionary tag
        $term = term_exists( 'publish', 'post_tag' );
        if ( ! $term ) {
          $term = wp_insert_term( 'publish', 'post_tag' );
        }
        $term      = get_tag( $term['term_id'] );
        $term_slug = $term->slug;

        // Create the exclusionary tag
        $excluded_term = term_exists( 'dont_publish', 'post_tag' );
        if ( ! $excluded_term ) {
          $excluded_term = wp_insert_term( 'dont_publish', 'post_tag' );
        }
        $excluded_term      = get_tag( $excluded_term['term_id'] );
        $excluded_term_slug = $excluded_term->slug;

        // Enable excluded tags option.
        self::$plugin_options['exclude_tags'] = array( $excluded_term_slug );
        $this->publish->setup_options( self::$plugin_options );

        // Set up a response body for creating a new post.
        $body                          = $this->mock_remote_post_success( 'post_create', 'POST' );
        $discourse_post_id             = $body->id;
        self::$post_atts['tags_input'] = array( $term_slug );

        // Add the post.
        $post_id = wp_insert_post( self::$post_atts, false, false );

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->publish_post_after_save( $post_id, $post );

        // Ensure publication occurs.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
        wp_delete_term( $term->ID, 'post_tags' );
        wp_delete_term( $excluded_term->ID, 'post_tags' );
    }

    /**
     * wpdc_publish_options filters publish_options
     */
    public function test_wpdc_publish_options() {
        $tags = array( 'tag1', 'tag2' );

        // Add filter.
        add_filter(
             'wpdc_publish_body', function( $body, $remote_post_type ) use ( $tags ) {
          if ( 'create_post' === $remote_post_type ) {
					$body['tags'] = $tags;
          }
          return $body;
        }, 10, 2
            );

        $raw_body             = $this->response_body_json( 'post_create' );
    		$response         = $this->build_response( 'success' );
    		$response['body'] = $raw_body;
        $body                 = json_decode( $raw_body );

        // Check tags are in the body passed to request.
        add_filter(
             'pre_http_request', function( $prempt, $args, $url ) use ( $tags, $response ) {
          $body = json_decode( $args['body'] );

          if ( ! isset( $body->tags ) || ! ( $tags === $body->tags ) ) {
						return new \WP_Error( 'http_request_failed', 'Failed to add tags' );
  				} else {
            return $response;
          }
        }, 10, 3
            );

        // Setup post.
        $post_id           = wp_insert_post( self::$post_atts, false, false );
        $discourse_post_id = $body->id;

        // Run the publication.
        $post = get_post( $post_id );
        $this->publish->publish_post_after_save( $post_id, $post );

        // Ensure publication occurs.
        $this->assertEquals( get_post_meta( $post_id, 'discourse_post_id', true ), $discourse_post_id );
        $this->assertEquals( get_post_meta( $post_id, 'wpdc_publishing_response', true ), 'success' );

        // Cleanup.
        wp_delete_post( $post_id );
    }

    /**
     * Successful remote_post request returns original response.
     */
    public function test_remote_post_success() {
        $success_response = $this->build_response( 'success' );
        $request          = array(
			'response' => $success_response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );
        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $success_response );
    }

    /**
     * Forbidden remote_post request returns standardised WP_Error and creates correct log.
     */
    public function test_remote_post_forbidden() {
        $raw_response = $this->build_response( 'forbidden' );
        $request      = array(
			'response' => $raw_response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $this->build_post_error() );

        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
        $this->assertRegExp( '/"http_code":' . $raw_response['response']['code'] . '/', $log );
    }

    /**
     * Unprocessable remote_post request returns standardised WP_Error and creates correct log.
     */
    public function test_remote_post_unprocessable() {
        $raw_response = $this->build_response( 'unprocessable', 'title' );
        $request      = array(
			'response' => $raw_response,
			'method'   => 'POST',
        );
        $this->mock_remote_post( $request );

        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $this->build_post_error() );

        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
        $this->assertRegExp( '/"http_code":' . $raw_response['response']['code'] . '/', $log );
    }

    /**
     * Forbidden remote_post request returns standardised WP_Error and creates correct log.
     */
    public function test_remote_post_failed_to_connect() {
        $request = array(
			'method'   => 'POST',
			'response' => new \WP_Error(
				'http_request_failed',
				'cURL error 7: Failed to connect to localhost port 3000: Connection refused'
			),
        );
        $this->mock_remote_post( $request );

        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $this->build_post_error() );

        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
    }

    /**
     * Build error returned by discourse-publish when post request fails.
     */
    protected function build_post_error() {
        $message = __( 'An error occurred when communicating with Discourse', 'wp-discourse' );
        return new \WP_Error( 'discourse_publishing_response_error', $message );
    }

    /**
     * Build error returned by discourse-publish when response body is invalid.
     */
    protected function build_body_error() {
        $message = __( 'An invalid response was returned from Discourse', 'wp-discourse' );
        return new \WP_Error( 'discourse_publishing_response_error', $message );
    }

    /**
     * Build an error notice returned by discourse-publish when post queued or topic deleted.
     */
    protected function build_notice( $message ) {
        return new \WP_Error( 'discourse_publishing_response_notice', $message );
    }

    /**
     * Initialize static variables used by test class.
     */
    public static function initialize_variables() {
        self::$remote_post_params = array(
            self::$discourse_url,
			array(
				'timeout' => 30,
				'method'  => 'POST',
				'headers' => array(
					'Api-Key'      => self::$connection_options['api-key'],
					'Api-Username' => self::$connection_options['publish-username'],
				),
				'body'    => http_build_query(
					array(
						'embed_url'        => 'https://wordpress.org/post.php',
						'featured_link'    => null,
						'title'            => 'New Topic Title',
						'raw'              => 'Post content',
						'category'         => 3,
						'skip_validations' => 'true',
						'auto_track'       => 'false',
						'visible'          => 'true',
					)
				),
			),
			'create_post',
			1,
        );
    }
}
