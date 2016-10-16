<?php
/**
 * Hooks into the WordPress 'comments_number' filter to create the markup required to make an ajax request to get and
 * display the current comments number.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\AjaxComponents\CommentsNumber;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class CommentsNumber.
 */
class CommentsNumber {
	protected $options;

	/**
	 * CommentsNumber constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Get access to the plugin options.
	 */
	public function setup() {
		$this->options = DiscourseUtilities::get_options();

		add_action( 'wp', array( $this, 'set_hooks' ) );
	}

	/**
	 * Hook into the the 'comments_number' filter if the setting is enabled.
	 */
	public function set_hooks() {
		global $wp_query;

		if ( ( $wp_query->is_singular && ! empty( $this->options['ajax-refresh-comments-number'] ) &&
		       1 === intval( $this->options['ajax-refresh-comments-number'] ) ) ||
		     ( ! $wp_query->is_singular &&
		       ! empty( $this->options['ajax-refresh-archive-comments-number'] ) &&
		       1 === intval( $this->options['ajax-refresh-archive-comments-number'] ) )
		) {
			add_filter( 'comments_number', array( $this, 'comments_number_ajax_placeholder' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'comments_number_script' ) );
		}
	}

	/**
	 * Register, localize, and enqueue script.
	 */
	public function comments_number_script() {
		$single_reply_text = ! empty( $this->options['single-reply-text'] ) ? esc_html( $this->options['single-reply-text'] ) : 'Reply';
		$many_replies_text = ! empty( $this->options['many-replies-text'] ) ? esc_html( $this->options['many-replies-text'] ) : 'Replies';
		$no_replies_text   = ! empty( $this->options['no-replies-text'] ) ? esc_html( $this->options['no-replies-text'] ) : 'No Replies';

		wp_register_script( 'comments_number_js', plugins_url( '/../../js/comments-number.js', __FILE__ ), array( 'jquery' ), null, true );
		wp_localize_script( 'comments_number_js', 'commentsNumberScript', array(
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'singleReplyText' => $single_reply_text,
			'manyRepliesText' => $many_replies_text,
			'noRepliesText'   => $no_replies_text,
		) );

		wp_register_style( 'loading_spinner_css', plugins_url( '/../../css/ajax-styles.css', __FILE__ ) );

		wp_enqueue_script( 'comments_number_js' );
		wp_enqueue_style( 'loading_spinner_css' );
	}

	/**
	 * Adds a span to the page that supplies data for the ajax script.
	 *
	 * @param string $output The comments_number string returned from WordPress.
	 * @param int    $number The number of comments.
	 */
	public function comments_number_ajax_placeholder( $output, $number ) {
		global $post;
		$post_id    = $post->ID;
		$nonce_name = 'discourse_comments_number_' . $post_id;
		echo '<span class="wp-discourse-comments-number-ajax wp-discourse-comments-number-loading" id="wp-discourse-comments-number-span-' . esc_attr( $post_id ) . '" 
		data-post-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( $nonce_name ) ) . '" data-nonce-name="' .
		     esc_attr( $nonce_name ) . '" data-old-number="' . esc_attr( $number ) . '"></span>';
	}
}


