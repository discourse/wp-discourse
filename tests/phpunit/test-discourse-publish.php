<?php
/**
 * Class DiscoursePublishTest
 *
 * @package WPDiscourse
 */

use \WPDiscourse\EmailNotification\EmailNotification;
use \WPDiscourse\DiscoursePublish\DiscoursePublish;
use \WPDiscourse\Logs\FileManager;
use \WPDiscourse\Logs\FileHandler;

/**
 * DiscoursePublish test case.
 */
class DiscoursePublishTest extends WP_UnitTestCase {

    /**
     * URL of mock discourse instance.
     *
     * @access public
     * @var string
     */
    public static $discourse_url;

    /**
     * Params used in remote posts.
     *
     * @access public
     * @var object
     */
    public static $remote_post_params;

    /**
     * WP_Post attributes.
     *
     * @access public
     * @var object
     */
    public static $post_atts;

    /**
     * Plugin options.
     *
     * @access public
     * @var object
     */
    public static $plugin_options;

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
        self::initialize_static_variables();
    }

    /**
     * Setup each test.
     */
    public function setUp() {
        $register_actions = false;
        $this->publish    = new DiscoursePublish( new EmailNotification(), $register_actions );
        $this->publish->setup_logger();
        $this->publish->setup_options( self::$plugin_options );
  	}

    /**
     * Teardown each test.
     */
    public function tearDown() {
        $this->clear_logs();
        remove_all_filters( 'pre_http_request' );
    }

    /**
     * Sync_to_discourse handles new posts correctly.
     */
    public function test_sync_to_discourse_when_creating() {
        // Set up a response body for creating a new post.
        $body                = $this->mock_remote_post_success( 'post_create' );
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
        $this->mock_remote_post( $raw_response );

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
        $this->mock_remote_post( $raw_response );

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
        $response  = $this->build_response( 'success' );
        $response['body'] = '{ "invalid_body" : true }';
        $this->mock_remote_post( $response );

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
        $response  = $this->build_response( 'success' );
        $response['body'] = '';
        $this->mock_remote_post( $response );

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
        $body                = $this->mock_remote_post_success( 'post_create' );
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
        $pin_until      = '2021-02-17';
        $pin_until_body = http_build_query(
            array(
				'status'  => 'pinned',
				'enabled' => 'true',
				'until'   => $pin_until,
			)
        );
        $second_request = array(
            'body'     => $pin_until_body,
            'response' => $this->build_response( 'success' ),
        );
        $body           = $this->mock_remote_post_success( 'post_create', $second_request );

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
     * Sync_to_discourse when updating a post.
     */
    public function test_sync_to_discourse_when_updating() {
        // Set up a response body for updating an existing post.
        $body = $this->mock_remote_post_success( 'post_update' );
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
        $response  = $this->build_response( 'success' );
        $raw_body  = $this->response_body_json( 'post_update' );
        $body = json_decode( $raw_body );
        $body->post->deleted_at = '2021-03-10T23:06:05.328Z';
        $response['body'] = json_encode( $body );
        $this->mock_remote_post( $response );

        // Add a post that's already been published to Discourse.
        $discourse_post_id                            = $body->post->id;
        $post_atts                                    = self::$post_atts;
        $post_atts['meta_input']['discourse_post_id'] = $discourse_post_id;
        $post_id                                      = wp_insert_post( $post_atts, false, false );

        // Run the update.
        update_post_meta( $post_id, 'update_discourse_topic', 1 );
        $post = get_post( $post_id );
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
        $featured_link_body = http_build_query(
            array(
				'featured_link' => get_permalink( $post_id ),
			)
            );
        $second_request     = array(
            'body'     => $featured_link_body,
            'response' => $this->build_response( 'success' ),
        );
        $body               = $this->mock_remote_post_success( 'post_update', $second_request );
        $post               = $body->post;

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
        $body = $this->mock_remote_post_success( 'post_update' );
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
     * Successful remote_post request returns original response.
     */
    public function test_remote_post_success() {
        $success_response = $this->build_response( 'success' );
        $this->mock_remote_post( $success_response );
        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $success_response );
    }

    /**
     * Forbidden remote_post request returns standardised WP_Error and creates correct log.
     */
    public function test_remote_post_forbidden() {
        $raw_response = $this->build_response( 'forbidden' );
        $this->mock_remote_post( $raw_response );

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
        $this->mock_remote_post( $raw_response );

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
        $this->mock_remote_post(
            new WP_Error(
                'http_request_failed',
                'cURL error 7: Failed to connect to localhost port 3000: Connection refused'
            )
        );

        $response = $this->publish->remote_post( ...self::$remote_post_params );
        $this->assertEquals( $response, $this->build_post_error() );

        $log = $this->get_last_log();
        $this->assertRegExp( '/publish.ERROR: create_post.post_error/', $log );
    }

    /**
     * Mock remote post response.
     *
     * @param object $response Remote post response object.
     * @param object $second_request Second request response of second request in tested method.
     */
    protected function mock_remote_post( $response, $second_request = null ) {
        add_filter(
            'pre_http_request',
            function( $prempt, $args, $url ) use ( $response, $second_request ) {
                if ( ! empty( $second_request ) && ( $second_request['body'] === $args['body'] ) ) {
                    return $second_request['response'];
                } else {
                    return $response;
                }
            },
            10,
            3
        );
    }

    /**
     * Mock remote post success.
     *
     * @param string $type Type of response.
     * @param object $second_request Second request response of second request in tested method.
     */
    protected function mock_remote_post_success( $type, $second_request = null ) {
        $raw_body         = $this->response_body_json( $type );
        $response         = $this->build_response( 'success' );
        $response['body'] = $raw_body;
        $this->mock_remote_post( $response, $second_request );
        return json_decode( $raw_body );
    }

    /**
     * Build error returned by discourse-publish when post request fails.
     */
    protected function build_post_error() {
        $message = __( 'An error occurred when communicating with Discourse', 'wp-discourse' );
        return new WP_Error( 'discourse_publishing_response_error', $message );
    }

    /**
     * Build error returned by discourse-publish when response body is invalid.
     */
    protected function build_body_error() {
        $message = __( 'An invalid response was returned from Discourse', 'wp-discourse' );
        return new WP_Error( 'discourse_publishing_response_error', $message );
    }
    
    /**
     * Build an error notice returned by discourse-publish when post queued or topic deleted.
     */
    protected function build_notice( $message ) {
        return new WP_Error( 'discourse_publishing_response_notice', $message );
    }

    /**
     * Get last line in latest log file.
     */
    protected function get_last_log() {
    		$manager   = new FileManager();
    		$log_files = glob( $manager->logs_dir . '/*.log' );
    		$log_file  = $log_files[0];
        return shell_exec( "tail -n 1 $log_file" );
    }

    /**
     * Clear all logs.
     */
    protected function clear_logs() {
    		$manager   = new FileManager();
    		$log_files = glob( $manager->logs_dir . '/*.log' );

    		foreach ( $log_files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
      	    }
    		}
  	}

    /**
     * Get fixture with response body.
     *
     * @param string $file Name of response body file.
     */
    protected function response_body_file( $file ) {
        return file_get_contents( __DIR__ . "/../fixtures/response_body/$file.json" );
    }

    /**
     * Build JSON of response body.
     *
     * @param string $type Type of response.
     * @param string $sub_type Sub-type of response.
     * @param string $action_type Action type of test.
     */
    protected function response_body_json( $type, $sub_type = null, $action_type = 'create_post' ) {
        if ( in_array( $type, array( 'post_create', 'post_update' ), true ) ) {
            return $this->response_body_file( $type );
        }
        if ( 'unprocessable' === $type ) {
            $messages     = array(
                'title' => 'Title seems unclear, most of the words contain the same letters over and over?',
                'embed' => 'Embed url has already been taken',
            );
            $message_type = $sub_type;
        } else {
            $messages     = array(
                'invalid_parameters' => "You supplied invalid parameters to the request: $sub_type",
                'forbidden'          => 'You are not permitted to view the requested resource. The API username or key is invalid.',
            );
            $message_type = $type;
        }
        return wp_json_encode(
            array(
                'action'     => $action_type,
                'errors'     => array( $messages[ $message_type ] ),
                'error_type' => $type,
            )
        );
    }

    /**
     * Build remote post response.
     *
     * @param string $type Type of response.
     * @param string $sub_type Sub-type of response.
     */
    protected function build_response( $type, $sub_type = null ) {
        $codes    = array(
            'success'            => 200,
            'invalid_parameters' => 400,
            'forbidden'          => 403,
            'unprocessable'      => 422,
        );
        $messages = array(
            'success'            => 'OK',
            'invalid_parameters' => 'Bad Request',
            'forbidden'          => 'Forbidden',
            'unprocessable'      => 'Unprocessable Entity',
        );
        if ( in_array( $type, array( 'invalid_parameters', 'unprocessable' ), true ) ) {
            $body = $this->response_body_json( $type, $sub_type );
        } else {
            $body = array(
                'success'   => '{}',
                'forbidden' => 'You are not permitted to view the requested resource. The API username or key is invalid.',
            )[ $type ];
        }
        return array(
            'headers'  => array(),
            'body'     => $body,
            'response' => array(
                'code'    => $codes[ $type ],
                'message' => $messages[ $type ],
            ),
        );
    }

    /**
     * Initialize static variables used by test class.
     */
    public static function initialize_static_variables() {
        self::$discourse_url = 'http://meta.discourse.org';

        self::$remote_post_params = array(
            self::$discourse_url,
			array(
				'timeout' => 30,
				'method'  => 'POST',
				'headers' => array(
					'Api-Key'      => '1234',
					'Api-Username' => 'angus',
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

        self::$post_atts = array(
            'post_author'  => 0,
            'post_content' => 'This is a new post',
            'post_title'   => 'This is the post title',
            'meta_input'   => array(
				'wpdc_auto_publish_overridden' => 0,
				'publish_to_discourse'         => 1,
				'publish_post_category'        => 1,
            ),
            'post_status'  => 'publish',
        );

        self::$plugin_options = array(
            'url'                => self::$discourse_url,
            'api-key'            => '1235567',
            'publish-username'   => 'angus',
            'allowed_post_types' => array( 'post' ),
        );
    }
}

