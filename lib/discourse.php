<?php
/**
 * WP-Discourse
 */
class Discourse {
  public static function homepage( $url, $post ) {
    return $url . "/users/" . strtolower( $post->username );
  }

  public static function avatar( $template, $size ) {
    return str_replace( "{size}", $size, $template );
  }

  // Version
  static $version ='0.6.6';

  // Options and defaults
  static $options = array(
    'url' => '',
    'api-key' => '',
    'enable-sso' => 0,
    'sso-secret' => '',
    'publish-username' => '',
    'publish-category' => '',
    'auto-publish' => 0,
    'allowed_post_types' => array( 'post', 'page' ),
    'auto-track' => 1,
    'max-comments' => 5,
    'use-discourse-comments' => 0,
    'publish-format' => '<small>Originally published at: {blogurl}</small><br>{excerpt}',
    'min-score' => 30,
    'min-replies' => 5,
    'min-trust-level' => 1,
    'custom-excerpt-length' => '55',
    'bypass-trust-level-score' => 50,
    'debug-mode' => 0,
    'full-post-content' => 0,
    'only-show-moderator-liked' => 0,
    'replies-html' => '<div id="comments" class="comments-area">
  <h2 class="comments-title">Notable Replies</h2>
  <ol class="comment-list">{comments}</ol>
  <div class="respond" class="comment-respond">
    <h3 id="reply-title" class="comment-reply-title"><a href="{topic_url}">Continue the discussion</a> at {discourse_url_name}</h3>
    <p class="more-replies">{more_replies}</p>
    <p class="comment-reply-title">{participants}</p>
  </div><!-- #respond -->
</div>',
      'no-replies-html' => '<div id="comments" class="comments-area">
  <div class="respond" class="comment-respond">
    <h3 id="reply-title" class="comment-reply-title"><a href="{topic_url}">Start the discussion</a> at {discourse_url_name}</h3>
  </div><!-- #respond -->
 </div>',
      'comment-html' => '<li class="comment even thread-even depth-1">
  <article class="comment-body">
    <footer class="comment-meta">
      <div class="comment-author vcard">
        <img alt="" src="{avatar_url}" class="avatar avatar-64 photo avatar-default" height="64" width="64">
        <b class="fn"><a href="{topic_url}" rel="external" class="url">{fullname}</a></b>
        <span class="says">says:</span>
      </div><!-- .comment-author -->
      <div class="comment-metadata">
        <time pubdate="" datetime="{comment_created_at}">{comment_created_at}</time>
      </div><!-- .comment-metadata -->
    </footer><!-- .comment-meta -->
    <div class="comment-content">{comment_body}</div><!-- .comment-content -->
  </article><!-- .comment-body -->
</li>',
      'participant-html' => '<img alt="" src="{avatar_url}" class="avatar avatar-25 photo avatar-default" height="25" width="25">'
  );

  public function __construct() {
    add_action( 'init', array( $this, 'init' ) );
    add_action( 'wp_footer', array( $this, 'discourse_comments_js' ), 100 );
  }

  static function install() {
    update_option( 'discourse_version', self::$version );
    add_option( 'discourse', self::$options );
  }

