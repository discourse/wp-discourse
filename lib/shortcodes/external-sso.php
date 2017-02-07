<?php
/**
 * Shortcode for discourse SSO
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

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
	$is_user_logged_in = is_user_logged_in();

	if ( ! empty( $_GET['redirect_to'] ) ) {
		$redirect_to = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
	} else {
		$redirect_to = home_url( '/' );
	}

	$payload = base64_encode(http_build_query(array(
		'nonce' => \WPDiscourse\Nonce::get_instance()->create( '_discourse_sso' ),
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

/**
 * Gets the auth URL for discourse
 *
 * @return string.
 */
function get_discourse_sso_url() {
	$is_user_logged_in = is_user_logged_in();

	$redirect_to = get_permalink();

	if ( empty( $redirect_to ) ) {
		$redirect_to = $is_user_logged_in ? admin_url( 'profile.php' ) : home_url( '/' );
	}

	return add_query_arg( array(
		'discourse_sso' => 1,
		'redirect_to' => $redirect_to,
	), home_url( '/' ) );
}


/**
 * Generates the markup for SSO link
 *
 * @method get_discourse_sso_link_markup
 *
 * @param  array $options anchor, link.
 *
 * @return string
 */
function get_discourse_sso_link_markup( $options = array() ) {
	$is_user_logged_in = is_user_logged_in();

	if ( $is_user_logged_in ) {
		if ( DiscourseUtilities::user_is_linked_to_sso() ) {
			return;
		}
		$anchor = ! empty( $options['link'] ) ? $options['link'] : __( 'Link your account to Discourse', 'wp-discourse' );
	} else {
		$anchor = ! empty( $options['login'] ) ? $options['login'] : __( 'Log in with Discourse', 'wp-discourse' );
	}

	$sso_login_url = get_discourse_sso_url();

	$anchor = sprintf( '<a href="%s">%s</a>', $sso_login_url, $anchor );

	return apply_filters( 'discourse/sso/client/login_anchor', $anchor, $sso_login_url, $options );
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

	return get_discourse_sso_link_markup( $options );
}

add_shortcode( 'discourse_sso', 'discourse_sso_shortcode' );
