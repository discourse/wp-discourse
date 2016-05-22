<?php
namespace WPDiscourse\PluginSupport\WoocommerceSupport;

class WoocommerceSupport {
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
  }
  
  function set_redirect( $redirect ) {
    if ( array_key_exists( 'redirect_to', $_GET ) ) {
      $redirect = $_GET['redirect_to'];
      return $redirect;
    }
    return $redirect;
  }

  function get_comments_number_filter() {
    return $this->comments_number_filter;
  }

}