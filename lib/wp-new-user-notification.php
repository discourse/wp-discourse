<?php
/**
 * Override functions from 'pluggable.php'
 */

if ( ! function_exists( 'wp_new_user_notification' ) ) :
	/**
	 * Overrides the 'wp_new_user_notification' function to add an 'email_verification_key' to the email
	 * sent to newly registered users.
	 *
	 * A new user registration notification is also sent to admin email.
	 *
	 * @since 2.0.0
	 * @since 4.3.0 The `$plaintext_pass` parameter was changed to `$notify`.
	 * @since 4.3.1 The `$plaintext_pass` parameter was deprecated. `$notify` added as a third parameter.
	 * @since 4.6.0 The `$notify` parameter accepts 'user' for sending notification only to the user created.
   * 
	 * @global wpdb         $wpdb      WordPress database object for queries.
	 * @global PasswordHash $wp_hasher Portable PHP password hashing framework instance.
	 *
	 * @param int    $user_id    User ID.
	 * @param null   $deprecated Not used (argument deprecated).
	 * @param string $notify     Optional. Type of notification that should happen. Accepts 'admin' or an empty
	 *                           string (admin only), or 'both' (admin and user). Default empty.
	 */

	// Only override the default function if SSO is enabled.
	$sso_options = get_option( 'discourse_sso_provider' );
	if ( ! empty( $sso_options['enable-sso'] ) ) {
		function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
			if ( $deprecated !== null ) {
				_deprecated_argument( __FUNCTION__, '4.3.1' );
			}

      // Accepts only 'user', 'admin' , 'both' or default '' as $notify.
      if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
        return;
      }

			global $wpdb, $wp_hasher;
			$user = get_userdata( $user_id );

			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

      /**
       * Filters whether the admin is notified of a new user registration.
       *
       * @since 6.1.0
       *
       * @param bool    $send Whether to send the email. Default true.
       * @param WP_User $user User object for new user.
       */
      $send_notification_to_admin = apply_filters( 'wp_send_new_user_notification_to_admin', true, $user );

			if ( 'user' !== $notify && true === $send_notification_to_admin ) {
				$switched_locale = switch_to_locale( get_locale() );

				/* translators: %s: site title */
				$message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
				/* translators: %s: user login */
				$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
				/* translators: %s: user email address */
				$message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

				$wp_new_user_notification_email_admin = array(
					'to'      => get_option( 'admin_email' ),
					/* translators: Password change notification email subject. %s: Site title */
					'subject' => __( '[%s] New User Registration' ),
					'message' => $message,
					'headers' => '',
				);

				/**
				 * Filters the contents of the new user notification email sent to the site admin.
				 *
				 * @since 4.9.0
				 *
				 * @param array $wp_new_user_notification_email {
				 *     Used to build wp_mail().
				 *
				 * @type string $to The intended recipient - site admin email address.
				 * @type string $subject The subject of the email.
				 * @type string $message The body of the email.
				 * @type string $headers The headers of the email.
				 * }
				 *
				 * @param WP_User $user User object for new user.
				 * @param string $blogname The site title.
				 */
				$wp_new_user_notification_email_admin = apply_filters( 'wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname );

				@wp_mail(
					$wp_new_user_notification_email_admin['to'],
					wp_specialchars_decode( sprintf( $wp_new_user_notification_email_admin['subject'], $blogname ) ),
					$wp_new_user_notification_email_admin['message'],
					$wp_new_user_notification_email_admin['headers']
				);

				if ( $switched_locale ) {
					restore_previous_locale();
				}
			}

      /**
       * Filters whether the user is notified of their new user registration.
       *
       * @since 6.1.0
       *
       * @param bool    $send Whether to send the email. Default true.
       * @param WP_User $user User object for new user.
       */
      $send_notification_to_user = apply_filters( 'wp_send_new_user_notification_to_user', true, $user );

			// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
			if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
				return;
			}

			$key = get_password_reset_key( $user );
      if ( is_wp_error( $key ) ) {
        return;
      }

			$switched_locale = switch_to_user_locale( $user_id );

			// Added by the wp-discourse plugin.
			$email_verification_sig = time() . '_' . wp_generate_password( 20, false );
			update_user_meta( $user_id, 'discourse_email_verification_key', $email_verification_sig );

			/* translators: %s: user login */
			$message  = sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			$message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
			$message .= esc_url_raw( network_site_url( "wp-login.php?action=rp&key=$key&mail_key=$email_verification_sig&login=" . rawurlencode( $user->user_login ) ), 'login' ) . "\r\n\r\n";

			$message .= esc_url( wp_login_url() ) . "\r\n";

			$wp_new_user_notification_email = array(
				'to'      => $user->user_email,
				/* translators: Password change notification email subject. %s: Site title */
				'subject' => __( '[%s] Your username and password info' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the new user notification email sent to the new user.
			 *
			 * @since 4.9.0
			 *
			 * @param array $wp_new_user_notification_email {
			 *     Used to build wp_mail().
			 *
			 * @type string $to The intended recipient - New user email address.
			 * @type string $subject The subject of the email.
			 * @type string $message The body of the email.
			 * @type string $headers The headers of the email.
			 * }
			 *
			 * @param WP_User $user User object for new user.
			 * @param string $blogname The site title.
			 */
			$wp_new_user_notification_email = apply_filters( 'wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname );

			wp_mail(
				$wp_new_user_notification_email['to'],
				wp_specialchars_decode( sprintf( $wp_new_user_notification_email['subject'], $blogname ) ),
				$wp_new_user_notification_email['message'],
				$wp_new_user_notification_email['headers']
			);

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}
	}
endif;
