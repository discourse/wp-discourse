<?php
/**
 * Allows for Single Sign On between between WordPress and Discourse.
 *
 * @package WPDiscourse\DiscourseSSO
 */

namespace WPDiscourse\DiscourseSSO;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseSSO
 */
class DiscourseSSO {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * An email_verifier object that has the `is_verified` and `send_verification_email` methods.
	 *
	 * @var object
	 */
	protected $wordpress_email_verifier;

	/**
	 * DiscourseSSO constructor.
	 *
	 * @param object $wordpress_email_verifier An object for verifying email addresses.
	 */
	public function __construct( $wordpress_email_verifier ) {
		$this->options                  = DiscourseUtilities::get_options(
			array(
				'discourse_connect',
				'discourse_sso',
			)
		);
		$this->wordpress_email_verifier = $wordpress_email_verifier;

		add_filter( 'query_vars', array( $this, 'sso_add_query_vars' ) );
		add_filter( 'login_url', array( $this, 'set_login_url' ), 10, 2 );
		add_action( 'parse_query', array( $this, 'sso_parse_request' ) );
		add_action( 'clear_auth_cookie', array( $this, 'logout_from_discourse' ) );
	}

	/**
	 * Allows the login_url to be configured.
	 *
	 * Hooks into the 'login_url' filter. If the 'login-path' option has been set the supplied path
	 * is used instead of the default WordPress login path.
	 *
	 * @param string $login_url The WordPress login url.
	 * @param string $redirect The after-login redirect, supplied by WordPress.
	 *
	 * @return string
	 */
	public function set_login_url( $login_url, $redirect ) {
		$options = get_option( 'discourse' );
		if ( $options['login-path'] ) {
			$login_url = $options['login-path'];

			if ( ! empty( $redirect ) ) {
				return add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );

			} else {
				return $login_url;
			}
		}

		if ( ! empty( $redirect ) ) {
			return add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
		} else {
			return $login_url;
		}

	}

	/**
	 * Adds the 'sso' and 'sig' keys to the query_vars array.
	 *
	 * Hooks into 'query_vars'.
	 *
	 * @param array $vars The array of query vars.
	 *
	 * @return array
	 */
	public function sso_add_query_vars( $vars ) {
		$vars[] = 'sso';
		$vars[] = 'sig';

		return $vars;
	}

	/**
	 * SSO Request Processing from Adam Capirola : https://gist.github.com/adamcapriola/11300529.
	 *
	 * Enables single sign on between WordPress and Discourse.
	 * Hooks into the 'parse_query' filter.
	 *
	 * @param WP_Query $wp The query object that parsed the query.
	 *
	 * @throws Exception Throws an exception it SSO helper class is not included, or the payload can't be validated against the sig.
	 */
	function sso_parse_request( $wp ) {

		/**
		 * Sync logout from Discourse to WordPress from Adam Capirola : https://meta.discourse.org/t/wordpress-integration-guide/27531.
		 * To make this work, enter a URL of the form "http://my-wp-blog.com/?request=logout" in the "logout redirect"
		 * field in your Discourse admin
		 */
		if ( isset( $this->options['enable-sso'] ) &&
		     1 === intval( $this->options['enable-sso'] ) &&
		     isset( $_GET['request'] ) && // Input var okay.
		     'logout' === $_GET['request'] // Input var okay.
		) {

			wp_logout();
			wp_redirect( $this->options['url'] );
			exit;
		}
		// End logout processing.
		if ( isset( $this->options['enable-sso'] ) &&
		     1 === intval( $this->options['enable-sso'] ) &&
		     array_key_exists( 'sso', $wp->query_vars ) &&
		     array_key_exists( 'sig', $wp->query_vars )
		) {
			// Not logged in to WordPress, redirect to WordPress login page with redirect back to here.
			if ( ! is_user_logged_in() ) {

				// Preserve sso and sig parameters.
				$redirect = add_query_arg( null, null );

				// Change %0A to %0B so it's not stripped out in wp_sanitize_redirect.
				$redirect = str_replace( '%0A', '%0B', $redirect );

				// Build login URL.
				$login = wp_login_url( esc_url_raw( $redirect ) );

				// Redirect to login.
				wp_redirect( $login );
				exit;
			} else {

				// Check for helper class.
				if ( ! class_exists( '\\WPDiscourse\\SSO\\Discourse_SSO' ) ) {
					echo( 'Helper class is not properly included.' );
					exit;
				}

				$current_user = wp_get_current_user();
				$user_id = $current_user->ID;
				$require_activation = false;

				if ( ! $this->wordpress_email_verifier->is_verified( $user_id ) ) {
					$require_activation = true;
				}

				// Payload and signature.
				$payload = $wp->query_vars['sso'];
				$sig     = $wp->query_vars['sig'];

				// Change %0B back to %0A.
				$payload = urldecode( str_replace( '%0B', '%0A', urlencode( $payload ) ) );

				// Validate signature.
				$sso_secret = $this->options['sso-secret'];
				$sso        = new \WPDiscourse\SSO\Discourse_SSO( $sso_secret );

				if ( ! ( $sso->validate( $payload, $sig ) ) ) {
					echo( 'Invalid request.' );
					exit;
				}

				$nonce  = $sso->get_nonce( $payload );
				$params = array(
					'nonce'       => $nonce,
					'name'        => $current_user->display_name,
					'username'    => $current_user->user_login,
					'email'       => $current_user->user_email,
					// 'true' and 'false' are strings so that they are not converted to 1 and 0 by `http_build_query`.
					'require_activation' => $require_activation ? 'true' : 'false',
					'about_me'    => $current_user->description,
					'external_id' => $current_user->ID,
					'avatar_url'  => get_avatar_url( get_current_user_id() ),
				);

				$q = $sso->build_login_string( $params );

				// Redirect back to Discourse.
				wp_redirect( $this->options['url'] . '/session/sso_login?' . $q );
				exit;
			}
		}
	}

	/**
	 * Log the current user out of Discourse before logging them out of WordPress.
	 *
	 * This function hooks into the 'clear_auth_cookie' action. It is the last action hook before logout
	 * where it is possible to access the user_id.
	 *
	 * @return \WP_Error
	 */
	public function logout_from_discourse() {
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		$base_url     = $this->options['url'];
		$api_key      = $this->options['api-key'];
		$api_username = $this->options['publish-username'];
		// This section is to retrieve the Discourse user_id. It would also be possible to retrieve Discourse
		// user info on login to WordPress and store it in the user_metadata table.
		$user_url = esc_url_raw( $base_url . "/users/by-external/$user_id.json" );
		$user_data = wp_remote_get( $user_url );
		if ( ! DiscourseUtilities::validate( $user_data ) ) {
			return new \WP_Error( 'unable_to_retrieve_user_data', 'There was an error in retrieving the current user data from Discourse.' );
		}
		$user_data = json_decode( wp_remote_retrieve_body( $user_data ), true );
		if ( array_key_exists( 'user', $user_data ) ) {
			$discourse_user_id = $user_data['user']['id'];
			if ( isset( $discourse_user_id ) ) {
				$logout_url = $base_url . "/admin/users/$discourse_user_id/log_out";
				$logout_url = esc_url_raw( $logout_url );
				$logout_response = wp_remote_post( $logout_url, array(
					'method' => 'POST',
					'body'   => array(
						'api_key'      => $api_key,
						'api_username' => $api_username,
					),
				) );
				if ( ! DiscourseUtilities::validate( $logout_response ) ) {
					return new \WP_Error( 'unable_to_log_out_user', 'There was an error in logging out the current user from Discourse.' );
				}
			}
		}
	}
}
