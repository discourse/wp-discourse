<?php
/**
 * Adds Discourse Login button on the login form.
 *
 * @package WPDiscourse\DiscourseSSO
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Alter the login form
 */
function discourse_sso_alter_login_form() {
	$options = DiscourseUtilities::get_options();

	if ( empty( $options['enable-discourse-sso'] ) || empty( $options['enable-discourse-sso-login-form-change'] ) ) {
		return;
	}

	printf( '<p>%s</p><p>&nbsp;</p>', get_discourse_sso_url() );
}

add_action( 'login_form', 'discourse_sso_alter_login_form' );
