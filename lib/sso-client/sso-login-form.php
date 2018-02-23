<?php
/**
 * Adds Discourse Login button on the login form.
 *
 * @package WPDiscourse\DiscourseSSO
 */

use \WPDiscourse\Shared\PluginUtilities as DiscourseUtilities;
use \WPDiscourse\Shared\PluginOptions;

/**
 * Decides if checkbox for login form alteration are enabled
 *
 * @return boolean
 */
function discourse_sso_auto_inject_button() {
	$options = $this->get_options();
	return ! empty( $options['sso-client-enabled'] ) && ! empty( $options['sso-client-login-form-change'] );
}

/**
 * Alter the login form
 */
function discourse_sso_alter_login_form() {
	if ( ! discourse_sso_auto_inject_button() ) {
		return;
	}

	printf( '<p>%s</p><p>&nbsp;</p>', wp_kses_data( get_discourse_sso_link_markup() ) );

	do_action( 'wpdc_sso_client_after_login_link' );
}

add_action( 'login_form', 'discourse_sso_alter_login_form' );


/**
 * Alter user profile
 */
function discourse_sso_alter_user_profile() {
	$auto_inject_button = discourse_sso_auto_inject_button();
	$options            = $this->get_options();
	$link_text          = ! empty( $options['link-to-discourse-text'] ) ? $options['link-to-discourse-text'] : '';
	$linked_text        = ! empty( $options['linked-to-discourse-text'] ) ? $options['linked-to-discourse-text'] : '';

	if ( ! apply_filters( 'wpdc_sso_client_add_link_buttons_on_profile', $auto_inject_button ) ) {
		return;
	}

	?>
	<table class="form-table">
	<tr>
	  <th><?php echo wp_kses_post( $link_text ); ?></th>
	  <td>
	<?php
	if ( DiscourseUtilities::user_is_linked_to_sso() ) {
		echo wp_kses_post( $linked_text );
	} else {
		echo wp_kses_data( get_discourse_sso_link_markup() );
	}
		?>
	  </td>
	</tr>
	</table>
	<?php
}

add_action( 'show_user_profile', 'discourse_sso_alter_user_profile' );
