<?php
/**
 * Adds Discourse Login button on the login form.
 *
 * @package WPDiscourse\DiscourseSSO
 */

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

function discourse_sso_auto_inject_button() {
	$options = DiscourseUtilities::get_options();
	return ! empty( $options['enable-discourse-sso'] ) && ! empty( $options['enable-discourse-sso-login-form-change'] );
}

/**
 * Alter the login form
 */
function discourse_sso_alter_login_form() {
	if ( ! discourse_sso_auto_inject_button() ) {
		return;
	}

	printf( '<p>%s</p><p>&nbsp;</p>', get_discourse_sso_url() );
}

add_action( 'login_form', 'discourse_sso_alter_login_form' );


/**
 * Alter user profile
 */
function discourse_sso_alter_user_profile() {
	if ( ! discourse_sso_auto_inject_button() ) {
		return;
	}

	?>
	<table class="form-table">
	<tr>
	  <th><?php esc_html_e( 'Link your account to Discourse', 'wp-discourse' ); ?></th>
	  <td>
	<?php
	if ( DiscourseUtilities::user_is_linked_to_sso() ) {
		esc_html_e( 'You\'re already linked to discourse!', 'wp-discourse' );
	} else {
		echo get_discourse_sso_url();
	}
		?>
	  </td>
	</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', 'discourse_sso_alter_user_profile' );
