<?php
/**
 * WP-Discourse
 */
use WPDiscourse\Templates as Templates;

class Discourse {
	protected $response_validator;

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
	 *
	 * Takes a `response_validator` object as a parameter.
	 * The `response_validator` has a `validate()` method that validates the response
	 * from `wp_remote_get` and `wp_remote_post`.
	 *
	 * @param $response_validator
	 */
	public function __construct( $response_validator ) {
		$this->response_validator = $response_validator;

		add_action( 'init', array( $this, 'init' ) );
	}

	static function install() {
		update_option( 'discourse_version', self::$version );
		add_option( 'discourse', self::$options );
	}

	public function init() {
		// allow translations
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		// replace comments with discourse comments
		add_filter( 'comments_number', array( $this, 'comments_number' ) );
		add_filter( 'comments_template', array( $this, 'comments_template' ), 20, 1 );
		add_filter( 'login_url', array( $this, 'set_login_url' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'discourse_comments_js' ) );
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

	function discourse_comments_js() {
		// Allowed post type
//		if ( is_singular( self::get_allowed_post_types() ) ) {
		if ( is_singular( self::get_plugin_options()['allowed_post_types'] ) ) {
			// Publish to Discourse enabled
			if ( self::use_discourse_comments( get_the_ID() ) ) {
				// Enqueue script
				wp_enqueue_script(
					'discourse-comments-js',
					WPDISCOURSE_URL . '/js/comments.js',
					array( 'jquery' ),
					self::$version,
					true
				);
				// Localize script
				$discourse_options = self::get_plugin_options();
				$data              = array(
					'url' => $discourse_options['url'],
				);
				wp_localize_script( 'discourse-comments-js', 'discourse', $data );
			}
		}
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

	function comments_number( $count ) {
		global $post;
		if ( self::use_discourse_comments( $post->ID ) ) {
			self::sync_comments( $post->ID );
			$count = get_post_meta( $post->ID, 'discourse_comments_count', true );
			if ( ! $count ) {
				$count = 'Leave a reply';
			} else {
				$count = $count == 1 ? '1 Reply' : $count . ' Replies';
			}
		}

		return $count;
	}

	function use_discourse_comments( $postid ) {
		// If "use comments" is disabled, bail out
		$options = self::get_plugin_options();
		if ( ! $options['use-discourse-comments'] ) {
			return 0;
		}

		$setting = get_post_meta( $postid, 'publish_to_discourse', true );

		return $setting == '1';
	}

	function sync_comments( $postid ) {
		global $wpdb;
		$discourse_options = self::get_plugin_options();

		// every 10 minutes do a json call to sync comment count and top comments
		$last_sync = (int) get_post_meta( $postid, 'discourse_last_sync', true );
		$time      = date_create()->format( 'U' );
		$debug     = isset( $discourse_options['debug-mode'] ) && intval( $discourse_options['debug-mode'] ) == 1;

		if ( $debug || $last_sync + 60 * 10 < $time ) {
			$got_lock = $wpdb->get_row( "SELECT GET_LOCK( 'discourse_lock', 0 ) got_it" );
			if ( $got_lock->got_it == '1' ) {
				if ( get_post_status( $postid ) == 'publish' ) {

					$comment_count            = intval( $discourse_options['max-comments'] );
					$min_trust_level          = intval( $discourse_options['min-trust-level'] );
					$min_score                = intval( $discourse_options['min-score'] );
					$min_replies              = intval( $discourse_options['min-replies'] );
					$bypass_trust_level_score = intval( $discourse_options['bypass-trust-level-score'] );

					$options = 'best=' . $comment_count . '&min_trust_level=' . $min_trust_level . '&min_score=' . $min_score;
					$options = $options . '&min_replies=' . $min_replies . '&bypass_trust_level_score=' . $bypass_trust_level_score;

					if ( isset( $discourse_options['only-show-moderator-liked'] ) && intval( $discourse_options['only-show-moderator-liked'] ) == 1 ) {
						$options = $options . '&only_moderator_liked=true';
					}
					$options = $options . '&api_key=' . $discourse_options['api-key'] . '&api_username=' . $discourse_options['publish-username'];

					$permalink = esc_url_raw( get_post_meta( $postid, 'discourse_permalink', true ) ) . '/wordpress.json?' . $options;
					$result    = wp_remote_get( $permalink );

					if ( $this->response_validator->validate( $result ) ) {

						$json = json_decode( $result['body'] );

						if ( isset( $json->posts_count ) ) {
							$posts_count = $json->posts_count - 1;
							if ( $posts_count < 0 ) {
								$posts_count = 0;
							}

							delete_post_meta( $postid, 'discourse_comments_count' );
							add_post_meta( $postid, 'discourse_comments_count', $posts_count, true );

							delete_post_meta( $postid, 'discourse_comments_raw' );
							add_post_meta( $postid, 'discourse_comments_raw', esc_sql( $result['body'] ), true );

							delete_post_meta( $postid, 'discourse_last_sync' );
							add_post_meta( $postid, 'discourse_last_sync', $time, true );
						}
					}
				}
				$wpdb->get_results( "SELECT RELEASE_LOCK( 'discourse_lock' )" );
			}
		}
	}

	function comments_template( $old ) {
		global $post;

		if ( self::use_discourse_comments( $post->ID ) ) {
			self::sync_comments( $post->ID );
			$options         = self::get_plugin_options();
			$num_WP_comments = get_comments_number();
			if ( ! $options['show-existing-comments'] || $num_WP_comments == 0 ) {
				// only show the Discourse comments
				return WPDISCOURSE_PATH . '/templates/comments.php';
			} else {
				// show the Discourse comments then show the existing WP comments (in $old)
				include WPDISCOURSE_PATH . '/templates/comments.php';
				echo '<div class="discourse-existing-comments-heading">' . wp_kses_post( $options['existing-comments-heading'] ) . '</div>';

				return $old;
			}
		}

		// show the existing WP comments
		return $old;
	}
}
