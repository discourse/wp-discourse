<?php
/**
 * The template for when Discourse comments are being loaded with ajax.
 *
 * @package WPDiscourse
 */

global $post;

echo '<div class="wpdc-comments-loading" id="wpdc-comments" data-post-id="' . esc_attr( $post->ID ) . '"></div>';
