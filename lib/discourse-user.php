<?php

namespace WPDiscourse\DiscourseUser;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseUser {
	protected $options;

	public function __construct() {
		$this->options = get_option( 'discourse' );

		if ( isset( $this->options['enable-sso'] ) && 1 === intval( $this->options['enable-sso'] ) ) {
			add_action( 'wp_login', array( $this, 'sync_discourse_user' ), 13, 2 );
		}
	}

	public function sync_discourse_user( $user_name, $user ) {
		$user_id = $user->ID;
		$api_key = $this->options['api-key'];
		$api_username = $this->options['publish-username'];
		$base_url = $this->options['url'];
		$url = $base_url . "/users/by-external/$user_id.json";
		$url = add_query_arg( array(
			'api_key' => $api_key,
			'api_username' => $api_username,
		), $url );
		$url = esc_url_raw( $url );

		$response = wp_remote_get( $url );

		if ( ! DiscourseUtilities::validate( $response ) ) {
			return 0;
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( array_key_exists( 'user', $response ) ) {

		}


	}

	public function get_current_user_id() {

	}
}