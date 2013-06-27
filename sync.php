<?php
// this script can be used to synchronize posts to Discourse
// there appears to be bugs in publish_future_post
// see: http://wordpress.org/support/topic/is-publish_future_post-hook-still-present-working
require_once("../wp-load.php");

$discourse = new Discourse();

$args = array('numberposts' => '3','orderby' => 'date', 'post_type' => array('post'));
$last_posts = get_posts($args);
foreach( $last_posts as $post ) : setup_postdata($post);
  $link = get_post_meta($post->ID, 'discourse_permalink', true);
  if(!$link){
    $pub = get_post_meta($post->ID, 'publish_to_discourse', true);
    if($pub){
      $discourse::sync_to_discourse($post->ID, $post->post_title, $post->post_content);
      wp_cache_post_change($post->ID);
    }
  }
endforeach;
?>
