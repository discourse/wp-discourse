<?php
/**
 * Syncs and displays Discourse content through ajax calls.
 */

namespace WPDiscourse\DiscourseAjaxContent;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseAjaxContent {
	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	public function setup() {
		$this->options = DiscourseUtilities::get_options();

		if ( ! empty( $this->options['ajax-refresh-comments-number'] ) ) {
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
	}

	public function adjust_hooks() {
		global $wp_query;

		if ( $wp_query->is_singular() ) {
			$post_id = $wp_query->post->ID;
			if ( $post_id &&
			     ! empty( $this->options['use-discourse-comments'] ) && 1 === intval( $this->options['use-discourse-comments'] ) &&
			     1 === intval( get_post_meta( $post_id, 'publish_to_discourse', true ) )
			) {
				add_filter( 'comments_number', array( $this, 'comments_number_ajax_placeholder' ), 10, 2 );
				add_action( 'wp_enqueue_scripts', array( $this, 'comments_number_script' ) );
			}
		}
	}

	public function comments_number_script() {
		$single_reply_text = ! empty( $this->option['single-reply-text'] ) ? esc_html( $this->options['single-reply-text'] ) : 'Reply';
		$many_replies_text = ! empty( $this->options['many-replies-text'] ) ? esc_html( $this->options['many-replies-text'] ) : 'Replies';
		// Todo: add an option for this.
		$no_replies_text = ! empty( $this->options['no-replies-text'] ) ? esc_html( $this->options['many-replies-text'] ) : 'No Replies';

		wp_register_script( 'comments_number_js', plugins_url( '/../js/comments-number.js', __FILE__ ), array( 'jquery' ), null, true );
		wp_localize_script( 'comments_number_js', 'comments_number_script', array(
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'single_reply_text' => $single_reply_text,
			'many_replies_text' => $many_replies_text,
			'no_replies_text'   => $no_replies_text,
		) );
		wp_enqueue_script( 'comments_number_js' );
	}

	public function comments_number_ajax_placeholder( $output, $number ) {
		global $post;
		$post_id    = $post->ID;
		$nonce_name = 'discourse_comments_number_' . $post_id;
		echo '<span class="wp-discourse-comments-number-ajax wp-discourse-comments-number-loading" id="wp-discourse-comments-number-span-' . esc_attr( $post_id ) . '"' .
		     ' data-post-id="' . esc_attr( $post_id ) . '" data-nonce="' . wp_create_nonce( $nonce_name ) . '" data-nonce-name="' .
		     $nonce_name . '" data-old-number="' . esc_attr( $number ) . '"></span>';
	}

	public function handle_comments_number_ajax_request() {
		$nonce_name   = ! empty( $_POST['nonce_name'] ) ? sanitize_key( wp_unslash( $_POST['nonce_name'] ) ) : null;
		$nonce        = ! empty( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : null;
		$current_span = ! empty( $_POST['current_span'] ) ? sanitize_key( wp_unslash( $_POST['current_span'] ) ) : null;
		$post_id = ! empty( $_POST['post_id'] ) ? sanitize_key( wp_unslash( $_POST['post_id'] ) ) : null;

		$comment_count = get_transient( $current_span );
		if ( empty( $comment_count ) ) {

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
				set_transient( $current_span, $comment_count, 10 * MINUTE_IN_SECONDS );
			} else {
				$this->ajax_error_response();

				exit;
			}
		}

		header( 'Content-type: application/json' );
		$ajax_response['status'] = 'success';
		$ajax_response['comments_count'] = $comment_count;

		echo json_encode( $ajax_response );

		exit;
	}

	protected function ajax_error_response() {
		header( 'Content-type: application/json' );
		$ajax_response['status'] = 'error';

		echo json_encode( $ajax_response );
	}

}