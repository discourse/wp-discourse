<?php
/**
 * Sends email notifications.
 *
 * @package WPDiscourse\EmailNotification
 */

namespace WPDiscourse\EmailNotification;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class EmailNotification
 */
class EmailNotification {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var array|void
	 */
	protected $options;

	/**
	 * EmailNotification constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	/**
	 * Setup the plugin options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Sends a notification email to a site admin if a post fails to publish on Discourse.
	 *
	 * @param object $post $discourse_post The post where the failure occurred.
	 * @param array  $args Optional arguments for the function. The 'location' argument can be used to indicate where the failure occurred.
	 */
	public function publish_failure_notification( $post, $args ) {
		$post_id  = $post->ID;
		$location = ! empty( $args['location'] ) ? $args['location'] : '';

		// This is to avoid sending two emails when a post is published through XML-RPC.
		if ( 'after_save' === $location && 1 === intval( get_post_meta( $post_id, 'wpdc_xmlrpc_failure_sent', true ) ) ) {
			delete_post_meta( $post_id, 'wpdc_xmlrpc_failure_sent' );

			return;
		}

		if ( isset( $this->options['publish-failure-notice'] ) && 1 === intval( $this->options['publish-failure-notice'] ) ) {
			$publish_failure_email = ! empty( $this->options['publish-failure-email'] ) ? $this->options['publish-failure-email'] : get_option( 'admin_email' );
			$blogname              = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$post_title            = $post->post_title;
			$post_date             = $post->post_date;
			$post_author           = get_user_by( 'id', $post->post_author )->user_login;
			$permalink             = get_permalink( $post_id );
			$support_url           = 'https://meta.discourse.org/c/support/wordpress';

			// translators: Discourse publishing email. Placeholder: blogname.
			$message = sprintf( __( 'A post has failed to publish on Discourse from your site [%1$s].', 'wp-discourse' ), $blogname ) . "\r\n\r\n";
			// translators: Discourse publishing email. Placeholder: post title.
			$message .= sprintf( __( 'The post \'%1$s\' was published on WordPress', 'wp-discourse' ), $post_title ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: post author, post date.
			$message .= sprintf( __( 'by %1$s, on %2$s.', 'wp-discourse' ), $post_author, $post_date ) . "\r\n\r\n";
			// translators: Discourse publishing email. Placeholder: permalink.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $permalink ) ) . "\r\n\r\n";
			$message .= __( 'Reason for failure:', 'wp-discourse' ) . "\r\n";

			switch ( $location ) {
				case 'after_save':
					$message .= __( 'The \'Publish to Discourse\' checkbox wasn\'t checked.', 'wp-discourse' ) . "\r\n";
					$message .= __( 'You are being notified because you have the \'Auto Publish\' setting enabled.', 'wp-discourse' ) . "\r\n\r\n";
					break;
				case 'after_xmlrpc_publish':
					add_post_meta( $post->ID, 'wpdc_xmlrpc_failure_sent', 1 );
					$message .= __( 'The post was published through XML-RPC.', 'wp-discourse' ) . "\r\n\r\n";
					break;
				case 'after_bad_response':
					if ( ! empty( $args['error_code'] ) ) {
						$error_code = intval( $args['error_code'] );
						// translators: Discourse publishing email. Placeholder: HTTP response code.
						$message .= sprintf( __( 'A %d response code was returned from Discourse.', 'wp-discourse' ), $error_code ) . "\r\n";
						$message .= $args['error_message'] . "\r\n\r\n";
						if ( 403 === $error_code ) {
							$message .= __( 'Check that:', 'wp-discourse' ) . "\r\n";
							$message .= __( '- the author has correctly set their Discourse username', 'wp-discourse' ) . "\r\n\r\n";
						} elseif ( 429 === $error_code ) {
							$message .= __( 'Check that:', 'wp-discourse' ) . "\r\n";
							$message .= __( '- your API requests are not being rate limited by your Discourse hosting provider.', 'wp-discourse' ) . "\r\n\r\n";
						} elseif ( 500 === $error_code ) {
							$message .= __( 'It is possible that the topic has been deleted on Discourse. If so, unlink the post from Discourse and try publishing again.', 'wp-discourse' ) . "\r\n\r\n";
						}
					} else {
						$message .= __( 'A bad response was returned from Discourse.', 'wp-discourse' ) . "\r\n";
						$message .= $args['error_message'] . "\r\n\r\n";
						$message .= __( 'Check that:', 'wp-discourse' ) . "\r\n";
						$message .= __( '- your Discourse forum is online.', 'wp-discourse' ) . "\r\n\r\n";
					}
					break;
			}

			$message .= __( 'If you\'re having trouble with the WP Discourse plugin, you can find help at:', 'wp-discourse' ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: Discourse support URL.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $support_url ) ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: blogname, email message.
			wp_mail( $publish_failure_email, sprintf( __( '[%s] Discourse Publishing Failure' ), $blogname ), $message );
		}// End if().
	}
}
