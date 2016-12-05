<?php
/**
 * Shortcode for discourse SSO
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;


/**
 * Gets a auth URL for discourse
 *
 * @param  array $options anchor, link.
 *
 * @return string.
 */
function get_discourse_sso_url( $options = array() ) {
	$discourse_options = DiscourseUtilities::get_options();

	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( get_user_meta( $user->ID, 'discourse_sso_user_id', true ) ) {
			return;
		}
		$anchor = ! empty( $options['link'] ) ? $options['link'] : __( 'Link your account to Discourse', 'wp-discourse' );
	} else {
		$anchor = ! empty( $options['login'] ) ? $options['login'] : __( 'Log in with Discourse', 'wp-discourse' );
	}

	$nonce = hash( 'sha512', mt_rand() );

	$redirect_to = get_permalink();

	if ( empty( $redirect_to ) ) {
		$redirect_to = home_url( '/' );
	}

	$payload = base64_encode(http_build_query(array(
		'nonce' => $nonce,
		'return_sso_url' => $redirect_to,
		)
	));

	$request = array(
		'sso' => $payload,
		'sig' => hash_hmac( 'sha256', $payload, $discourse_options['sso-secret'] ),
	);

	$sso_login_url = $discourse_options['url'] . '/session/sso_provider?' . http_build_query( $request );

	$anchor = sprintf( '<a href="%s">%s</a>', $sso_login_url, $anchor );

	return apply_filters( 'discourse_as_sso_provider_login_anchor', $anchor, $sso_login_url, $options, $discourse_options );
}

/**
 * Shortcode for SSO link
 *
 * @method discourse_sso_shortcode
 *
 * @param  array $atts shortcode params.
 *
 * @return string markup
 */
function discourse_sso_shortcode( $atts = array() ) {
	$options = shortcode_atts(array(
		'login' => null,
		'link' => null,
	), $atts);

	return get_discourse_sso_url( $options );
}

add_shortcode( 'discourse_sso', 'discourse_sso_shortcode' );
