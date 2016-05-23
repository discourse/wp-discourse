<?php
namespace WPDiscourse\PluginSupport;

class WooCommerceSupport {
  private static $instance = null;
  protected $comments_number_filter = 'woocommerce_product_review_count';

  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  private function __construct() {
    add_filter( 'woocommerce_login_redirect', array( $this, 'set_redirect' ) );
    add_filter( 'woocommerce_product_review_count', array( $this, 'comments_number' ) );
  }

  function set_redirect( $redirect ) {
    if ( array_key_exists( 'redirect_to', $_GET ) ) {
      $redirect = $_GET['redirect_to'];
      return $redirect;
    }
    return $redirect;
  }

  function comments_number() {
    global $post;
    $count = get_post_meta( $post->ID, 'discourse_comments_count', true );
    return $count ? $count : 0;
  }
}