<?php
/**
 * WP-Discourse
 */
use WPDiscourse\Templates as Templates;

class Discourse {

	public static function homepage( $url, $post ) {
		return $url . "/users/" . strtolower( $post->username );
	}

	public static function avatar( $template, $size ) {
		return str_replace( "{size}", $size, $template );
	}

	// Version
	static $version = '0.7.0';

	// Options and defaults
	static $options = array(
		'url'                       => '',
		'api-key'                   => '',
		'enable-sso'                => 0,
		'sso-secret'                => '',
		'publish-username'          => 'system',
		'publish-category'          => '',
		'auto-publish'              => 0,
		'allowed_post_types'        => array( 'post' ),
		'auto-track'                => 1,
		'max-comments'              => 5,
		'use-discourse-comments'    => 0,
		'show-existing-comments'    => 0,
		'min-score'                 => 30,
		'min-replies'               => 1,
		'min-trust-level'           => 1,
		'custom-excerpt-length'     => 55,
		'bypass-trust-level-score'  => 50,
		'debug-mode'                => 0,
		'full-post-content'         => 0,
		'only-show-moderator-liked' => 0,
		'login-path'                => ''
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	static function install() {
		update_option( 'discourse_version', self::$version );
		add_option( 'discourse', self::$options );
	}

	public function init() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		add_filter( 'login_url', array( $this, 'set_login_url' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
	}

	// If a value has been supplied for the 'login-path' option, use it instead of
	// the default WordPress login path.
	function set_login_url( $login_url, $redirect ) {
		$options = self::get_plugin_options();
		if ( $options['login-path'] ) {
			$login_url = $options['login-path'];

			if ( ! empty( $redirect ) ) {
				return add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );

			} else {
				return $login_url;
			}
		}

		if ( ! empty( $redirect ) ) {
			return add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
		} else {
			return $login_url;
		}

	}

	function admin_styles() {
		wp_register_style( 'wp_discourse_admin', WPDISCOURSE_URL . '/css/admin-styles.css' );
		wp_enqueue_style( 'wp_discourse_admin' );
	}

	static function convert_relative_img_src_to_absolute( $url, $content ) {
		if ( preg_match( "/<img\s*src\s*=\s*[\'\"]?(https?:)?\/\//i", $content ) ) {
			return $content;
		}

		$search  = '#<img src="((?!\s*[\'"]?(?:https?:)?\/\/)\s*([\'"]))?#';
		$replace = "<img src=\"{$url}$1";

		return preg_replace( $search, $replace, $content );
	}

	static function get_plugin_options() {
		return wp_parse_args( get_option( 'discourse' ), Discourse::$options );
	}
}
