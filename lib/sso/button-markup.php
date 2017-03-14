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
	$discourse_options = DiscourseUtilities::get_options();
	$is_user_logged_in = is_user_logged_in();

	if ( $is_user_logged_in ) {
		if ( DiscourseUtilities::user_is_linked_to_sso() ) {

			return;
		}
		$link_account_text = ! empty( $discourse_options['link-to-discourse-text'] ) ? $discourse_options['link-to-discourse-text'] : '';
		$anchor = ! empty( $options['link'] ) ? $options['link'] : $link_account_text;
	} else {
		$login_text = ! empty( $discourse_options['external-login-text'] ) ? $discourse_options['external-login-text'] : '';
		$anchor = ! empty( $options['login'] ) ? $options['login'] : $login_text;
	}

	$sso_login_url = get_discourse_sso_url();

	$anchor = apply_filters( 'wpdc_sso_client_login_anchor', $anchor );
	$button = sprintf( '<a href="%s">%s</a>', $sso_login_url, $anchor );

	return apply_filters( 'wpdc_sso_client_login_button', $button, $sso_login_url, $options );
}