  public function init() {
    // allow translations
    load_plugin_textdomain( 'discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

    // replace comments with discourse comments
    add_filter( 'comments_number', array( $this, 'comments_number' ) );
    add_filter( 'comments_template', array( $this, 'comments_template' ) );
    add_filter( 'query_vars', array( $this, 'sso_add_query_vars' ) );

    wp_enqueue_script( 'jquery' );

    add_action( 'save_post', array( $this, 'save_postdata' ) );
    add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
    add_action( 'transition_post_status', array( $this, 'publish_post_to_discourse' ), 10, 3 );
    add_action( 'parse_query', array( $this, 'sso_parse_request' ) );
  }

  function discourse_comments_js() {
    if ( wp_script_is( 'jquery', 'done' ) ) {
  ?>
    <script>
    jQuery(document).ready(function() {
      jQuery('.lazyYT').each(function() {
        var id = jQuery(this).data('youtube-id'),
            url = 'https://www.youtube.com/watch?v=' + id;
        jQuery(this).replaceWith('<a href="' + url + '">' + url + '</a>');
      });
      jQuery('a.mention').each(function() {
        <?php
          $discourse_options = self::get_plugin_options();
          $discourse_url = $discourse_options['url'];
        ?>
        var discourse_url = '<?php echo $discourse_url; ?>';
        jQuery(this).attr('href', discourse_url + jQuery(this).attr('href'));
      });
    });
    </script>
  <?php
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
        $redirect = add_query_arg();

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
        global $current_user;
        get_currentuserinfo();

        // Map information
        $params = array(
          'nonce' => $nonce,
          'name' => $current_user->display_name,
          'username' => $current_user->user_login,
          'email' => $current_user->user_email,
          'about_me' => $current_user->description,
          'external_id' => $current_user->ID,
          'avatar_url' => self::get_avatar_url($current_user->ID)
        );

        // Build login string
        $q = $sso->buildLoginString( $params );

        // Redirect back to Discourse
        wp_redirect( $discourse_options['url'] . '/session/sso_login?' . $q );
        exit;
      }
    }
  }

  function get_avatar_url( $user_id ) {
    $avatar = get_avatar( $user_id );
    if( preg_match( "/src=['\"](.*?)['\"]/i", $avatar, $matches ) )
      return utf8_uri_encode( $matches[1] );
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

          $permalink = (string) get_post_meta( $postid, 'discourse_permalink', true ) . '/wordpress.json?' . $options;
          $result = wp_remote_get( $permalink );
          if ( is_wp_error( $result ) ) {
             error_log( $result->get_error_message() );
          } else {
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
        echo '<div class="discourse-existing-comments-heading">' . $options['existing-comments-heading'] . '</div>';
        return $old;
      }
    }
    // show the existing WP comments
    return $old;
  }

  function publish_post_to_discourse( $new_status, $old_status, $post ) {
    $publish_to_discourse = get_post_meta( $post->ID, 'publish_to_discourse', true );
    if ( ( self::publish_active() || ! empty( $publish_to_discourse ) ) && $new_status == 'publish' && self::is_valid_sync_post_type( $post->ID ) ) {
      // This seems a little redundant after `save_postdata` but when using the Press This
      // widget it updates the field as it should.

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

    $baked = $options['publish-format'];
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

    $category = $options['publish-category'];
    if ( $category === '' ) {
      $categories = get_the_category();
      foreach ( $categories as $category ) {
        if ( in_category( $category->name, $postid ) ) {
          $category = $category->name;
          break;
        }
      }
    }

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

    if( ! $discourse_id > 0 ) {
      $url =  $options['url'] .'/posts';

      // use key 'http' even if you send the request to https://...
      $post_options = array(
        'timeout' => 30,
        'method' => 'POST',
        'body' => http_build_query( $data ),
      );
      $result = wp_remote_post( $url, $post_options);
      if ( is_wp_error( $result ) ) {
        error_log( $result->get_error_message() );
      } else {
        $json = json_decode( $result['body'] );

        // todo may have $json->errors with list of errors

        if( property_exists( $json, 'id' ) ) {
          $discourse_id = (int) $json->id;
        }

        if( isset( $discourse_id ) && $discourse_id > 0 ) {
          add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
        }
      }
    } else {
      // for now the updates are just causing grief, leave'em out
      return;
      $url = $options['url'] .'/posts/' . $discourse_id ;
      $post_options = array( 'method' => 'PUT', 'body' => http_build_query( $data ) );
      $result = wp_remote_post( $url, $post_options );
      $json = json_decode( $result['body'] );

      if(isset( $json->post ) ) {
        $json = $json->post;
      }

      // todo may have $json->errors with list of errors
    }

    if( isset( $json->topic_slug ) ) {
      delete_post_meta( $postid, 'discourse_permalink' );
      add_post_meta( $postid, 'discourse_permalink', $options['url'] . '/t/' . $json->topic_slug . '/' . $json->topic_id, true );
    }
  }
}
