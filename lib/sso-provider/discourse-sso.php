<?php
/**
 * Allows for Single Sign On between between WordPress and Discourse.
 *
 * @package WPDiscourse\DiscourseSSO
 */

namespace WPDiscourse\DiscourseSSO;

use \WPDiscourse\Shared\PluginUtilities;

/**
 * Class DiscourseSSO
 */
class DiscourseSSO {
	use PluginUtilities;

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
		$bypass_sync = apply_filters( 'wpdc_bypass_sync_sso', false, $user->ID, $user );

		if ( ! $bypass_sync && ! empty( $this->options['enable-sso'] ) && ! empty( $this->options['auto-create-sso-user'] ) ) {
			// Make sure the login hasn't been initiated by clicking on a SSO login link.
			$query_string = parse_url( wp_get_referer(), PHP_URL_QUERY );
			$query_params = [];
			parse_str( $query_string, $query_params );
			$sso_referer = ! empty( $query_params['redirect_to'] ) && preg_match( '/^\/\?sso/', $query_params['redirect_to'] );
			if ( ! $sso_referer ) {
				$params = $this->get_sso_params( $user );

				$this->sync_sso( $params, $user->ID );
			}
		}
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
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
		if ( ! empty( $this->options['enable-sso'] ) &&
			 isset( $_GET['request'] ) && // Input var okay.
			 'logout' === $_GET['request'] // Input var okay.
		) {

			wp_logout();
			wp_safe_redirect( $this->options['url'] );

			exit;
		}
		// End logout processing.
		if ( ! empty( $this->options['enable-sso'] ) &&
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
				$params          = $this->get_sso_params( $current_user );
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
			$discourse_user = $this->get_discourse_user( $user_id );
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
		if ( ! $this->validate( $logout_response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'There was an error in logging out the current user from Discourse.' );
		}

		return null;
	}

	/**
	 * Gets the SSO parametrs for a user.
	 *
	 * @param object $user The WordPress user.
	 * @param array  $sso_options An optional array of extra SSO parameters.
	 *
	 * @return array
	 */
	protected function get_sso_params( $user, $sso_options = array() ) {
		$plugin_options      = $this->options;
		$user_id             = $user->ID;
		$require_activation  = get_user_meta( $user_id, 'discourse_email_not_verified', true ) ? true : false;
		$require_activation  = apply_filters( 'discourse_email_verification', $require_activation, $user );
		$force_avatar_update = ! empty( $plugin_options['force-avatar-update'] );
		$avatar_url          = get_avatar_url(
			$user_id, array(
				'default' => '404',
			)
		);
		$avatar_url          = apply_filters( 'wpdc_sso_avatar_url', $avatar_url, $user_id );

		if ( ! empty( $plugin_options['real-name-as-discourse-name'] ) ) {
			$first_name = ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name  = ! empty( $user->last_name ) ? $user->last_name : '';

			if ( $first_name || $last_name ) {
				$name = trim( $first_name . ' ' . $last_name );
			}
		}

		if ( empty( $name ) ) {
			$name = $user->display_name;
		}

		$params = array(
			'external_id'         => $user_id,
			'username'            => $user->user_login,
			'email'               => $user->user_email,
			'require_activation'  => $require_activation ? 'true' : 'false',
			'name'                => $name,
			'bio'                 => $user->description,
			'avatar_url'          => $avatar_url,
			'avatar_force_update' => $force_avatar_update ? 'true' : 'false',
		);

		if ( ! empty( $sso_options ) ) {
			foreach ( $sso_options as $option_key => $option_value ) {
				$params[ $option_key ] = $option_value;
			}
		}

		return apply_filters( 'wpdc_sso_params', $params, $user );
	}

	/**
	 * Syncs a user with Discourse through SSO.
	 *
	 * @param array $sso_params The sso params to sync.
	 * @param int   $user_id The WordPress user's ID.
	 *
	 * @return int|string|\WP_Error
	 */
	protected function sync_sso( $sso_params, $user_id = null ) {
		$plugin_options = $this->options;
		if ( empty( $plugin_options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The sync_sso_record function can only be used when SSO is enabled.' );
		}
		$api_credentials = $this->get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$url         = $api_credentials['url'] . '/admin/users/sync_sso';
		$sso_secret  = $plugin_options['sso-secret'];
		$sso_payload = base64_encode( http_build_query( $sso_params ) );
		// Create the signature for Discourse to match against the payload.
		$sig = hash_hmac( 'sha256', $sso_payload, $sso_secret );

		$response = wp_remote_post(
			esc_url_raw( $url ), array(
				'body' => array(
					'sso'          => $sso_payload,
					'sig'          => $sig,
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				),
			)
		);

		if ( ! $this->validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'An error was returned from Discourse while trying to sync the sso record.' );
		}

		$discourse_user = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $discourse_user->id ) ) {
			$wordpress_user_id = $sso_params['external_id'];
			update_user_meta( $wordpress_user_id, 'discourse_sso_user_id', $discourse_user->id );
			update_user_meta( $wordpress_user_id, 'discourse_username', $discourse_user->username );

			do_action( 'wpdc_after_sync_sso', $discourse_user, $user_id );
		}

		return wp_remote_retrieve_response_code( $response );
	}
}
