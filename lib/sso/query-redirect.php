<?php
/**
 * Hooks that deals with query vars
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;
use \WPDiscourse\Nonce;

add_filter( 'query_vars', 'discourse_sso_custom_query_vars' );

/**
 * Adds the `discourse_sso` value to the wp_query
 *
 * @method discourse_sso_custom_query_vars
 *
 * @param  array $vars query vars.
 *
 * @return array
 */
function discourse_sso_custom_query_vars( $vars ) {
	$vars[] = 'discourse_sso';
	return $vars;
}

add_action( 'parse_query', 'discourse_sso_url_redirect' );

/**
 * Redirect user to the SSO provider
 *
 * @method discourse_sso_url_redirect
 *
 * @param  object $wp the wp_query.
 */
function discourse_sso_url_redirect( $wp ) {
	if ( empty( $wp->query['discourse_sso'] ) ) {
		return;
	}

	$discourse_options = DiscourseUtilities::get_options();

	if ( ! empty( $_GET['redirect_to'] ) ) {
		$redirect_to = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
	} else {
		$redirect_to = home_url( '/' );
	}

	$payload = base64_encode(http_build_query(array(
		'nonce' => Nonce::get_instance()->create( '_discourse_sso' ),
		'return_sso_url' => $redirect_to,
	)
	));

	$request = array(
		'sso' => $payload,
		'sig' => hash_hmac( 'sha256', $payload, $discourse_options['sso-secret'] ),
	);

	$sso_login_url = $discourse_options['url'] . '/session/sso_provider?' . http_build_query( $request );

	wp_redirect( $sso_login_url );
	exit;
}
