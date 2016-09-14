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
	public static $version = '0.9.9';

	static $discourse_connect = array(
		'url' => '',
		'api-key' => '',
		'publish-username' => 'system',
	);

	static $discourse_publish = array(
		'display-subcategories' => 0,
		'publish-category' => '',
		'auto-publish' => 0,
		'allowed_post_types' => array( 'post' ),
		'auto-track' => 1,
	);

	static $discourse_comment = array(
		'max-comments' => 5,
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
	);

	static $discourse_sso = array(
		'enable-sso' => 0,
		'sso-secret' => '',
		'login-path' => '',
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		add_action( 'admin_init', array( $this, 'initialize_plugin_options' ) );
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1 );
	}

	public function initialize_plugin_options() {
		if ( false === get_option( 'discourse_connect' ) ) {
			add_option( 'discourse_connect', self::$discourse_connect );
		}

		if ( false === get_option( 'discourse_publish' ) ) {
			add_option( 'discourse_publish', self::$discourse_publish );
		}

		if ( false === get_option( 'discourse_comment' ) ) {
			add_option( 'discourse_comment', self::$discourse_comment );
		}

		if ( false === get_option( 'discourse_sso' ) ) {
			add_option( 'discourse_sso', self::$discourse_sso );
		}
	}

	/**
	 * Adds the options 'discourse' and 'discourse_version'.
	 *
	 * Called with `register_activation_hook` from `wp-discourse.php`.
	 */
	public static function install() {
		update_option( 'discourse_version', self::$version );
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
