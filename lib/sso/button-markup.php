<?php
/**
 * Builds the SSO auth button
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

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

	$anchor = apply_filters( 'discourse/sso/client/login_anchor', $anchor );
	$button = sprintf( '<a href="%s">%s</a>', $sso_login_url, $anchor );

	return apply_filters( 'discourse/sso/client/login_button', $button, $sso_login_url, $options );
}
