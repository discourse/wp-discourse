<?php
/**
 * Sends email notifications.
 *
 * @package WPDiscourse\EmailNotification
 */

namespace WPDiscourse\EmailNotification;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class EmailNotification
 */
class EmailNotification {

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
		add_action( 'wpdc_topic_sync_failure_notification', array( $this, 'send_topic_sync_notification' ) );
	}

	/**
	 * Setup the plugin options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Sends a notification email that lists Discourse topics that have failed to sync with posts.
	 *
	 * The notification is only sent when the use-discourse-webhook and webhook-sync-notification options are enabled.
	 */
	public function send_topic_sync_notification() {
		$sync_failures = get_option( 'wpdc_webhook_sync_failures' );
		if ( $sync_failures ) {
			$email        = get_option( 'admin_email' );
			$blogname     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$support_url  = 'https://meta.discourse.org/c/support/wordpress';
			$num_failures = count( $sync_failures );
			if ( 1 === $num_failures ) {
				$message = sprintf(
				// translators: Topic sync notification singular heading. Placeholder: blogname.
					__( 'The following Discourse topic has failed to be synced with your blog %1$s.', 'wp-discourse' ), $blogname
				) . "\r\n\r\n";
			} else {
				$message = sprintf(
				// translators: Topic sync notification plural heading. Placeholder: blogname.
					__( 'The following Discourse topics have failed to be synced with your blog %1$s.', 'wp-discourse' ), $blogname
				) . "\r\n\r\n";
				;
			}

			foreach ( $sync_failures as $topic ) {
				$title    = ! empty( $topic['title'] ) ? $topic['title'] : '';
				$topic_id = ! empty( $topic['topic_id'] ) ? $topic['topic_id'] : '';
				$time     = ! empty( $topic['time'] ) ? $topic['time'] : '';
				$message  .= sprintf(
				// translators: Topic sync notification skipped topic. Placeholder: title, topic_id, time of update.
					__( '%1$s (topic_id %2$s) updated on Discourse at %3$s', 'wp-discourse' ), $title, $topic_id, $time
				) . "\r\n";
				;
			}

			$message .= "\r\n";
			if ( 1 === $num_failures ) {
				$message .= __( 'If there is a post associated with this topic on your WordPress site either republish' ) . "\r\n";
				$message .= __( 'it to Discourse, or manually add its topic_id as post metadata with the key \'discourse_topic_id.\'' ) . "\r\n\r\n";
			} else {
				$message .= __( 'If there are posts associated with these topics on your WordPress site either republish' ) . "\r\n";
				$message .= __( 'them to Discourse, or manually add their topic_ids as post metadata with the key \'discourse_topic_id\'.' ) . "\r\n\r\n";
			}

			$message .= __( "To stop receiving these email notifications, unselect the 'Webhook Sync Notifications' option", 'wp-discourse' ) . "\r\n";
			$message .= __( 'on the WP Discourse Connection Settings tab.', 'wp-discourse' ) . "\r\n\r\n";

			$message .= __( 'If you\'re having trouble with the WP Discourse plugin, you can find help at:', 'wp-discourse' ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: Discourse support URL.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $support_url ) ) . "\r\n";

			// The message has been created. Delete the option.
			delete_option( 'wpdc_webhook_sync_failures' );

			// translators: Topic sync notification emal. Placeholder: blogname.
			wp_mail( $email, sprintf( __( '[%s] Discourse Webhook Sync Failure' ), $blogname ), $message );
		}// End if().
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
					$message .= __( 'A bad response was returned from Discourse.', 'wp-discourse' ) . "\r\n\r\n";
					$message .= __( 'Check that:', 'wp-discourse' ) . "\r\n";
					$message .= __( '- the author has correctly set their Discourse username', 'wp-discourse' ) . "\r\n\r\n";
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
