<?php

namespace  WPDiscourse\EmailNotification;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class EmailNotification {
	public function __construct() {
		add_action( 'wpdc_topic_sync_failure_notification', array( $this, 'send_topic_sync_notification' ) );
	}

	public function send_topic_sync_notification() {
		$sync_failures = get_option( 'wpdc_webhook_sync_failures' );
		if ( $sync_failures ) {
			$email = get_option( 'admin_email' );
			$blogname              = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$support_url           = 'https://meta.discourse.org/c/support/wordpress';
			$num_failures = count( $sync_failures );
			if ( 1 === $num_failures ) {

			} else {

			}
		}
	}
}