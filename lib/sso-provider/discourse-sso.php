<?php
/**
 * Allows for Single Sign On between between WordPress and Discourse.
 *
 * @package WPDiscourse\DiscourseSSO
 */

namespace WPDiscourse\DiscourseSSO;

use WPDiscourse\DiscourseBase;
use \WPDiscourse\SSO\SSO;

/**
 * Class DiscourseSSO
 */
class DiscourseSSO extends DiscourseBase {
	/**
	 * Logger context
	 *
	 * @access protected
	 * @var string
	 */
	protected $logger_context = 'sso_provider';

	/**
	 * DiscourseSSO constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'init', array( $this, 'setup_logger' ) );
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
	 * @return null
	 */
	public function sync_sso_record( $user_login, $user ) {
		if ( empty( $this->options['enable-sso'] ) || empty( $this->options['auto-create-sso-user'] ) ) {

			return null;
		}
		do_action( 'wpdc_sso_provider_before_create_user', $user_login, $user );
		$bypass_sync = apply_filters( 'wpdc_bypass_sync_sso', false, $user->ID, $user );

		if ( ! $bypass_sync ) {
			// Make sure the login hasn't been initiated by clicking on a SSO login link.
			$query_string = wp_parse_url( wp_get_referer(), PHP_URL_QUERY );
			$query_params = array();
			parse_str( $query_string, $query_params );
			$sso_referer = ! empty( $query_params['redirect_to'] ) && preg_match( '/^\/\?sso/', $query_params['redirect_to'] );
			if ( ! $sso_referer ) {
				$params = $this->get_sso_params( $user );

				$this->sync_sso( $params, $user->ID );
			}
		}

		return null;
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
				return esc_url_raw( add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login_url ) );

			} else {
				return $login_url;
			}
		}

		if ( ! empty( $redirect ) ) {
			return esc_url_raw( add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login_url ) );
		} else {
			return esc_url_raw( $login_url );
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
	 * @return null
	 */
	public function sso_parse_request( $wp ) {
		if ( empty( $this->options['enable-sso'] ) ) {

			return null;
		}

		$this->handle_logout_request();

		if ( array_key_exists( 'sso', $wp->query_vars ) && array_key_exists( 'sig', $wp->query_vars ) ) {
			$payload = sanitize_text_field( $wp->query_vars['sso'] );
			$sig     = sanitize_text_field( $wp->query_vars['sig'] );

			// Not logged in to WordPress, redirect to WordPress login page with redirect back to here.
			if ( ! is_user_logged_in() ) {
				$redirect = add_query_arg( $payload, $sig );
				$login    = wp_login_url( esc_url_raw( $redirect ) );

				do_action( 'wpdc_sso_before_login_redirect', $redirect, $login );

				return $this->redirect_to( $login );
			} else {
				$sso_secret = $this->options['sso-secret'];
				$sso        = new SSO( $sso_secret );
				if ( ! ( $sso->validate( $payload, $sig ) ) ) {
					return $this->handle_error( 'parse_request.invalid_sso' );
				}

				$current_user = wp_get_current_user();
				$params       = $this->get_sso_params( $current_user );

				try {
					$params['nonce'] = $sso->get_nonce( $payload );
					$q               = $sso->build_login_string( $params );
				} catch ( \Exception $e ) {
					return $this->handle_error( 'parse_request.invalid_sso_params', array( 'message' => esc_html( $e->getMessage() ) ) );
				}

				do_action( 'wpdc_sso_provider_before_sso_redirect', $current_user->ID, $current_user );

				if ( ! empty( $this->options['verbose-sso-logs'] ) ) {
					$this->logger->info( 'parse_request.success', array( 'user_id' => $current_user->ID ) );
				}

				return $this->redirect_to( $this->options['url'] . '/session/sso_login?' . $q );
			}
		}

		return null;
	}

	/**
	 * Log the current user out of Discourse before logging them out of WordPress.
	 *
	 * This function hooks into the 'clear_auth_cookie' action. It is the last action hook before logout
	 * where it is possible to access the user_id.
	 *
	 * Todo: this function duplicates code from `client.php`.
	 *
	 * @return null|\WP_Error
	 */
	public function logout_from_discourse() {
		// If SSO is not enabled, don't make the request.
		if ( empty( $this->options['enable-sso'] ) ) {
			return null;
		}

		$user              = wp_get_current_user();
		$user_id           = $user->ID;
		$discourse_user_id = get_user_meta( $user_id, 'discourse_sso_user_id', true );

		if ( empty( $discourse_user_id ) ) {
			$discourse_user = $this->get_discourse_user( $user_id );

			if ( empty( $discourse_user->id ) ) {
				return $this->handle_error(
					'logout.discourse_user',
					array(
						'message' => 'The Discourse user_id could not be returned when trying to logout the user.',
						'user_id' => $user_id,
					)
				);
			}

			$discourse_user_id = $discourse_user->id;
			update_user_meta( $user_id, 'discourse_sso_user_id', $discourse_user_id );
		}

		$path     = "/admin/users/$discourse_user_id/log_out";
		$response = $this->discourse_request( $path, array( 'method' => 'POST' ) );

		if ( is_wp_error( $response ) ) {
			return $this->handle_error(
				'logout.response_error',
				array(
					'message'           => 'There was an error in logging out the user from Discourse.',
					'user_id'           => $user_id,
					'discourse_user_id' => $discourse_user_id,
				)
			);
		} else {
			return true;
		}
	}

	/**
	 * Syncs Discourse logout requests with WordPress.
	 */
	protected function handle_logout_request() {
		if ( isset( $_GET['request'] ) && 'logout' === $_GET['request'] ) { // Input var okay.
			$user_id = get_current_user_id();
			wp_logout();

			if ( version_compare( get_bloginfo( 'version' ), '5.3', '<' ) ) {
				// See https://core.trac.wordpress.org/ticket/35488.
				wp_set_current_user( 0 );
			}

			if ( $user_id && ! empty( $this->options['verbose-sso-logs'] ) ) {
				$this->logger->info( 'handle_logout_request.success', array( 'user_id' => $user_id ) );
			}

			$this->redirect_to( $this->options['url'] );
		}
	}

	/**
	 * Handle sso_provider errors
	 *
	 * @param string $type Error type.
	 * @param array  $args Error args.
	 */
	protected function handle_error( $type, $args = array() ) {
		$this->logger->error( $type, $args );
		return new \WP_Error( $type, isset( $args['message'] ) ? $args['message'] : 'SSO error' );
	}

  /**
   * Handle redirects
   *
   * @param string $url Url to redirect to.
   */
	public function redirect_to( $url ) {
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}
}
