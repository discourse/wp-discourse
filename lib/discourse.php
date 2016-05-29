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
  static $version ='0.7.0';

  // Options and defaults
  static $options = array(
    'url' => '',
    'api-key' => '',
    'enable-sso' => 0,
    'sso-secret' => '',
    'publish-username' => 'system',
    'publish-category' => '',
    'auto-publish' => 0,
    'allowed_post_types' => array( 'post' ),
    'auto-track' => 1,
    'max-comments' => 5,
    'use-discourse-comments' => 0,
    'show-existing-comments' => 0,
    'min-score' => 30,
    'min-replies' => 1,
    'min-trust-level' => 1,
    'custom-excerpt-length' => 55,
    'bypass-trust-level-score' => 50,
    'debug-mode' => 0,
    'full-post-content' => 0,
    'only-show-moderator-liked' => 0,
    'login-path' => ''
  );

  /**
   * Discourse constructor.
   *
   * @param $response_validator validates the response from Discourse and sets
   * and gets the connection status with Discourse.
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
    add_filter( 'query_vars', array( $this, 'sso_add_query_vars' ) );
    add_filter( 'login_url', array( $this, 'set_login_url' ), 10, 2 );

    add_action( 'wp_enqueue_scripts', array( $this, 'discourse_comments_js' ) );

    add_action( 'save_post', array( $this, 'save_postdata' ) );
    add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
    add_action( 'transition_post_status', array( $this, 'publish_post_to_discourse' ), 10, 3 );
    add_action( 'parse_query', array( $this, 'sso_parse_request' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
  }

  // If a value has been supplied for the 'login-path' option, use it instead of
  // the default WordPress login path.
  function set_login_url( $login_url, $redirect ) {
    $options = self::get_plugin_options();
    if ( $options['login-path'] ) {
      $login_url = $options['login-path'];

      if ( !empty( $redirect ) ) {
        return add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );

      } else {
        return $login_url;
      }
    }

    if ( !empty( $redirect ) ) {
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
    if ( is_singular( self::get_allowed_post_types() ) ) {
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
        $data = array(
          'url' => $discourse_options['url'],
        );
        wp_localize_script( 'discourse-comments-js', 'discourse', $data );
      }
    }
  }

  function sso_add_query_vars( $vars ) {
      $vars[] = "sso";
      $vars[] = "sig";
      return $vars;
  }

  // SSO Request Processing from Adam Capirola : https://gist.github.com/adamcapriola/11300529
  function sso_parse_request( $wp )
  {
    $discourse_options = self::get_plugin_options();

    // sync logout from Discourse to WordPress from Adam Capirola : https://meta.discourse.org/t/wordpress-integration-guide/27531
    // to make this work, enter a URL of the form "http://my-wp-blog.com/?request=logout" in the "logout redirect"
    // field in your Discourse admin
    if (isset( $discourse_options['enable-sso'] ) &&
        intval( $discourse_options['enable-sso'] ) == 1 &&
        isset( $_GET['request'] ) && $_GET['request'] == 'logout' ) {

      wp_logout();
      wp_redirect( $discourse_options['url'] );
      exit;
    }
    // end logout processing

    // only process requests with "my-plugin=ajax-handler"
    if ( isset( $discourse_options['enable-sso'] ) &&
         intval( $discourse_options['enable-sso'] ) == 1 &&
         array_key_exists('sso', $wp->query_vars) &&
         array_key_exists('sig', $wp->query_vars) ) {

      // Not logged in to WordPress, redirect to WordPress login page with redirect back to here
      if ( ! is_user_logged_in() ) {

        // Preserve sso and sig parameters
        $redirect = add_query_arg( NULL, NULL );

        // Change %0A to %0B so it's not stripped out in wp_sanitize_redirect
        $redirect = str_replace( '%0A', '%0B', $redirect );

        // Build login URL
        $login = wp_login_url( esc_url_raw( $redirect ) );

        // Redirect to login
        wp_redirect( $login );
        exit;
      }
      else {

        // Check for helper class
        if ( ! class_exists( 'Discourse_SSO' ) ) {
          // Error message
          echo( 'Helper class is not properly included.' );
          exit;
        }

        // Payload and signature
        $payload = $wp->query_vars['sso'];
        $sig = $wp->query_vars['sig'];

        // Change %0B back to %0A
        $payload = urldecode( str_replace( '%0B', '%0A', urlencode( $payload ) ) );

        // Validate signature
        $sso_secret = $discourse_options['sso-secret'];
        $sso = new Discourse_SSO( $sso_secret );
        if ( ! ( $sso->validate( $payload, $sig ) ) ) {
          // Error message
          echo( 'Invalid request.' );
          exit;
        }

        // Nonce
        $nonce = $sso->getNonce( $payload );

        // Current user info
        $current_user = wp_get_current_user();

        // Map information
        $params = array(
          'nonce' => $nonce,
          'name' => $current_user->display_name,
          'username' => $current_user->user_login,
          'email' => $current_user->user_email,
          'about_me' => $current_user->description,
          'external_id' => $current_user->ID,
          'avatar_url' => get_avatar_url(get_current_user_id())
        );

        // Build login string
        $q = $sso->buildLoginString( $params );

        // Redirect back to Discourse
        wp_redirect( $discourse_options['url'] . '/session/sso_login?' . $q );
        exit;
      }
    }
  }

  static function convert_relative_img_src_to_absolute($url, $content) {
    if( preg_match( "/<img\s*src\s*=\s*[\'\"]?(https?:)?\/\//i", $content) )
      return $content;

    $search = '#<img src="((?!\s*[\'"]?(?:https?:)?\/\/)\s*([\'"]))?#';
    $replace = "<img src=\"{$url}$1";
    return preg_replace($search, $replace, $content);
  }

  static function get_plugin_options() {
    return wp_parse_args( get_option( 'discourse' ), Discourse::$options );
  }

  function comments_number( $count ) {
    global $post;
    if( self::use_discourse_comments( $post->ID ) ) {
      self::sync_comments( $post->ID );
      $count = get_post_meta( $post->ID, 'discourse_comments_count', true );
      if( ! $count ) {
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
    $time = date_create()->format( 'U' );
    $debug = isset( $discourse_options['debug-mode'] ) && intval( $discourse_options['debug-mode'] ) == 1;

    if( $debug || $last_sync + 60 * 10 < $time ) {
      $got_lock = $wpdb->get_row( "SELECT GET_LOCK( 'discourse_lock', 0 ) got_it" );
      if( $got_lock->got_it == '1' ) {
        if( get_post_status( $postid ) == 'publish' ) {
          // workaround unpublished posts, publish if needed
          // if you have a scheduled post we never seem to be called
          if( ! ( get_post_meta( $postid, 'discourse_post_id', true ) > 0 ) ) {
            $post = get_post( $postid );
            self::publish_post_to_discourse( 'publish', 'publish', $post );
          }

          $comment_count = intval( $discourse_options['max-comments'] );
          $min_trust_level = intval( $discourse_options['min-trust-level'] );
          $min_score = intval( $discourse_options['min-score'] );
          $min_replies = intval( $discourse_options['min-replies'] );
          $bypass_trust_level_score = intval( $discourse_options['bypass-trust-level-score'] );

          $options = 'best=' . $comment_count . '&min_trust_level=' . $min_trust_level . '&min_score=' . $min_score;
          $options = $options . '&min_replies=' . $min_replies . '&bypass_trust_level_score=' . $bypass_trust_level_score;

          if ( isset( $discourse_options['only-show-moderator-liked'] ) && intval( $discourse_options['only-show-moderator-liked'] ) == 1 ) {
            $options = $options . '&only_moderator_liked=true';
          }
          $options = $options . '&api_key=' . $discourse_options['api-key'] . '&api_username=' . $discourse_options['publish-username'];

          $permalink = esc_url_raw( get_post_meta( $postid, 'discourse_permalink', true ) ) . '/wordpress.json?' . $options;
          $result = wp_remote_get( $permalink );

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
              add_post_meta( $postid, 'discourse_comments_raw', esc_sql( $result['body'] ) , true );

              delete_post_meta( $postid, 'discourse_last_sync' );
              add_post_meta( $postid, 'discourse_last_sync', $time, true );
            }
          }
        }
        $wpdb->get_results( "SELECT RELEASE_LOCK( 'discourse_lock' )" );
      }
    } else {
      $this->response_validator->update_connection_status( 60 );
    }
  }


  function comments_template( $old ) {
    global $post;

    if( self::use_discourse_comments( $post->ID ) ) {
      self::sync_comments( $post->ID );
      $options = self::get_plugin_options();
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

  function publish_post_to_discourse( $new_status, $old_status, $post ) {
    $publish_to_discourse = get_post_meta( $post->ID, 'publish_to_discourse', true );
    $publish_post_category = get_post_meta( $post->ID, 'publish_post_category', true );

    if ( ( self::publish_active() || ! empty( $publish_to_discourse ) ) && $new_status == 'publish' && self::is_valid_sync_post_type( $post->ID ) ) {
      // This seems a little redundant after `save_postdata` but when using the Press This
      // widget it updates the field as it should.

      if( isset( $_POST['publish_post_category'] ) ){
        #delete_post_meta( $post->ID, 'publish_post_category');
        add_post_meta( $post->ID, 'publish_post_category', $_POST['publish_post_category'], true );
      }

      add_post_meta( $post->ID, 'publish_to_discourse', '1', true );

      self::sync_to_discourse( $post->ID, $post->post_title, $post->post_content );
    }
  }

  // When publishing by xmlrpc, ignore the `publish_to_discourse` option
  function xmlrpc_publish_post_to_discourse( $postid ) {
    $post = get_post( $postid );
    if ( get_post_status( $postid ) == 'publish' && self::is_valid_sync_post_type( $postid ) ) {
      add_post_meta( $postid, 'publish_to_discourse', '1', true );
      self::sync_to_discourse( $postid, $post->post_title, $post->post_content );
    }
  }

  function is_valid_sync_post_type( $postid = NULL ) {
    // is_single() etc. is not reliable
    $allowed_post_types = $this->get_allowed_post_types();
    $current_post_type  = get_post_type( $postid );

    return in_array( $current_post_type, $allowed_post_types );
  }

  function get_allowed_post_types() {
    $discourse_options = self::get_plugin_options();
    $selected_post_types = $discourse_options['allowed_post_types'];

    /** If no post type is explicitly set then use the defaults */
    if ( empty( $selected_post_types ) ) {
      $selected_post_types = self::$options['allowed_post_types'];
    }

    return $selected_post_types;
  }

  function publish_active() {
    if ( isset( $_POST['showed_publish_option'] ) && isset( $_POST['publish_to_discourse'] ) ) {
      return $_POST['publish_to_discourse'] == '1';
    }

    return false;
  }

  function save_postdata( $postid ) {
    if ( ! current_user_can( 'edit_page', $postid ) ) {
      return $postid;
    }

    if ( empty( $postid ) ) {
      return $postid;
    }

    // trust me ... WordPress is crazy like this, try changing a title.
    if( ! isset( $_POST['ID'] ) ) {
      return $postid;
    }

    if( $_POST['action'] == 'editpost' ) {
      delete_post_meta( $_POST['ID'], 'publish_to_discourse' );
    }

    if ( isset( $_POST['publish_post_category'] ) ){
      delete_post_meta($_POST['ID'], 'publish_post_category');
      add_post_meta( $_POST['ID'], 'publish_post_category',  $_POST['publish_post_category'], true );
    }

    add_post_meta( $_POST['ID'], 'publish_to_discourse', self::publish_active() ? '1' : '0', true );

    return $postid;
  }

  function sync_to_discourse( $postid, $title, $raw ) {
    global $wpdb;

    // this avoids a double sync, just 1 is allowed to go through at a time
    $got_lock = $wpdb->get_row( "SELECT GET_LOCK('discourse_sync_lock', 0) got_it" );
    if ( $got_lock ) {
      self::sync_to_discourse_work( $postid, $title, $raw );
      $wpdb->get_results( "SELECT RELEASE_LOCK('discourse_sync_lock')" );
    }
  }

  function sync_to_discourse_work( $postid, $title, $raw ) {
    $discourse_id = get_post_meta( $postid, 'discourse_post_id', true );
    $options = self::get_plugin_options();
    $post = get_post( $postid );
    $use_full_post = isset( $options['full-post-content'] ) && intval( $options['full-post-content'] ) == 1;

    if ($use_full_post) {
      $excerpt = $raw;
    } else {
      $excerpt = apply_filters( 'the_content', $raw );
      $excerpt = wp_trim_words( $excerpt, $options['custom-excerpt-length'] );
    }

    if ( function_exists( 'discourse_custom_excerpt' ) ) {
      $excerpt = discourse_custom_excerpt( $postid );
    }

    // trim to keep the Discourse markdown parser from treating this as code.
    $baked = trim( Templates\HTMLTemplates::publish_format_html() );
    $baked = str_replace( "{excerpt}", $excerpt, $baked );
    $baked = str_replace( "{blogurl}", get_permalink( $postid ), $baked );
    $author_id = $post->post_author;
    $author = get_the_author_meta( 'display_name', $author_id );
    $baked = str_replace( "{author}", $author, $baked);
    $thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $postid ), 'thumbnail' );
    $baked = str_replace( "{thumbnail}", "![image](".$thumb['0'].")", $baked );
    $featured = wp_get_attachment_image_src( get_post_thumbnail_id( $postid ), 'full' );
    $baked = str_replace( "{featuredimage}", "![image](".$featured['0'].")", $baked );

    $username = get_the_author_meta( 'discourse_username', $post->post_author );
    if( ! $username || strlen( $username ) < 2 ) {
      $username = $options['publish-username'];
    }

    // Get publish category of a post
    $publish_post_category = get_post_meta( $post->ID, 'publish_post_category', true );
    $publish_post_category =  $post->publish_post_category;
    $default_category = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
    $category = isset( $publish_post_category ) ? $publish_post_category : $default_category;

    if ( $category === '' ) {
      $categories = get_the_category();
      foreach ( $categories as $category ) {
        if ( in_category( $category->name, $postid ) ) {
          $category = $category->name;
          break;
        }
      }
    }

    if( ! $discourse_id > 0 ) {
      $data = array(
          'wp-id' => $postid,
          'embed_url' => get_permalink( $postid ),
          'api_key' => $options['api-key'],
          'api_username' => $username,
          'title' => $title,
          'raw' => $baked,
          'category' => $category,
          'skip_validations' => 'true',
          'auto_track' => ( $options['auto-track'] == "1" ? 'true' : 'false' )
      );
      $url =  $options['url'] .'/posts';
      // use key 'http' even if you send the request to https://...
      $post_options = array(
        'timeout' => 30,
        'method' => 'POST',
        'body' => http_build_query( $data ),
      );
      $result = wp_remote_post( $url, $post_options);

      if ( $this->response_validator->validate( $result ) ) {
        $json = json_decode( $result['body'] );

        if( property_exists( $json, 'id' ) ) {
          $discourse_id = (int) $json->id;
        }

        if( isset( $discourse_id ) && $discourse_id > 0 ) {
          add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
        }
      }

    } else {
      $data = array(
          'api_key' => $options['api-key'],
          'api_username' => $username,
          'post[raw]' => $baked,
          'skip_validations' => 'true',
      );
      $url = $options['url'] .'/posts/' . $discourse_id ;
      $post_options = array(
          'timeout' => 30,
          'method' => 'PUT',
          'body' => http_build_query( $data ),
      );
      $result = wp_remote_post( $url, $post_options);

      if ( $this->response_validator->validate( $result ) ) {
        $json = json_decode( $result['body'] );

        if( property_exists( $json, 'id' ) ) {
          $discourse_id = (int) $json->id;
        }

        if( isset( $discourse_id ) && $discourse_id > 0 ) {
          add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
        }
      }
    }

    if( isset( $json->topic_slug ) ) {
      delete_post_meta( $postid, 'discourse_permalink' );
      add_post_meta( $postid, 'discourse_permalink', $options['url'] . '/t/' . $json->topic_slug . '/' . $json->topic_id, true );
    }
  }

}
