<?php
/**
 * Allows for Single Sign On between WordPress and Discourse with Discourse as SSO Provider
 *
 * @package WPDiscourse\sso
 */

namespace WPDiscourse\SSOClient;

/**
 * Class Client
 */
class Client extends SSOClientBase {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'parse_request' ), 5 );
		add_filter( 'wp_login_errors', array( $this, 'handle_login_errors' ) );
		add_action( 'clear_auth_cookie', array( $this, 'logout_from_discourse' ) );
		add_action( 'login_form', array( $this, 'discourse_sso_alter_login_form' ) );
		add_action( 'show_user_profile', array( $this, 'discourse_sso_alter_user_profile' ) );
		add_action( 'admin_notices', array( $this, 'set_user_notice' ) );
	}

	/**
	 * Sets a notice for users when they link their account with Discourse.
	 */
	public function set_user_notice() {
		global $pagenow;
		$sso_client_enabled = ! empty( $this->options['sso-client-enabled'] );

		if ( $sso_client_enabled && 'profile.php' === $pagenow ) {
			$user_id           = get_current_user_id();
			$mismatched_emails = get_user_meta( $user_id, 'discourse_mismatched_emails', true );

			if ( $mismatched_emails ) {
				$error_message = __(
					'<div class="notice notice-error is-dismissible"><p>To link an existing WordPress account with Discourse,
                     the email addresses of both accounts must match. Make sure you are logged into the correct Discourse account.
                     If you are unable to edit your email addresses to match, contact a site administrator.</p></div>',
					'wp-discourse'
				);

				delete_user_meta( $user_id, 'discourse_mismatched_emails' );

				echo wp_kses_post( $error_message );
			} else {
				$user_synced = get_user_meta( $user_id, 'discourse_sso_client_synced', true );
				if ( $user_synced ) {
					$success_message = __(
						'<div class="notice notice-success is-dismissible"><p>Your account is linked to Discourse!.</p></div>',
						'wp-discourse'
					);

					delete_user_meta( $user_id, 'discourse_sso_client_synced' );

					echo wp_kses_post( $success_message );
				}
			}
		}
	}

	/**
	 * Decides if checkbox for login form alteration are enabled
	 *
	 * @return boolean
	 */
	protected function discourse_sso_auto_inject_button() {
		return ! empty( $this->options['sso-client-enabled'] ) && ! empty( $this->options['sso-client-login-form-change'] );
	}

	/**
	 * Alter the login form
	 */
	public function discourse_sso_alter_login_form() {
		if ( ! $this->discourse_sso_auto_inject_button() ) {

			return null;
		}

		printf(
			'<p>%s</p><p>&nbsp;</p>',
			wp_kses_data(
				$this->get_discourse_sso_link_markup(
					array(
						'redirect' => $this->options['sso-client-login-form-redirect'],
					)
				)
			)
		);

		do_action( 'wpdc_sso_client_after_login_link' );
	}

	/**
	 * Alter user profile
	 */
	public function discourse_sso_alter_user_profile() {
		$auto_inject_button = $this->discourse_sso_auto_inject_button();
		$link_text          = ! empty( $this->options['link-to-discourse-text'] ) ? $this->options['link-to-discourse-text'] : '';
		$linked_text        = ! empty( $this->options['linked-to-discourse-text'] ) ? $this->options['linked-to-discourse-text'] : '';
		$user               = wp_get_current_user();

		if ( ! apply_filters( 'wpdc_sso_client_add_link_buttons_on_profile', $auto_inject_button ) ) {

			return null;
		}

		?>
		<table class="form-table">
			<tr>
				<th><?php echo wp_kses_post( $link_text ); ?></th>
				<td>
					<?php
					if ( $user && get_user_meta( $user->ID, 'discourse_sso_user_id', true ) ) {

						echo wp_kses_post( $linked_text );
					} else {

						echo wp_kses_data( $this->get_discourse_sso_link_markup() ) .
							 ' <em>' . esc_html__( 'To link accounts, your Discourse email address needs to match your WordPress email address', 'wp-discourse' ) . '</em>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Parse Request Hook
	 */
	public function parse_request() {
		$this->options = $this->get_options();

		if ( empty( $this->options['sso-client-enabled'] ) || empty( $_GET['sso'] ) || empty( $_GET['sig'] ) ) {
			return;
		}

		if ( ! $this->is_valid_signature() ) {
			return;
		}

		$user_id = $this->get_user_id();

		if ( is_wp_error( $user_id ) ) {
			$this->handle_errors( $user_id );

			return;
		}

		$updated_user = $this->update_user( $user_id );
		if ( is_wp_error( $updated_user ) ) {
			$this->handle_errors( $updated_user );

			return;
		}

		$this->auth_user( $user_id );
	}

	/**
	 * Validates SSO signature
	 *
	 * @return boolean
	 */
	private function is_valid_signature() {
		$sso = urldecode( $this->get_sso_response( 'raw' ) );

		return hash_hmac( 'sha256', $sso, $this->get_sso_secret() ) === $this->get_sso_signature();
	}

	/**
	 * Get user id or create a user.
	 *
	 * For logged in users, the function checks if the 'discourse_sso_user_id' is set. If it isn't set, the user's email
	 * is checked against the email from the SSO payload. If this doesn't match, the user is redirected to their profile
	 * page where an error notice is displayed.
	 *
	 * For non-logged-in users, the function checks if there's an existing user with the payload's 'discourse_sso_user_id',
	 * if there isn't, there is an optional check for a user with a matching email address. If both checks fail, a new user
	 * is created.
	 *
	 * @return int|\WP_Error
	 */
	private function get_user_id() {
		if ( is_user_logged_in() ) {
			$user_id  = get_current_user_id();
			$redirect = $this->get_sso_response( 'return_sso_url' );
			if ( get_user_meta( $user_id, 'discourse_sso_user_id', true ) ) {
				wp_safe_redirect( $redirect );

				exit;
			} else {
				$discourse_email = $this->get_sso_response( 'email' );
				$wp_email        = wp_get_current_user()->user_email;
				if ( $discourse_email === $wp_email ) {
					update_user_meta( $user_id, 'discourse_sso_user_id', $this->get_sso_response( 'external_id' ) );
					update_user_meta( $user_id, 'discourse_sso_client_synced', 1 );
					wp_safe_redirect( $redirect );

					exit;
				} else {
					update_user_meta( $user_id, 'discourse_mismatched_emails', 1 );
					$profile_url = get_edit_profile_url();
					wp_safe_redirect( $profile_url );

					exit;
				}
			}
		} else {
			$user_query = new \WP_User_Query(
				array(
					'meta_key'   => 'discourse_sso_user_id',
					'meta_value' => $this->get_sso_response( 'external_id' ),
				)
			);

			$user_query_results = $user_query->get_results();

			if ( empty( $user_query_results ) && ! empty( $this->options['sso-client-sync-by-email'] ) ) {
				$user = get_user_by( 'email', $this->get_sso_response( 'email' ) );
				if ( $user ) {

					return $user->ID;
				}
			}

			if ( empty( $user_query_results ) ) {
				$user_password = wp_generate_password( 12, true );

				$user_id = wp_create_user(
					$this->get_sso_response( 'username' ),
					$user_password,
					$this->get_sso_response( 'email' )
				);

				do_action( 'wpdc_sso_client_after_create_user', $user_id );

				return $user_id;
			}

			return $user_query_results[0]->ID;
		}// End if().
	}

	/**
	 * Update WP user with discourse user data
	 *
	 * @param  int $user_id the user ID.
	 *
	 * @return int|\WP_Error integer if the update was successful, WP_Error otherwise.
	 */
	private function update_user( $user_id ) {
		$query = $this->get_sso_response();
		$nonce = Nonce::get_instance()->verify( $query['nonce'], '_discourse_sso' );

		if ( ! $nonce ) {
			return new \WP_Error( 'expired_nonce' );
		}

		$username     = $query['username'];
		$updated_user = array(
			'ID'            => $user_id,
			'user_nicename' => $username,
		);

		if ( ! empty( $query['name'] ) ) {
			$updated_user['first_name'] = explode( ' ', $query['name'] )[0];
			$updated_user['name']       = $query['name'];
		}

		$updated_user = apply_filters( 'wpdc_sso_client_updated_user', $updated_user, $query );

		$update = wp_update_user( $updated_user );

		if ( ! is_wp_error( $update ) ) {
			update_user_meta( $user_id, 'discourse_username', $username );

			if ( ! get_user_meta( $user_id, 'discourse_sso_user_id', true ) ) {
				update_user_meta( $user_id, 'discourse_sso_user_id', $query['external_id'] );
			}

			do_action( 'wpdc_after_sso_client_user_update', $user_id, $query );
		}

		return $update;
	}

	/**
	 * Set auth cookies
	 *
	 * @param  int $user_id the user ID.
	 * @return null
	 */
	private function auth_user( $user_id ) {
		$query = $this->get_sso_response();
		wp_set_current_user( $user_id, $query['username'] );
		wp_set_auth_cookie( $user_id );
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {

			return null;
		}
		do_action( 'wp_login', $query['username'], $user );

		$redirect_to = apply_filters( 'wpdc_sso_client_redirect_after_login', $query['return_sso_url'] );

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Handle Login errors
	 *
	 * @param  \WP_Error $error WP_Error object.
	 */
	private function handle_errors( $error ) {
		$redirect_to = apply_filters( 'wpdc_sso_client_redirect_after_failed_login', wp_login_url() );

		$redirect_to = add_query_arg( 'discourse_sso_error', $error->get_error_code(), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Add errors on the login form.
	 *
	 * @param  \WP_Error $errors the WP_Error object.
	 *
	 * @return \WP_Error updated errors.
	 */
	public function handle_login_errors( $errors ) {
		if ( isset( $_GET['discourse_sso_error'] ) ) { // Input var okay.
			$err = sanitize_text_field( wp_unslash( $_GET['discourse_sso_error'] ) ); // Input var okay.

			switch ( $err ) {
				case 'existing_user_email':
					$message = __( "There is an exiting account with the email address you are attempting to login with. If you are trying to log in through Discourse, you need to first login through WordPress, visit your profile page, and click on the 'sync accounts' link.", 'wp-discourse' );
					$errors->add( 'discourse_sso_existing_user', $message );
					break;

				case 'expired_nonce':
					$message = __( 'Expired Nonce', 'wp-discourse' );
					$errors->add( 'discourse_sso_expired_nonce', $message );
					break;

				case 'existing_user_login':
					$message = __( 'There is already an account registered with the username supplied by Discourse. If this is you, login through WordPress and visit your profile page to sync your account with Discourse', 'wp-discourse' );
					$errors->add( 'existing_user_login', $message );
					break;

				default:
					$message = __( 'Unhandled Error', 'wp-discourse' );
					$errors->add( 'discourse_sso_unhandled_error', $message );
					break;
			}
		}

		return $errors;
	}

	/**
	 * Gets the url to be redirected to
	 *
	 * @return string
	 */
	public function get_redirect_to_after_sso() {
		return $this->get_sso_response( 'return_sso_url' );
	}

	/**
	 * Get SSO Signature
	 */
	private function get_sso_signature() {
		$sig = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : ''; // Input var okay.

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
	 * @param string $return_key The key to return.
	 *
	 * @return string|array
	 */
	private function get_sso_response( $return_key = '' ) {
		if ( empty( $_GET['sso'] ) ) { // Input var okay.
			return null;
		};

		if ( 'raw' === $return_key ) {

			return sanitize_text_field( wp_unslash( $_GET['sso'] ) ); // Input var okay.
		}

		$sso = base64_decode( sanitize_text_field( wp_unslash( $_GET['sso'] ) ), true ); // Input var okay.

		if ( ! $sso ) {
			return null;
		}

		$response = array();

		parse_str( $sso, $response );
		$response = array_map( 'rawurldecode', $response );
		$response = array_map( 'sanitize_text_field', $response );

		if ( empty( $response['external_id'] ) ) {
			return null;
		}

		if ( ! empty( $return_key ) && isset( $response[ $return_key ] ) ) {
			return $response[ $return_key ];
		}

		return $response;
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
		// If sso-client is not enabled, don't make the request.
		if ( empty( $this->options['sso-client-enabled'] ) || empty( $this->options['sso-client-sync-logout'] ) ) {

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
			$logout_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_key ),
					'Api-Username' => sanitize_text_field( $api_username ),
				),
			)
		);

		if ( ! $this->validate( $logout_response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'There was an error in logging out the current user from Discourse.' );
		}

		return null;
	}
}
