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
				$discourse_username = get_user_meta( get_current_user_id(), 'discourse_username', true );
				$current_username = wp_get_current_user()->user_login;
				$publish_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : '';

				$profile_page_link = '<a href="' . esc_url( admin_url( '/profile.php' ) ) . '">' . __( 'profile page', 'wp-discourse' ) . '</a>';

				if ( empty( $discourse_username ) && $current_username !== $publish_username ) {
				    $username_not_set_notice = sprintf(
				    	// translators: Discourse username_not_set notice. Placeholder: discourse_username.
					__( '<div class="notice notice-error is-dismissible"><p>You have not set your Discourse username. Any posts you publish to Discourse will be published under the system default username \'%1$s\'. To stop seeing this notice, please visit your %2$s and set your Discourse username.</p></div>', 'wp-discourse' ), $publish_username, $profile_page_link );

				    echo wp_kses_post( $username_not_set_notice );
				}
			}
		}
	}
}
