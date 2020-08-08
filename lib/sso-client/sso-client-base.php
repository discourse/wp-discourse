<?php
/**
 * SSO Client base class.
 *
 * @package WPDiscourse.
 */

namespace WPDiscourse\SSOClient;

use \WPDiscourse\Shared\PluginUtilities;

/**
 * Class SSOClientBase
 */
class SSOClientBase {
	use PluginUtilities;

	/**
	 * Generates the markup for SSO link
	 *
	 * @method get_discourse_sso_link_markup
	 *
	 * @param  array $options anchor, link.
	 *
	 * @return string
	 */
	protected function get_discourse_sso_link_markup( $options = array() ) {
		$discourse_options = $this->get_options();
		$user_id           = get_current_user_id();

		if ( ! empty( $user_id ) ) {
			if ( get_user_meta( $user_id, 'discourse_sso_user_id', true ) ) {

				return null;
			}
			$link_account_text = ! empty( $discourse_options['link-to-discourse-text'] ) ? $discourse_options['link-to-discourse-text'] : '';
			$anchor            = ! empty( $options['link'] ) ? $options['link'] : $link_account_text;
		} else {
			$login_text = ! empty( $discourse_options['external-login-text'] ) ? $discourse_options['external-login-text'] : '';
			$anchor     = ! empty( $options['login'] ) ? $options['login'] : $login_text;
		}

		if ( isset( $_GET['redirect_to'] ) ) {
			$redirect_to = wp_validate_redirect(
				esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ),
				null
			);
		} elseif ( ! empty( $options['redirect'] ) ) {
			$redirect_to = $options['redirect'];
		} else {
			$redirect_to = null;
		}
		$sso_login_url = $this->get_discourse_sso_url( $redirect_to );

		$anchor = apply_filters( 'wpdc_sso_client_login_anchor', $anchor );
		$button = sprintf( '<a class="wpdc-sso-client-login-link" href="%s">%s</a>', esc_url( $sso_login_url ), sanitize_text_field( $anchor ) );

		return apply_filters( 'wpdc_sso_client_login_button', $button, $sso_login_url, $options );
	}

	/**
	 * Gets the auth URL for discourse.
	 *
	 * @param string|null $redirect The URL to redirect to.
	 *
	 * @return string
	 */
	protected function get_discourse_sso_url( $redirect = null ) {
		$is_user_logged_in = is_user_logged_in();

		$redirect_to = $redirect ? $redirect : get_permalink();

		if ( empty( $redirect_to ) ) {
			$redirect_to = $is_user_logged_in ? admin_url( 'profile.php' ) : home_url( '/' );
		}

		return add_query_arg(
			array(
				'discourse_sso' => sanitize_key( apply_filters( 'wpdc_sso_client_query', 1 ) ),
				'redirect_to'   => apply_filters(
					'wpdc_sso_client_redirect_url',
					urlencode( esc_url_raw( $redirect_to ) ),
					$redirect_to
				),
			),
			home_url( '/' )
		);
	}
}
