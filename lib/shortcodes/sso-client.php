<?php
/**
 * Shortcode for discourse SSO
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\SSOClient;

/**
 * Class SSOClientShortcode
 */
class SSOClientShortcode extends SSOClientBase {

	/**
	 * SSOClientShortcode constructor.
	 */
	public function __construct() {
		add_shortcode( 'discourse_sso_client', array( $this, 'discourse_sso_client_shortcode' ) );
	}

	/**
	 * Shortcode for SSO link.
	 *
	 * @method discourse_sso_client_shortcode
	 *
	 * @param  array $atts shortcode params.
	 *
	 * @return string
	 */
	public function discourse_sso_client_shortcode( $atts = array() ) {
		$options = shortcode_atts(
			array(
				'login'    => null,
				'link'     => null,
				'redirect' => null,
			), $atts
		);

		return $this->get_discourse_sso_link_markup( $options );
	}
}
