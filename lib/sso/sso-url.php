<?php
/**
 * Builds the SSO auth url
 *
 * @package WPDiscourse
 */

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

	return add_query_arg(
		array(
			'discourse_sso' => 1,
			'redirect_to' => rawurlencode( $redirect_to ),
		), home_url( '/' )
	);
}
