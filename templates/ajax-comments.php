<?php
/**
 * The template for when Discourse comments are being loaded with ajax.
 *
 * @package WPDiscourse
 */

global $post;

echo '<div id="wpdc-comments" data-post-id="' . esc_attr( $post->ID ) . '"></div>';
