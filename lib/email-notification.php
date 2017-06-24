<?php

namespace WPDiscourse\EmailNotification;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class EmailNotification {
	public function __construct() {
		add_action( 'wpdc_topic_sync_failure_notification', array( $this, 'send_topic_sync_notification' ) );
	}

	public function send_topic_sync_notification() {
		$sync_failures = get_option( 'wpdc_webhook_sync_failures' );
		if ( $sync_failures ) {
			$email        = get_option( 'admin_email' );
			$blogname     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$support_url  = 'https://meta.discourse.org/c/support/wordpress';
			$num_failures = count( $sync_failures );
			if ( 1 === $num_failures ) {
				$message = sprintf(
					           __( 'The following Discourse topic has failed to be synced with your blog %1$s.', 'wp-discourse' ), $blogname
				           ) . "\r\n\r\n";
			} else {
				$message = sprintf(
					           __( 'The following Discourse topics have failed to be synced with your blog %1$s.', 'wp-discourse' ), $blogname
				           ) . "\r\n\r\n";;
			}

			foreach ( $sync_failures as $topic ) {
				$title    = ! empty( $topic['title'] ) ? $topic['title'] : '';
				$topic_id = ! empty( $topic['topic_id'] ) ? $topic['topic_id'] : '';
				$time     = ! empty( $topic['time'] ) ? $topic['time'] : '';
				$message  .= sprintf(
					             __( '%1$s (topic_id %2$s) updated on Discourse at %3$s', 'wp-discourse' ), $title, $topic_id, $time
				             ) . "\r\n";;
			}

			$message .= "\r\n";
			if ( 1 === $num_failures ) {
				$message .= __( 'To fix this problem, find the associated post on your WordPress site and either republish' ) . "\r\n";
				$message .= __( 'it to Discourse, or manually add its topic_id as post metadata with the key \'discourse_topic_id.\'' ) . "\r\n\r\n";
			} else {
				$message .= __( 'To fix these problems, find the associated posts on your WordPress site and either republish' ) . "\r\n";
				$message .= __( 'them to Discourse, or manually add their topic_ids as post metadata with the key \'discourse_topic_id\'.' ) . "\r\n\r\n";
			}

			$message .= __( "To stop receiving these email notifications, unselect the 'Webhook Sync Notifications' option", 'wp-discourse' ) . "\r\n";
			$message .= __( 'on the WP Discourse Connection Settings tab.', 'wp-discourse' ) . "\r\n\r\n";

			$message .= __( 'If you\'re having trouble with the WP Discourse plugin, you can find help at:', 'wp-discourse' ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: Discourse support URL.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $support_url ) ) . "\r\n";

			// The message has been created. Delete the option.
			delete_option( 'wpdc_webhook_sync_failures' );

			wp_mail( $email, sprintf( __( '[%s] Discourse Webhook Sync Failure' ), $blogname ), $message );
		}
	}
}