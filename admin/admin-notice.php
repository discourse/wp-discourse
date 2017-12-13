<?php
/**
 * Add admin notices.
 *
 * @package WPDiscourse;
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class AdminNotice
 */
class AdminNotice {


	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var    mixed|void
	 */
	protected $options;

	/**
	 * AdminNotice constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'set_admin_notices' ) );
		add_action( 'admin_init', array( $this, 'setup_options' ) );
	}

	/**
	 * Sets the plugin options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Set admin notices.
	 */
	public function set_admin_notices() {
		global $pagenow, $post;

		// Post edit screen notices.
		if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {
			$allowed_post_types = ! empty( $this->options['allowed_post_types'] ) ? $this->options['allowed_post_types'] : array();

			if ( in_array( $post->post_type, $allowed_post_types, true ) ) {
				$post_id                       = $post->ID;
				$discourse_publishing_response = get_post_meta( $post_id, 'wpdc_publishing_response', true );

				if ( ! empty( $discourse_publishing_response ) ) {

					if ( 'error' === $discourse_publishing_response ) {
						$error_message = __( '<div class="notice notice-error is-dismissible"><p>There has been an error publishing this post to Discourse.</p></div>', 'wp-discourse' );

						echo wp_kses_post( $error_message );
					}

					if ( 'success' === $discourse_publishing_response ) {
						$discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true );
						$discourse_link      = '<a href="' . esc_url( $discourse_permalink ) . '" target="_blank">' . __( 'View post', 'wp-discourse' ) . '</a>';

						$success_message = sprintf(
							// translators: Discourse post-published success message. Placeholder: discourse_permalink.
							__( '<div class="notice notice-success is-dismissible"><p>Your post has been published to Discourse. %1$s on Discourse.</p></div>', 'wp-discourse' ), $discourse_link
						);

						delete_post_meta( $post_id, 'wpdc_publishing_response' );

						echo wp_kses_post( $success_message );
					}
				}

				$discourse_username       = get_user_meta( get_current_user_id(), 'discourse_username', true );
				$current_username         = wp_get_current_user()->user_login;
				$publish_username         = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : '';
				$use_discourse_name_field = empty( $this->options['hide-discourse-name-field'] );

				$profile_page_link = '<a href="' . esc_url( admin_url( '/profile.php' ) ) . '">' . __( 'profile page', 'wp-discourse' ) . '</a>';

				if ( empty( $discourse_username )
					&& $use_discourse_name_field
					&& $current_username !== $publish_username
				) {
					$username_not_set_notice = sprintf(
						// translators: Discourse username_not_set notice. Placeholder: discourse_username.
						__( '<div class="notice notice-error is-dismissible"><p>You have not set your Discourse username. Any posts you publish to Discourse will be published under the system default username \'%1$s\'. To stop seeing this notice, please visit your %2$s and set your Discourse username.</p></div>', 'wp-discourse' ), $publish_username, $profile_page_link
					);

					echo wp_kses_post( $username_not_set_notice );
				}
			}// End if().
		}// End if().

		// All admin screen notices.
		if ( is_multisite() && is_main_site() && 'display' === get_option( 'wpdc_141_update_notice' ) ) {
			$options_moved_notice = __(
				'<div class="notice notice-warning is-dismissible"><p>As of WP Discourse version 1.4.1, the WP Discourse
network options have been moved to the Network Admin Dashboard.</p></div>', 'wp-discourse'
			);

			update_option( 'wpdc_141_update_notice', 'displayed' );

			echo wp_kses_post( $options_moved_notice );
		}

		// Setting changed notices.
		$settings_change_notice_seen = get_option( 'wpdc_settings_update_notice_150' );
		if ( ! empty( $this->options['auto-create-sso-user'] ) && ! $settings_change_notice_seen ) {
			add_option( 'wpdc_settings_update_notice_150', 1 );

			$settings_changed_notice = __(
				'<div class="notice notice-warning is-dismissible"><p>As of WP Discourse version 1.5.0, the <strong>Create Discourse User On
Login</strong> option no longer logs the user into Discourse. If you would like users to be able to login to your forum directly from a
page on your site, you will need to create an SSO login link with its <code>src</code> in this form: <code>http://community.example.com/session/sso?return_path=/</code>
with <code>return_path</code> set to the Discourse route you would like users to end up on.</p></div>'
			);

			echo wp_kses_post( $settings_changed_notice );
		}
	}
}
