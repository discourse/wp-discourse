<?php
/**
 * Sets up the plugin.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Discourse;

/**
 * Class Discourse
 */
class Discourse {

	/**
	 * Sets the plugin version.
	 *
	 * @var string
	 */
	public static $version = '0.7.0';

	/**
	 * The default options.
	 *
	 * The options can be accessed in any file with `get_option( 'discourse' )`.
	 *
	 * @var array
	 */
	static $options = array(
		'url'                       => '',
		'api-key'                   => '',
		'enable-sso'                => 0,
		'sso-secret'                => '',
		'publish-username'          => 'system',
		'display-subcategories'     => 0,
		'publish-category'          => '',
		'auto-publish'              => 0,
		'allowed_post_types'        => array( 'post' ),
		'auto-track'                => 1,
		'max-comments'              => 5,
		'use-discourse-comments'    => 0,
		'show-existing-comments'    => 0,
		'min-score'                 => 0,
		'min-replies'               => 1,
		'min-trust-level'           => 1,
		'custom-excerpt-length'     => 55,
		'bypass-trust-level-score'  => 50,
		'debug-mode'                => 0,
		'full-post-content'         => 0,
		'only-show-moderator-liked' => 0,
		'login-path'                => '',
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1 );
	}

	/**
	 * Adds the options 'discourse' and 'discourse_version'.
	 *
	 * Called with `register_activation_hook` from `wp-discourse.php`.
	 */
	public static function install() {
		update_option( 'discourse_version', self::$version );
		add_option( 'discourse', self::$options );
	}

	/**
	 * Adds 'discourse_username' to the user_contactmethods array.
	 *
	 * @param array $fields The array of contact methods.
	 *
	 * @return mixed
	 */
	function extend_user_profile( $fields ) {
		$fields['discourse_username'] = 'Discourse Username';

		return $fields;
	}
}
