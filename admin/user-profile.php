<?php
/**
 * Adds Discourse fields to the user profile page.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;
/**
 * Class UserProfile
 */
class UserProfile {
	use PluginUtilities;
	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;
	/**
	 * UserProfile constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'edit_user_profile', array( $this, 'add_discourse_fields_to_profile' ) );
		// Allow admins to update their own profile.
		add_action( 'show_user_profile', array( $this, 'add_discourse_fields_to_profile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_discourse_user_metadata' ) );
		// Allow admins to update their own profile.
		add_action( 'personal_options_update', array( $this, 'update_discourse_user_metadata' ) );
	}
	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}
	/**
	 * Adds Discourse fields to the user profile page.
	 *
	 * The name field can be edited by users if the Hide Discourse Name Field option is not enabled. The verify_email
	 * field is only shown to admins.
	 *
	 * @param \WP_User $profile_user The WordPress user who is being updated.
	 */
	public function add_discourse_fields_to_profile( $profile_user ) {
		$is_admin                      = current_user_can( 'manage_options' );
		$show_discourse_username_field = empty( $this->options['hide-discourse-name-field'] );
		$username_editable             = $is_admin || ! empty( $this->options['discourse-username-editable'] );
		if ( $is_admin || $show_discourse_username_field ) :
			if ( ! $is_admin && ! $username_editable ) {
				$discourse_username_description = __( 'Used for publishing posts from WordPress to Discourse. Needs to be set by a site administrator.', 'wp-discourse' );
			} else {
				$discourse_username_description = __( 'Used for publishing posts from WordPress to Discourse. Needs to match the username on Discourse.', 'wp-discourse' );
			}
			?>
			<h2><?php esc_html_e( 'Discourse', 'wp-discourse' ); ?></h2>
			<table class="form-table">
				<?php
				wp_nonce_field( 'update_discourse_usermeta', 'update_discourse_usermeta_nonce' );
					$discourse_username = get_user_meta( $profile_user->ID, 'discourse_username', true );
				?>
					<tr>
						<th>
							<label for="discourse_username"><?php esc_html_e( 'Discourse Username', 'wp-discourse' ); ?></label>
						</th>
						<td>
							<input type="text" name="discourse_username"
								   value="<?php echo esc_attr( $discourse_username ); ?>" <?php echo disabled( $username_editable, false, false ); ?>>
							<em><?php echo esc_html( $discourse_username_description ); ?></em>
						</td>
					</tr>
				<?php

				// Only show the email verification field to admins on sites with SSO enabled.
                $sso_enabled = ! empty( $this->options['enable-sso'] );
				if ( $is_admin && $sso_enabled ) :
					$email_verified_meta = get_user_meta( $profile_user->ID, 'discourse_email_not_verified', true );
                    $email_verified = empty( $email_verified_meta );
					?>
					<tr>
						<th>
							<label for="email_verified"><?php esc_html_e( 'Email Address Verified', 'wp-discourse' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="email_verified" value="1" <?php checked( $email_verified ); ?>>
							<em><?php esc_html_e( "Marking the user's email as verified will allow them to bypass email authentication on Discourse.", 'wp-discourse' ); ?></em>
						</td>
					</tr>
				<?php endif; ?>
			</table>
			<?php
		endif;
	}
	/**
	 * Updates the Discourse meta
	 *
	 * @param integer $user_id The WordPress user's ID.
	 *
	 * @return int
	 */
	public function update_discourse_user_metadata( $user_id ) {
		if ( ! isset( $_POST['update_discourse_usermeta_nonce'] ) || // Input var okay.
			 ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['update_discourse_usermeta_nonce'] ) ), 'update_discourse_usermeta' ) // Input var okay.
		) {

			return 0;
		}
		$is_admin = current_user_can( 'manage_options' );
		$sso_enabled = ! empty( $this->options['enable-sso'] );
		if ( $is_admin && $sso_enabled ) {
		    // This gets ugly because of support for php 5.4.
		    $email_verified = false;
		    if ( isset( $_POST['email_verified'] ) ) {
		        $email_verified_value = intval( wp_unslash( $_POST['email_verified'] ) );
		        $email_verified = ! empty( $email_verified_value );
            }
			if ( $email_verified ) {
				delete_user_meta( $user_id, 'discourse_email_not_verified' );
			} else {
				update_user_meta( $user_id, 'discourse_email_not_verified', 1 );
			}
		}
		$show_discourse_username_field = empty( $this->options['hide-discourse-name-field'] );
		if ( isset( $_POST['discourse_username'] ) && ( $is_admin || $show_discourse_username_field ) ) { // Input var okay.
			$discourse_username = sanitize_text_field( wp_unslash( $_POST['discourse_username'] ) ); // Input var okay.
			update_user_meta( $user_id, 'discourse_username', $discourse_username );
		}

		return $user_id;
	}
}
