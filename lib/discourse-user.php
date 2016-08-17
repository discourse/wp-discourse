<?php

namespace WPDiscourse\DiscourseUser;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseUser {
	protected $options;

	public function __construct() {
		$this->options = get_option( 'discourse' );

		if ( isset( $this->options['enable-sso'] ) && 1 === intval( $this->options['enable_sso'] ) ) {
			add_action( 'wp_login', array( $this, 'sync_discourse_user' ), 13, 2 );
		}
	}

	public function sync_discourse_user( $user_name, $user ) {

	}

	public function get_current_user_id() {

	}
}