<?php
/**
 * Shortcode for discourse SSO
 *
 * @package WPDiscourse
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Shortcode for SSO link
 *
 * @method discourse_sso_client_shortcode
 *
 * @param  array $atts shortcode params.
 *
 * @return string markup
 */
function discourse_sso_client_shortcode( $atts = array() ) {
	$options = shortcode_atts(array(
		'login' => null,
		'link' => null,
	), $atts);

	return get_discourse_sso_link_markup( $options );
}

add_shortcode( 'discourse_sso_client', 'discourse_sso_client_shortcode' );
