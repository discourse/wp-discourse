<?php
namespace WPDiscourse\PluginSupport;

class WooCommerceSupport {

  protected $discourse;

  function __construct( \Discourse $discourse ) {
    $this->discourse = $discourse;
    add_filter( 'woocommerce_login_redirect', array( $this, 'set_redirect' ) );
    add_filter( 'woocommerce_product_review_count', array( $this->discourse, 'comments_number' ) );
  }

  function set_redirect( $redirect ) {
    if ( array_key_exists( 'redirect_to', $_GET ) ) {
      $redirect = $_GET['redirect_to'];

      return $redirect;
    }

    return $redirect;
  }
}