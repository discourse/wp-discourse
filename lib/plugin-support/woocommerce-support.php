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

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class WooCommerceSupport
 */
class WooCommerceSupport {
	protected $options;

	/**
	 * WooCommerceSupport constructor.
	 */
	function __construct() {
		$this->options = DiscourseUtilities::get_options( 'discourse_connection' );

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
		if ( array_key_exists( 'allowed_post_types', $this->options ) && in_array( 'product', $$this->options['allowed_post_types'], true ) ) {
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
