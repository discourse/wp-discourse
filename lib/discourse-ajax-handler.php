<?php
/**
 * Handles ajax requests.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseAjaxHandler;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseAjaxHandler
 */
class DiscourseAjaxHandler {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * DiscourseAjaxHandler constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Setup options an add ajax action hooks.
	 */
	public function setup() {
		$this->options = DiscourseUtilities::get_options();

		add_action( 'wp_ajax_get_discourse_comments_number', array( $this, 'get_discourse_comments_number' ) );
		add_action( 'wp_ajax_nopriv_get_discourse_comments_number', array( $this, 'get_discourse_comments_number' ) );
	}

	/**
	 * Responds to a POST request that has the `get_discourse_comments_number` action.
	 *
	 * Requires POST variables for `nonce`, `nonce_name`, `post_id`, and `location`.
	 */
	public function get_discourse_comments_number() {

		if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['nonce_name'] ) || // Input var okay.
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), sanitize_key( wp_unslash( $_POST['nonce_name'] ) ) ) ) { // Input var okay.
			$this->ajax_error_response();

			exit;
		}

		$location = ! empty( $_POST['location'] ) ? sanitize_key( wp_unslash( $_POST['location'] ) ) : null; // Input var okay.
		$post_id      = ! empty( $_POST['post_id'] ) ? sanitize_key( wp_unslash( $_POST['post_id'] ) ) : null; // Input var okay.

		$comment_count = get_transient( $location );
		if ( false === $comment_count ) {

			if ( ! $location || ! $post_id ) {
				$this->ajax_error_response();

				exit;
			}

			if ( ! $discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true ) ) {
				$this->ajax_error_response();

				exit;
			}

			$discourse_permalink = esc_url_raw( $discourse_permalink ) . '.json';

			$response = wp_remote_get( $discourse_permalink );

			if ( ! DiscourseUtilities::validate( $response ) ) {
				$this->ajax_error_response();

				exit;
			}

			$json = json_decode( $response['body'] );
			if ( isset( $json->posts_count ) ) {
				$comment_count = intval( $json->posts_count ) - 1;
				update_post_meta( $post_id, 'discourse_comments_count', $comment_count );

				// Todo: make this configurable.
				$cache_duration = apply_filters( 'wp_discourse_comment_count_sync_period' , 10 * MINUTE_IN_SECONDS );
				set_transient( $location, $comment_count, 10 * $cache_duration );
			} else {
				$this->ajax_error_response();

				exit;
			}
		}

		header( 'Content-type: application/json' );
		$ajax_response['status']         = 'success';
		$ajax_response['comments_count'] = $comment_count;

		echo wp_json_encode( $ajax_response );

		exit;
	}

	/**
	 * Echoes an error response.
	 */
	protected function ajax_error_response() {
		header( 'Content-type: application/json' );
		$ajax_response['status'] = 'error';

		echo wp_json_encode( $ajax_response );
	}
}
