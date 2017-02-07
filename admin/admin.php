<?php
/**
 * WP-Discourse admin settings
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/admin.php
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseAdmin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseAdmin
 */
class DiscourseAdmin {
	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	protected $option_input;

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		$this->option_input = new \WPDiscourse\OptionInput\OptionInput();
		new \WPDiscourse\ConnectionSettings\ConnectionSettings( $this->option_input );
		new \WPDiscourse\PublishSettings\PublishSettings( $this->option_input );
		new \WPDiscourse\CommentSettings\CommentSettings( $this->option_input );
		new \WPDiscourse\ConfigurableTextSettings\ConfigurableTextSettings( $this->option_input );
		new \WPDiscourse\SSOSettings\SSOSettings( $this->option_input );
		new \WPDiscourse\OptionsPage\OptionsPage();

	}

	/**
	 * Enqueues the admin stylesheet.
	 */
	public function admin_styles() {
		wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/admin/css/admin-styles.css' );
		wp_enqueue_style( 'wp_discourse_admin' );
	}
}
