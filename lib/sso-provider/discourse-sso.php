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
	 * DiscourseSSO constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'query_vars', array( $this, 'sso_add_query_vars' ) );
		add_filter( 'login_url', array( $this, 'set_login_url' ), 10, 2 );
		add_action( 'parse_query', array( $this, 'sso_parse_request' ) );
		add_action( 'clear_auth_cookie', array( $this, 'logout_from_discourse' ) );
		add_action( 'wp_login', array( $this, 'sync_sso_record' ), 10, 2 );
	}

	/**
	 * Syncs a user with Discourse through the sync_sso route.
	 *
	 * @param string   $user_login The user's username.
	 * @param \WP_User $user The User object.
	 */
	public function sync_sso_record( $user_login, $user ) {
		do_action( 'wpdc_sso_provider_before_create_user', $user_login, $user );
		if ( ! empty( $this->options['enable-sso'] ) && ! empty( $this->options['auto-create-sso-user'] ) ) {
			$params = DiscourseUtilities::get_sso_params( $user );

			DiscourseUtilities::sync_sso_record( $params );
		}
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
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
		if ( ! empty( $this->options['login-path'] ) ) {
			$login_url = $this->options['login-path'];

			if ( ! empty( $redirect ) ) {
				return add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login_url );

			} else {
				return $login_url;
			}
		}

		if ( ! empty( $redirect ) ) {
			return add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login_url );
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
	 * @param \WP_Query $wp The query object that parsed the query.
	 *
	 * @throws \Exception Throws an exception it SSO helper class is not included, or the payload can't be validated against the sig.
	 */
	public function sso_parse_request( $wp ) {

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
			wp_safe_redirect( $this->options['url'] );

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

				do_action( 'wpdc_sso_before_login_redirect', $redirect, $login );

				// Redirect to login.
				wp_safe_redirect( $login );

				exit;
			} else {

				// Check for helper class.
				if ( ! class_exists( '\WPDiscourse\SSO\SSO' ) ) {
					echo( 'Helper class is not properly included.' );

					exit;
				}

				// Payload and signature.
				$payload = $wp->query_vars['sso'];
				$sig     = $wp->query_vars['sig'];

				// Change %0B back to %0A.
				$payload = rawurldecode( str_replace( '%0B', '%0A', rawurlencode( $payload ) ) );

				// Validate signature.
				$sso_secret = $this->options['sso-secret'];
				$sso        = new \WPDiscourse\SSO\SSO( $sso_secret );

				if ( ! ( $sso->validate( $payload, $sig ) ) ) {
					echo( 'Invalid request.' );
					exit;
				}

				$nonce           = $sso->get_nonce( $payload );
				$current_user    = wp_get_current_user();
				$params          = DiscourseUtilities::get_sso_params( $current_user );
				$params['nonce'] = $nonce;
				$q               = $sso->build_login_string( $params );

				do_action( 'wpdc_sso_provider_before_sso_redirect', $current_user->ID, $current_user );
				// Redirect back to Discourse.
				wp_safe_redirect( $this->options['url'] . '/session/sso_login?' . $q );

				exit;
			}// End if().
		}// End if().
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
		// If SSO is not enabled, don't make the request.
		if ( empty( $this->options['enable-sso'] ) ) {

			return null;
		}

		$user              = wp_get_current_user();
		$user_id           = $user->ID;
		$base_url          = $this->options['url'];
		$api_key           = $this->options['api-key'];
		$api_username      = $this->options['publish-username'];
		$discourse_user_id = get_user_meta( $user_id, 'discourse_sso_user_id', true );

		if ( empty( $discourse_user_id ) ) {
			$discourse_user = DiscourseUtilities::get_discourse_user( $user_id );
			if ( empty( $discourse_user->id ) ) {

				return new \WP_Error( 'wpdc_response_error', 'The Discourse user_id could not be returned when trying to logout the user.' );
			}

			$discourse_user_id = $discourse_user->id;
			update_user_meta( $user_id, 'discourse_sso_user_id', $discourse_user_id );
		}

		$logout_url      = $base_url . "/admin/users/$discourse_user_id/log_out";
		$logout_url      = esc_url_raw( $logout_url );
		$logout_response = wp_remote_post(
			$logout_url, array(
				'method' => 'POST',
				'body'   => array(
					'api_key'      => $api_key,
					'api_username' => $api_username,
				),
			)
		);
		if ( ! DiscourseUtilities::validate( $logout_response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'There was an error in logging out the current user from Discourse.' );
		}

		return null;
	}
}
