<?php
namespace WPDiscourse\DiscourseSSO;

class DiscourseSSO {
	
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'sso_add_query_vars' ) );
		add_action( 'parse_query', array( $this, 'sso_parse_request' ) );
	}

	function sso_add_query_vars( $vars ) {
		$vars[] = "sso";
		$vars[] = "sig";

		return $vars;
	}

	function sso_parse_request( $wp ) {
		$discourse_options = self::get_plugin_options();

		// sync logout from Discourse to WordPress from Adam Capirola : https://meta.discourse.org/t/wordpress-integration-guide/27531
		// to make this work, enter a URL of the form "http://my-wp-blog.com/?request=logout" in the "logout redirect"
		// field in your Discourse admin
		if ( isset( $discourse_options['enable-sso'] ) &&
		     intval( $discourse_options['enable-sso'] ) == 1 &&
		     isset( $_GET['request'] ) && $_GET['request'] == 'logout'
		) {

			wp_logout();
			wp_redirect( $discourse_options['url'] );
			exit;
		}
		// end logout processing

		// only process requests with "my-plugin=ajax-handler"
		if ( isset( $discourse_options['enable-sso'] ) &&
		     intval( $discourse_options['enable-sso'] ) == 1 &&
		     array_key_exists( 'sso', $wp->query_vars ) &&
		     array_key_exists( 'sig', $wp->query_vars )
		) {

			// Not logged in to WordPress, redirect to WordPress login page with redirect back to here
			if ( ! is_user_logged_in() ) {

				// Preserve sso and sig parameters
				$redirect = add_query_arg( null, null );

				// Change %0A to %0B so it's not stripped out in wp_sanitize_redirect
				$redirect = str_replace( '%0A', '%0B', $redirect );

				// Build login URL
				$login = wp_login_url( esc_url_raw( $redirect ) );

				// Redirect to login
				wp_redirect( $login );
				exit;
			} else {

				// Check for helper class
				if ( ! class_exists( 'Discourse_SSO' ) ) {
					// Error message
					echo( 'Helper class is not properly included.' );
					exit;
				}

				// Payload and signature
				$payload = $wp->query_vars['sso'];
				$sig     = $wp->query_vars['sig'];

				// Change %0B back to %0A
				$payload = urldecode( str_replace( '%0B', '%0A', urlencode( $payload ) ) );

				// Validate signature
				$sso_secret = $discourse_options['sso-secret'];
				$sso        = new Discourse_SSO( $sso_secret );
				if ( ! ( $sso->validate( $payload, $sig ) ) ) {
					// Error message
					echo( 'Invalid request.' );
					exit;
				}

				// Nonce
				$nonce = $sso->get_nonce( $payload );

				// Current user info
				$current_user = wp_get_current_user();

				// Map information
				$params = array(
					'nonce'       => $nonce,
					'name'        => $current_user->display_name,
					'username'    => $current_user->user_login,
					'email'       => $current_user->user_email,
					'about_me'    => $current_user->description,
					'external_id' => $current_user->ID,
					'avatar_url'  => get_avatar_url( get_current_user_id() )
				);

				// Build login string
				$q = $sso->build_login_string( $params );

				// Redirect back to Discourse
				wp_redirect( $discourse_options['url'] . '/session/sso_login?' . $q );
				exit;
			}
		}
	}
	
}