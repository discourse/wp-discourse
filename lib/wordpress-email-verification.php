<?php
/**
 * Allows for email address verification in WordPress.
 *
 * This class is set up to be as reusable as possible, with the hope that people will use and improve on it.
 *
 * @package WPDiscourse\WordPressEmailVerification
 */

namespace WPDiscourse\WordPressEmailVerification;

/**
 * This file overwrites the pluggable WordPress `wp_new_user_notification` method to include an email verification signature.
 *
 * The signature is made like this:
 *`$email_verification_sig = time() . '_' . wp_generate_password( 20, false );`
 * It is added to the activation url that is included in the 'new user notification' email with the key of 'mail_key'.
 * This is how the url is put together:
 *`"wp-login.php?action=rp&key=$key&mail_key=$email_verification_sig&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";`
 * The signature is also saved as user_metadata under a key that must be equal to the `$verification_signature_key_name`:
 *`update_user_meta( $user_id, 'discourse_email_verification_key', $email_verification_sig );`
 */
require_once( __DIR__ . '/wp-new-user-notification.php' );

/**
 * Class WordPressEmailVerification
 *
 * @package WPDiscourse\WordPressEmailVerification
 */
class WordPressEmailVerification {
	/**
	 * The key under which the verification signature is stored in the database.
	 *
	 * @var string
	 */
	protected $verification_signature_key_name;

	/**
	 * The site prefix, used to avoid naming collisions in the database.
	 *
	 * @var string
	 */
	protected $site_prefix;

	/**
	 * The plugin text domain.
	 *
	 * @var string
	 */
	protected $text_domain;

	/**
	 * The time period for which the key sent by the `send_verification_email` method is valid.
	 *
	 * @var int
	 */
	protected $email_expiration_period = HOUR_IN_SECONDS;

	/**
	 * WordPressEmailVerification constructor.
	 *
	 * Note: the `verification_signature_key_name` must be equal to the name under which the signature is stored
	 * in `wp-new-user-notification.php`.
	 *
	 * @param string $verification_signature_key_name The name of the key that the verification signature is stored under.
	 * @param string $site_prefix A site prefix to avoid naming collisions in the database, for example 'testeleven'.
	 * @param string $text_domain The key for your text domain.
	 */
	public function __construct( $verification_signature_key_name, $site_prefix, $text_domain = '' ) {

		$this->verification_signature_key_name = $verification_signature_key_name;
		$this->site_prefix                     = $site_prefix;
		$this->text_domain                     = $text_domain;

		add_action( 'init', array( $this, 'initialize' ) );
	}

	/**
	 * Initializes the class.
	 *
	 * This function hooks into the 'user_register' action to flag all newly registered user's emails as unverified.
	 * The 'resetpass_form' and 'login_form' hooks are used to add a hidden 'mail_key' field to the reset password and
	 * login forms.
	 */
	public function initialize() {

		add_action( 'user_register', array( $this, 'flag_email' ) );
		add_action( 'resetpass_form', array( $this, 'mail_key_field' ) );
		add_action( 'login_form', array( $this, 'mail_key_field' ) );
		add_action( 'after_password_reset', array( $this, 'verify_email_after_password_reset' ) );
		add_action( 'wp_login', array( $this, 'verify_email_after_login' ), 10, 2 );
		add_action( 'login_message', array( $this, 'email_not_verified_messages' ) );
	}

	/**
	 * Flags all users when they first register as having an unverified email address.
	 *
	 * @param int $user_id The user's ID.
	 */
	public function flag_email( $user_id ) {

		$this->set_verification_status( $user_id, 1 );
	}

	/**
	 * Creates a hidden 'mail_key' field on the login form.
	 *
	 * Hooks into the 'resetpass_form' and 'login_form' actions.
	 */
	public function mail_key_field() {

		if ( isset( $_REQUEST['mail_key'] ) ) { // Input var okay.

			$mail_key = sanitize_key( wp_unslash( $_REQUEST['mail_key'] ) ); // Input var okay.
			wp_nonce_field( 'verify_email', 'verify_email_nonce' );
			echo '<input type="hidden" name="mail_key" value="' . esc_attr( $mail_key ) . '" />';
		}
	}

