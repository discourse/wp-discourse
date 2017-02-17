<?php

namespace WPDiscourse\sso;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;
use \WPDiscourse\Nonce;

class QueryRedirect {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'query_vars', array( $this, 'discourse_sso_custom_query_vars' ) );
		add_action( 'parse_query', array( $this, 'discourse_sso_url_redirect' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
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
		if ( empty( $this->options['sso-client-enabled'] ) || 1 !== intval( $this->options['sso-client-enabled'] ) ||
		     empty( $wp->query['discourse_sso'] )
		) {
			return;
		}

		if ( ! empty( $_GET['redirect_to'] ) ) {
			$redirect_to = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
		} else {
			$redirect_to = home_url( '/' );
		}

		$payload = base64_encode( http_build_query( array(
				'nonce'          => Nonce::get_instance()->create( '_discourse_sso' ),
				'return_sso_url' => $redirect_to,
			)
		) );

		$request = array(
			'sso' => $payload,
			'sig' => hash_hmac( 'sha256', $payload, $this->options['sso-secret'] ),
		);

		$sso_login_url = $this->options['url'] . '/session/sso_provider?' . http_build_query( $request );

		wp_redirect( $sso_login_url );
		exit;
	}


}