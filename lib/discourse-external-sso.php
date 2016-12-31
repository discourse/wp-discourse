<?php
/**
 * Allows for Single Sign On between WordPress and Discourse with Discourse as SSO Provider
 *
 * @package WPDiscourse\DiscourseSSO
 */

namespace WPDiscourse\DiscourseSSO;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseExternalSSO
 */
class DiscourseExternalSSO {

	/**
	 * The user meta key name that would store the Discourse user id
	 *
	 * @var string
	 */
	private $sso_meta_key = 'discourse_sso_user_id';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'parse_request' ), 5 );
		add_filter( 'wp_login_errors', array( $this, 'handle_login_errors' ) );
	}

	/**
	 * Parse Reuqst Hook
	 */
	public function parse_request() {
		if ( empty( $_GET['sso'] ) || empty( $_GET['sig'] ) ) {
			return;
		}

		$this->options = DiscourseUtilities::get_options();

		if ( ! $this->is_valid_signatiure() ) {
			return;
		}

		$this->disable_notification_on_user_update();
		$user_id = $this->get_user_id();

		if ( is_wp_error( $user_id ) ) {
			$this->handle_errors( $user_id );
			return;
		}

		$this->update_user( $user_id );
		$this->auth_user( $user_id );
	}

	/**
	 * Update WP user with discourse user data
	 *
	 * @param  int $user_id the user ID.
	 */
	private function update_user( $user_id ) {
		$query = $this->get_sso_response();

		$updated_user = array(
			'ID' => $user_id,
			'user_login' => $query['username'],
			'user_email' => $query['email'],
			'user_nicename' => $query['name'],
			'display_name' => $query['name'],
			'first_name' => $query['name'],
		);

		$updated_user = apply_filters( 'discourse/sso/provider/updated_user', $updated_user, $query );

		wp_update_user( $updated_user );

		update_user_meta( $user_id, $this->sso_meta_key, $query['external_id'] );
	}

	/**
	 * Handle Login errors
	 *
	 * @param  WP_Error $error WP_Error object.
	 */
	private function handle_errors( $error ) {
	 	$redirect_to = apply_filters( 'discourse/sso/provider/redirect_after_failed_login', wp_login_url() );

	 	$redirect_to = add_query_arg( 'discourse_sso_error', $error->get_error_code(), $redirect_to );

		wp_safe_redirect( $redirect_to );
	}


	/**
	 * Add errors on the login form.
	 *
	 * @param  WP_Error $errors the WP_Error object.
	 *
	 * @return WP_Error updated errors.
	 */
	public function handle_login_errors( $errors ) {
		if ( isset( $_GET['discourse_sso_error'] ) ) {
			$err = sanitize_text_field( wp_unslash( $_GET['discourse_sso_error'] ) );
			switch ( $err ) {
				case 'existing_user_email':
					$message = __( 'Your are already registered with this email. You should login with your user/password, then link your account to discourse.', 'wp-discourse' );
					$errors->add( 'discourse_sso_existing_user', $message );
					break;

				default:
					// code...
					break;
			}
		}

		return $errors;
		;
	}

	/**
	 * Set suth cookies
	 *
	 * @param  int $user_id the user ID.
	 */
	private function auth_user( $user_id ) {
		$query = $this->get_sso_response();

		wp_set_current_user( $user_id, $query['username'] );
		wp_set_auth_cookie( $user_id );
		do_action( 'wp_login', $query['username'], $query['email'] );

		$redirect_to = apply_filters( 'discourse/sso/provider/redirect_after_login', $query['return_sso_url'] );
		wp_safe_redirect( $redirect_to );
	}

	/**
	 * Disable built in notification on email/password changes.
	 */
	private function disable_notification_on_user_update() {
		add_filter( 'send_password_change_email', '__return_false' );
		add_filter( 'send_email_change_email', '__return_false' );
	}

	/**
	 * Get user id or create an user
	 *
	 * @return int      user id
	 */
	private function get_user_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {

			$user_query = new \WP_User_Query([
				'meta_key' => $this->sso_meta_key,
				'meta_value' => $this->get_sso_response( 'external_id' ),
			]);

			$user_query_results = $user_query->get_results();

			if ( empty( $user_query_results ) ) {
				$user_password = wp_generate_password( $length = 12, $include_standard_special_chars = true );
				return wp_create_user(
					$this->get_sso_response( 'username' ),
					$user_password,
					$this->get_sso_response( 'email' )
				);
			}

			return $user_query_results{0}->ID;
		}
	}

	/**
	 * Validates SSO signature
	 *
	 * @return boolean
	 */
	private function is_valid_signatiure() {
		$sso = urldecode( $this->get_sso_response( 'raw' ) );
		return hash_hmac( 'sha256', $sso, $this->get_sso_secret() ) === $this->get_sso_signature();
	}

	/**
	 * Get SSO Signature
	 */
	private function get_sso_signature() {
		$sig = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
		return sanitize_text_field( $sig );
	}

	/**
	 * Get SSO Secret from options
	 */
	private function get_sso_secret() {
		return $this->options['sso-secret'];
	}

	/**
	 * Parse SSO Response
	 *
	 * @param string $return_key ss.
	 *
	 * @return string
	 */
	private function get_sso_response( $return_key = '' ) {
		if ( empty( $_GET['sso'] ) ) {
			return null;
		};

		if ( 'raw' === $return_key ) {
			// since sanitization do bad things to our sso payload, we must pass it raw in order to be validated
			// @codingStandardsIgnoreStart
			return $_GET['sso'];
			// @codingStandardsIgnoreEnd
		}

		$sso = urldecode( sanitize_text_field( wp_unslash( $_GET['sso'] ) ) );

		$response = array();

		parse_str( base64_decode( $sso ), $response );
		$response = array_map( 'sanitize_text_field', $response );

		if ( empty( $response['external_id'] ) ) {
			return null;
		}

		if ( ! empty( $return_key ) && isset( $response[ $return_key ] ) ) {
			return $response[ $return_key ];
		}

		return $response;
	}
}
