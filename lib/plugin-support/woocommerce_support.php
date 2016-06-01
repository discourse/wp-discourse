<?php
namespace WPDiscourse\PluginSupport;

class WooCommerceSupport {

  protected $discourse;

  function __construct( \Discourse $discourse ) {
    $this->discourse = $discourse;

    add_filter( 'woocommerce_login_redirect', array( $this, 'set_redirect' ) );
    add_filter( 'woocommerce_product_review_count', array( $this, 'comments_number' ) );
  }
  
  function comments_number( $count ) {
    global $post;
    $options = get_option( 'discourse' );
    if ( array_key_exists( 'allowed_post_types', $options ) && in_array( 'product', $options['allowed_post_types'] ) ) {
      $count = get_post_meta( $post->ID, 'discourse_comments_count', true );
      return $count;
    }
    return $count;
  }

  function set_redirect( $redirect ) {
    if ( array_key_exists( 'redirect_to', $_GET ) ) {
      $redirect = $_GET['redirect_to'];

      return $redirect;
    }

    return $redirect;
  }
}