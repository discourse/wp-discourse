<?php
/**
 * Syncs and displays Discourse content through ajax calls.
 *
 * This class allows current Discourse content to be displayed on cached WordPress pages.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseAjaxContent;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseAjaxContent
 */
class DiscourseAjaxContent {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * DiscourseAjaxContent constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Setup options and conditionally add hooks.
	 */
	public function setup() {
		$this->options = DiscourseUtilities::get_options();

		if ( ! empty( $this->options['ajax-refresh-comments-number'] ) && 1 === intval( $this->options['ajax-refresh-comments-number'] ) ||
		     ! empty( $this->options['ajax-refresh-archive-comments-number'] ) && 1 === intval( $this->options['ajax-refresh-archive-comments-number'] )
		) {
			add_action( 'wp', array( $this, 'adjust_hooks' ) );
			add_action( 'wp_ajax_nopriv_get_discourse_comments_number', array(
				$this,
				'handle_comments_number_ajax_request',
			) );
			add_action( 'wp_ajax_get_discourse_comments_number', array(
				$this,
				'handle_comments_number_ajax_request',
			) );
		}

		if ( ! empty( $this->options['ajax-refresh-comments'] ) && 1 === intval( $this->options['ajax-refresh-comments'] ) ) {
			add_filter( 'wp_discourse_comments_content', array( $this, 'comments_content_ajax_placeholder' ), 10, 2 );
			add_action( 'wp_ajax_nopriv_get_discourse_comments_content', array(
				$this,
				'handle_comments_content_ajax_request',
			) );
			add_action( 'wp_ajax_get_discourse_comments_content', array(
				$this,
				'handle_comments_content_ajax_request',
			) );
			add_action( 'wp_enqueue_scripts', array( $this, 'comments_content_script' ) );
		}
	}

	/**
	 * Filter 'comments_number' and add 'wp_enqueue_scripts' action.
	 */
	public function adjust_hooks() {
		global $wp_query;

		if ( ( $wp_query->is_singular && ! empty( $this->options['ajax-refresh-comments-number'] ) && 1 === intval( $this->options['ajax-refresh-comments-number'] ) ) ||
		     ( ! $wp_query->is_singular && ! empty( $this->options['ajax-refresh-archive-comments-number'] ) && 1 === intval( $this->options['ajax-refresh-archive-comments-number'] ) ) ) {
			$post_id = $wp_query->post->ID;
//			if ( $post_id &&
//			     ! empty( $this->options['use-discourse-comments'] ) && 1 === intval( $this->options['use-discourse-comments'] ) &&
//			     1 === intval( get_post_meta( $post_id, 'publish_to_discourse', true ) )
//			) {
				add_filter( 'comments_number', array( $this, 'comments_number_ajax_placeholder' ), 10, 2 );
				add_action( 'wp_enqueue_scripts', array( $this, 'comments_number_script' ) );
			}
//		}
	}

	/**
	 * Register, localize, and enqueue script.
	 */
	public function comments_number_script() {
		$single_reply_text = ! empty( $this->options['single-reply-text'] ) ? esc_html( $this->options['single-reply-text'] ) : 'Reply';
		$many_replies_text = ! empty( $this->options['many-replies-text'] ) ? esc_html( $this->options['many-replies-text'] ) : 'Replies';
		$no_replies_text   = ! empty( $this->options['no-replies-text'] ) ? esc_html( $this->options['no-replies-text'] ) : 'No Replies';

		wp_register_script( 'comments_number_js', plugins_url( '/../js/comments-number.js', __FILE__ ), array( 'jquery' ), null, true );
		wp_localize_script( 'comments_number_js', 'comments_number_script', array(
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'single_reply_text' => $single_reply_text,
			'many_replies_text' => $many_replies_text,
			'no_replies_text'   => $no_replies_text,
		) );
		wp_enqueue_script( 'comments_number_js' );
	}

	/**
	 * Register, localize, and enqueue script.
	 */
	public function comments_content_script() {
		wp_register_script( 'comments_content_js', plugins_url( '/../js/comments-content.js', __FILE__ ), array( 'jquery' ), null, true );
		wp_localize_script( 'comments_content_js', 'comments_content_script', array(
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
		) );
		wp_enqueue_script( 'comments_content_js' );
	}

	/**
	 * ----------------
	 * Comments number.
	 * ----------------
	 */

	/**
	 * Adds a span to the page that supplies data for the ajax script.
	 *
	 * @param string $output The comments_number string returned from WordPress.
	 * @param int $number The number of comments.
	 */
	public function comments_number_ajax_placeholder( $output, $number ) {
		global $post;
		$post_id    = $post->ID;
		$nonce_name = 'discourse_comments_number_' . $post_id;
		echo '<span class="wp-discourse-comments-number-ajax wp-discourse-comments-number-loading" id="wp-discourse-comments-number-span-' . esc_attr( $post_id ) . '"' .
		     ' data-post-id="' . esc_attr( $post_id ) . '" data-nonce="' . wp_create_nonce( $nonce_name ) . '" data-nonce-name="' .
		     $nonce_name . '" data-old-number="' . esc_attr( $number ) . '"></span>';
	}

	/**
	 * Handles the ajax request.
	 */
	public function handle_comments_number_ajax_request() {
		$nonce_name   = ! empty( $_POST['nonce_name'] ) ? sanitize_key( wp_unslash( $_POST['nonce_name'] ) ) : null;
		$nonce        = ! empty( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		$current_span = ! empty( $_POST['current_span'] ) ? sanitize_key( wp_unslash( $_POST['current_span'] ) ) : null;
		$post_id      = ! empty( $_POST['post_id'] ) ? sanitize_key( wp_unslash( $_POST['post_id'] ) ) : null;

		$comment_count = get_transient( $current_span );
		if ( false === $comment_count ) {

			if ( ! $nonce_name || ! $nonce || ! $current_span || ! $post_id ) {
				$this->ajax_error_response();

				exit;
			}

			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $nonce_name ) ) {
				$this->ajax_error_response();

				exit;
			}

			if ( ! $discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true ) ) {
				$this->ajax_error_response();

				exit;
			}

			$discourse_permalink = esc_url_raw( $discourse_permalink ) . '.json';
			write_log($discourse_permalink);

			$response = wp_remote_get( $discourse_permalink );

			if ( ! DiscourseUtilities::validate( $response ) ) {
				$this->ajax_error_response();

				exit;
			}

			$json = json_decode( $response['body'] );
			if ( isset( $json->posts_count ) ) {
				$comment_count = intval( $json->posts_count ) - 1;
				write_log($comment_count);
				update_post_meta( $post_id, 'discourse_comments_count', $comment_count );

				// Todo: make this configurable.
				set_transient( $current_span, $comment_count, 10 * MINUTE_IN_SECONDS );
			} else {
				$this->ajax_error_response();

				exit;
			}
		}

		header( 'Content-type: application/json' );
		$ajax_response['status']         = 'success';
		$ajax_response['comments_count'] = $comment_count;

		echo json_encode( $ajax_response );

		exit;
	}

	/**
	 * -----------------
	 * Comments content.
	 * -----------------
	 */

	public function comments_content_ajax_placeholder( $discourse_html, $permalink ) {

	}

	public function handle_comments_content_ajax_request() {

	}


	/**
	 * Echoes an error response.
	 */
	protected function ajax_error_response() {
		header( 'Content-type: application/json' );
		$ajax_response['status'] = 'error';

		echo json_encode( $ajax_response );
	}

}