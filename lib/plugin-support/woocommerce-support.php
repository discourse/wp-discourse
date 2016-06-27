<?php
/**
 * Adds limitied support for the WooCommerce plugin.
 *
 * This file will soon be moved into its own plugin.
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/plugin-support/woocommerce_support.php
 * @package WPDiscourse\PluginSupport
 */

namespace WPDiscourse\PluginSupport;

/**
 * Class WooCommerceSupport
 */
class WooCommerceSupport {

	/**
	 * WooCommerceSupport constructor.
	 */
	function __construct() {
		add_filter( 'woocommerce_login_redirect', array( $this, 'set_redirect' ) );
		add_filter( 'woocommerce_product_review_count', array( $this, 'comments_number' ) );
	}

	/**
	 * Replaces the WooCommerce comments count with the Discourse comments count.
	 *
	 * @param int $count The comments count returned from WooCommerce.
	 *
	 * @return mixed
	 */
	function comments_number( $count ) {
		global $post;
		$options = get_option( 'discourse' );
		if ( array_key_exists( 'allowed_post_types', $options ) && in_array( 'product', $options['allowed_post_types'], true ) ) {
			$count = get_post_meta( $post->ID, 'discourse_comments_count', true );

			return $count;
		}

		return $count;
	}

	/**
	 * Sets the login redirect so that it can include the query parameters required for single sign on with Discourse.
	 *
	 * @param string $redirect The redirect URL supplied by WooCommerce.
	 *
	 * @return mixed
	 */
	function set_redirect( $redirect ) {
		if ( isset( $_GET['redirect_to'] ) && esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) { // Input var okay.
			$redirect = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ); // Input var okay.

			return $redirect;
		}

		return $redirect;
	}
}