	/**
	 * Attempts to verify the email address after the user responds to the 'new user notification' email.
	 *
	 * @param \WP_User $user The user who's password has been reset.
	 */
	public function verify_email_after_password_reset( $user ) {

		if ( isset( $_POST['mail_key'] ) && isset( $_POST['verify_email_nonce'] ) ) { // Input var okay.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['verify_email_nonce'] ) ), 'verify_email' ) ) { // Input var okay.
				return 0;
			}
			$sig       = sanitize_key( wp_unslash( $_POST['mail_key'] ) ); // Input var okay.
			$user_id   = $user->ID;
			$saved_sig = sanitize_key( $this->get_user_signature_value( $user_id ) );

			if ( $sig === $saved_sig ) {
				$this->remove_unverified_flag( $user_id );
				$this->delete_user_signature( $user_id );
				$this->delete_user_verification_time( $user_id );
			}
		}
	}

	/**
	 * Attempts to verify the email address when the user replies to the verification email.
	 *
	 * @param string   $user_name The user's name.
	 * @param \WP_User $user The user who has logged in.
	 */
	public function verify_email_after_login( $user_name, $user ) {

		if ( isset( $_POST['mail_key'] ) && isset( $_POST['verify_email_nonce'] ) ) { // Input var okay.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['verify_email_nonce'] ) ), 'verify_email' ) ) { // Input var okay.
				return 0;
			}
			$user_id = $user->ID;
			list( $sig_created_at, $sig_value ) = explode( '_', sanitize_key( wp_unslash( $_POST['mail_key'] ) ) ); // Input var okay.
			$saved_sig = $this->get_user_signature_value( $user_id );
			list( $saved_sig_create_at, $saved_sig_value ) = explode( '_', sanitize_key( wp_unslash( $saved_sig ) ) );
			$expired_sig = time() > intval( $sig_created_at ) + $this->email_expiration_period;

			if ( $expired_sig ) {
				$this->process_expired_sig( $user_id );

			} elseif ( $sig_value !== $saved_sig_value || $sig_created_at !== $saved_sig_create_at ) {
				$this->process_mismatched_sig( $user_id );

			} else {
				$this->remove_unverified_flag( $user_id );
				$this->delete_user_signature( $user_id );
				$this->delete_user_verification_time( $user_id );
			}
		}
	}

	/**
	 * Filters the message based on the action and error code.
	 *
	 * @param string $message The original message.
	 *
	 * @return string
	 */
	public function email_not_verified_messages( $message ) {

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // Input var okay.
		$error  = isset( $_REQUEST['error'] ) ? sanitize_key( wp_unslash( $_REQUEST['error'] ) ) : ''; // Input var okay.

		if ( 'rp' === $action && 'emailnotverified' === $error ) {
			$message = '<p class="message">' . __( 'To allow us to verify your email address, please update your password and log into the site.', $this->text_domain ) . '</p>';

			return $message;
		}

		if ( 'login' === $action && 'emailnotverified' === $error ) {
			$message = '<p class="message">' . __( 'To allow us to verify your email address, please log into the site.', $this->text_domain ) . '</p>';

			return $message;
		}

		if ( 'login' === $action && 'expiredemailkey' === $error ) {
			$message = '<p class="message">' . __( 'Your email verification key has expired. A new one has been sent to you. Please check your inbox and try again.', $this->text_domain ) . '</p>';

			return $message;
		}

		if ( 'login' === $action && 'mismatchedemailkey' === $error ) {
			$message = '<p class="message">' . __( 'There has been a problem with processing your email verification. A new email has been sent to you. Please check your inbox and try again.', $this->text_domain ) . '</p>';

			return $message;
		}

		return $message;
	}

	/**
	 * Sends an email verification message.
	 *
	 * The message includes a login link that has an email verification signature. Unless `$force` is set to true, the
	 * message will not be sent more than once every hour.
	 *
	 * @param int  $user_id The user to send the message to.
	 * @param bool $force Whether to force sending the email before the `email_expiration_period` has passed. (Used when there is a signature mismatch).
	 * @param bool $admin Whether to send a notice to the admin.
	 */
	public function send_verification_email( $user_id, $force = false, $admin = true ) {

		$key_created_at = $this->get_user_verification_time( $user_id );
		$current_time   = time();

		if ( ! empty( $key_created_at ) && ! $force && ( intval( $key_created_at ) + $this->email_expiration_period ) > $current_time ) {
			return;
		}

		$user     = get_userdata( $user_id );
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		if ( $admin ) {
			$message = sprintf( __( 'An existing user is verifying their email address on your site %s:', $this->text_domain ), $blogname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Username: %s', $this->text_domain ), $user->user_login ) . "\r\n\r\n";
			$message .= sprintf( __( 'Email: %s', $this->text_domain ), $user->user_email ) . "\r\n";

			@wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] Existing User Email Verification', $this->text_domain ), $blogname ), $message );
		}

		$email_verification_sig = $current_time . '_' . wp_generate_password( 20, false );
		$this->update_user_signature_value( $user_id, $email_verification_sig );
		$this->update_user_verification_time( $user_id, $current_time );

		$redirect = urlencode( home_url( '/' ) );

		$message = sprintf( __( 'Username: %s', $this->text_domain ), $user->user_login ) . "\r\n\r\n";
		$message .= __( 'To verify your email address, visit the following address:', $this->text_domain ) . "\r\n\r\n";
		$message .= '<' . network_site_url( "wp-login.php?action=login&mail_key=$email_verification_sig&error=emailnotverified&redirect_to=$redirect&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";

		$message .= wp_login_url() . "\r\n";

		wp_mail( $user->user_email, sprintf( __( '[%s] Verify your email address', $this->text_domain ), $blogname ), $message );
	}

	/**
	 * This is the main 'public' function, returns true if a user's email is verified, false otherwise.
	 *
	 * @param int $user_id The user's ID.
	 *
	 * @return bool
	 */
	public function is_verified( $user_id ) {

		$status = $this->get_verification_status( $user_id );

		return '' === $status ? true : false;
	}

	/**
	 * Adds a site prefix to the user_metadata key to avoid naming collisions.
	 *
	 * @param string $value The string to add the prefix to.
	 * @return string
	 */
	protected function prefix_value( $value ) {

		return $this->site_prefix . '_' . $value;
	}

	/**
	 * Returns the prefixed verification status key name.
	 *
	 * @return string
	 */
	protected function verification_status_key_name() {

		return $this->prefix_value( 'email_not_verified' );
	}

	/**
	 * Gets the database entry for the user's verification status.
	 *
	 * @param int $user_id The ID of the user.
	 *
	 * @return mixed
	 */
	protected function get_verification_status( $user_id ) {

		return get_user_meta( $user_id, $this->verification_status_key_name(), true );
	}

	/**
	 * Sets the verification status for a user.
	 *
	 * @param int   $user_id The user's ID.
	 * @param mixed $status The value to be set.
	 */
	protected function set_verification_status( $user_id, $status ) {

		update_user_meta( $user_id, $this->verification_status_key_name(), $status );
	}

	/**
	 * Removes the unverified status flag for a user.
	 *
	 * @param int $user_id The user's ID.
	 */
	protected function remove_unverified_flag( $user_id ) {

		delete_user_meta( $user_id, $this->verification_status_key_name() );
	}

	/**
	 * Returns the prefixed key name for the verification time database entry.
	 *
	 * @return string
	 */
	protected function verification_time_key_name() {

		return $this->prefix_value( 'email_key_created_at' );
	}

	/**
	 * Returns the database entry for the time at which the last verification email was sent to the user.
	 *
	 * This is used to limit the number of emails that are being sent.
	 *
	 * @param int $user_id The user's ID.
	 *
	 * @return mixed
	 */
	protected function get_user_verification_time( $user_id ) {

		return get_user_meta( $user_id, $this->verification_time_key_name(), true );
	}

	/**
	 * Updates the database entry for the time at which the last email was sent.
	 *
	 * @param int $user_id The user's ID.
	 * @param int $time The time at which the email was sent.
	 */
	protected function update_user_verification_time( $user_id, $time ) {

		update_user_meta( $user_id, $this->verification_time_key_name(), $time );
	}

	/**
	 * Deletes the database entry for the time at which the last verification email was sent to the user.
	 *
	 * @param int $user_id The user's ID.
	 */
	protected function delete_user_verification_time( $user_id ) {

		delete_user_meta( $user_id, $this->verification_time_key_name() );
	}

	/**
	 * Returns the verification signature key name, this is set in the constructor.
	 *
	 * @return mixed
	 */
	protected function verification_signature_key_name() {

		return $this->verification_signature_key_name;
	}

	/**
	 * Gets the value of the verification signature from the database.
	 *
	 * @param int $user_id The user's ID.
	 * @return mixed
	 */
	protected function get_user_signature_value( $user_id ) {

		return get_user_meta( $user_id, $this->verification_signature_key_name(), true );
	}

	/**
	 * Updates the database entry for the user's verification signature.
	 *
	 * @param int    $user_id The user's ID.
	 * @param string $sig The new signature.
	 */
	protected function update_user_signature_value( $user_id, $sig ) {

		update_user_meta( $user_id, $this->verification_signature_key_name(), $sig );
	}

	/**
	 * Deletes the database entry for the user's verification signature.
	 *
	 * @param int $user_id The user's ID.
	 */
	protected function delete_user_signature( $user_id ) {

		delete_user_meta( $user_id, $this->verification_signature_key_name() );
	}

	/**
	 * Called when the user's signature has expired.
	 *
	 * @param int $user_id The user's ID.
	 */
	protected function process_expired_sig( $user_id ) {

		$this->send_verification_email( $user_id );
		wp_redirect( site_url( 'wp-login.php?action=login&error=expiredemailkey' ) );
		exit;
	}

	/**
	 * Called when the user's signature doesn't match the request's signature.
	 *
	 * @param int $user_id The user's ID.
	 */
	protected function process_mismatched_sig( $user_id ) {

		$this->send_verification_email( $user_id, true );
		wp_redirect( site_url( 'wp-login.php?action=login&error=mismatchedemailkey' ) );
		exit;
	}
}

