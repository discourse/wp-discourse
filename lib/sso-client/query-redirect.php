<?php
/**
 * WP-Discourse query redirects for when Discourse is used as the SSO provider.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\SSOClient;

use \WPDiscourse\Shared\PluginUtilities;

/**
 * Class QueryRedirect
 */
class QueryRedirect {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * QueryRedirect constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'query_vars', array( $this, 'discourse_sso_custom_query_vars' ) );
		add_action( 'parse_query', array( $this, 'discourse_sso_url_redirect' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Adds the `discourse_sso` value to the wp_query
	 *
	 * @method discourse_sso_custom_query_vars
	 *
	 * @param  array $vars query vars.
	 *
	 * @return array
	 */
	public function discourse_sso_custom_query_vars( $vars ) {
		$vars[] = 'discourse_sso';

		return $vars;
	}

	/**
	 * Redirect user to the SSO provider
	 *
	 * @method discourse_sso_url_redirect
	 *
	 * @param  object $wp the wp_query.
	 */
	public function discourse_sso_url_redirect( $wp ) {
		if ( empty( $this->options['sso-client-enabled'] ) || 1 !== intval( $this->options['sso-client-enabled'] ) ) {

			return;
		}

		/**
		 * Sync logout from Discourse to WordPress from Adam Capirola : https://meta.discourse.org/t/wordpress-integration-guide/27531.
		 * To make this work, enter a URL of the form "http://my-wp-blog.com/?request=logout" in the "logout redirect"
		 * field in your Discourse admin.
		 */
		if ( isset( $_GET['request'] ) && 'logout' === $_GET['request'] ) { // Input var okay.
			wp_logout();
			wp_safe_redirect( $this->options['url'] );

			exit;
		}

		if ( empty( $wp->query['discourse_sso'] ) ) {

			return;
		}

		if ( ! empty( $_GET['redirect_to'] ) ) { // Input var okay.
			$redirect_to = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ); // Input var okay.
		} else {
			$redirect_to = home_url( '/' );
		}

		$payload = base64_encode(
			http_build_query(
				array(
					'nonce'          => Nonce::get_instance()->create( '_discourse_sso' ),
					'return_sso_url' => $redirect_to,
				)
			)
		);

		$request = array(
			'sso' => $payload,
			'sig' => hash_hmac( 'sha256', $payload, $this->options['sso-secret'] ),
		);

		$sso_login_url = $this->options['url'] . '/session/sso_provider?' . http_build_query( $request );

		wp_safe_redirect( $sso_login_url );

		exit;
	}
}
