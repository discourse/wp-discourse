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
    add_action( 'woocommerce_login_form_end', array( $this, 'set_redirect' ) );
  }

  // The WooCommerce login form handler (`WC_Form_Handler::process_login`) tries to set the
  // after-login redirect path based on `$_POST['redirect']`. If that isn't set, it falls back to
  // the 'myaccount' page permalink. This function sets a hidden 'redirect' field based on the
  // request's 'redirect_to' value. Doing it this way allows the values for 'sso' and 'sig' to
  // be passed to the redirect.
  function set_redirect() {
    if ( array_key_exists( 'redirect_to', $_GET ) ) {
      $redirect = $_GET['redirect_to'];
      echo '<input type="hidden" name="redirect" value="'. $redirect . '">';
    }
  }

  function get_comments_number_filter() {
    return $this->comments_number_filter;
  }

}