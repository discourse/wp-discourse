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
	 * @var mixed|void
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
						__( '<div class="notice notice-success is-dismissible"><p>You\'re post has been published to Discourse. %1$s on Discourse.</p></div>', 'wp-discourse' ), $discourse_link );

						update_post_meta( $post_id, 'wpdc_publishing_response', 'success_displayed' );

						echo wp_kses_post( $success_message );
					}
				}

				$discourse_username = get_user_meta( get_current_user_id(), 'discourse_username', true );
				$current_username   = wp_get_current_user()->user_login;
				$publish_username   = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : '';

				$profile_page_link = '<a href="' . esc_url( admin_url( '/profile.php' ) ) . '">' . __( 'profile page', 'wp-discourse' ) . '</a>';

				if ( empty( $discourse_username ) && $current_username !== $publish_username ) {
					$username_not_set_notice = sprintf(
						// translators: Discourse username_not_set notice. Placeholder: discourse_username.
					__( '<div class="notice notice-error is-dismissible"><p>You have not set your Discourse username. Any posts you publish to Discourse will be published under the system default username \'%1$s\'. To stop seeing this notice, please visit your %2$s and set your Discourse username.</p></div>', 'wp-discourse' ), $publish_username, $profile_page_link );

					echo wp_kses_post( $username_not_set_notice );
				}
			}// End if().
		}// End if().
	}
}
