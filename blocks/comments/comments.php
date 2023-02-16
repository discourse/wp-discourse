<?php
/**
 * Server-side rendering of wp-discourse blocks.
 *
 * @package WPDiscourse
 */

 use \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter;
 use \WPDiscourse\DiscourseComment\DiscourseComment;

 /**
  * Renders the `wp-discourse/comments` block on the server.
  *
  * @param array    $attributes Block attributes.
  * @param string   $content    Block default content.
  * @param WP_Block $block      Block instance.
  * @return string Returns the filtered post comments for the current post wrapped inside "p" tags.
  */
function render_block_wpdc_comments( $attributes, $content, $block ) {
	$post_id = $block->context['postId'];
  if ( ! isset( $post_id ) ) {
		return '';
	}

	$comment_formatter = new DiscourseCommentFormatter();
	$comment           = new DiscourseComment( $comment_formatter );
  $default             = '';

  $comment->setup_options();
  $comment_formatter->setup_options();

  ob_start();

  $comment->comments_template( $default );
  $result = ob_get_contents();

  ob_end_clean();

	return $result;
}

/**
 * Registers the `wp-discourse/comments` block on the server.
 */
function register_wpdc_blocks() {
  if ( version_compare( get_bloginfo( 'version' ), '5.5', '>=' ) ) {
		register_block_type_from_metadata(
  		WPDISCOURSE_PATH . 'blocks/comments/build/block.json',
  		array(
  			'render_callback' => 'render_block_wpdc_comments',
  		)
		  );
  }
}
add_action( 'init', 'register_wpdc_blocks' );
