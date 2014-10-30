<?php
/*
Plugin Name: WP-Discourse
Description: Allows you to publish your posts to a Discourse instance and view top Discourse comments on your blog
Version: 0.6.1
Author: Sam Saffron, Robin Ward
Author URI: https://github.com/discourse/wp-discourse
*/
/*  Copyright 2014 Civilized Discourse Construction Kit, Inc (team@discourse.org)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
class Discourse {
  public static function homepage( $url, $post ) {
    return $url . "/users/" . strtolower( $post->username );
  }

  public static function avatar( $template, $size ) {
    return str_replace( "{size}", $size, $template );
  }

  var $domain = 'discourse';

  // Version
  static $version ='0.0.1';

  // Options and defaults
  static $options = array(
    'url' => '',
    'api-key' => '',
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
    register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
    register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
    add_action( 'init', array( $this, 'init' ) );
    add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'admin_menu', array( $this, 'discourse_admin_menu' ) );
  }

  static function install() {
    update_option( 'discourse_version', self::$version );
    add_option( 'discourse', self::$options );
  }

  static function uninstall() {
    delete_option( 'discourse_version' );
    delete_option( 'discourse' );
  }


  public function init() {
    // allow translations
    load_plugin_textdomain( 'discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

    // replace comments with discourse comments
    add_filter( 'comments_number', array( $this, 'comments_number' ) );
    add_filter( 'comments_template', array( $this, 'comments_template' ) );

    $plugin_dir = plugin_dir_url( __FILE__ );
    wp_register_style( 'discourse_comments', $plugin_dir . 'css/style.css' );
    wp_enqueue_style( 'discourse_comments' );

    wp_register_script( 'discourse_comments_js', $plugin_dir . 'js/discourse.js', array( 'jquery' ));
    wp_enqueue_script( 'discourse_comments_js' );

    add_action( 'save_post', array( $this, 'save_postdata' ) );
    add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
    add_action( 'transition_post_status', array( $this, 'publish_post_to_discourse' ), 10, 3 );
  }

  function get_plugin_options() {
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

          $permalink = (string) get_post_meta( $postid, 'discourse_permalink', true ) . '/wordpress.json?' . $options;
          $soptions = array( 'http' => array( 'ignore_errors' => true, 'method'  => 'GET' ) );
          $context = stream_context_create( $soptions );
          $result = file_get_contents( $permalink, false, $context );
          $json = json_decode( $result );

          if ( isset( $json->posts_count ) ) {
            $posts_count = $json->posts_count - 1;
            if ( $posts_count < 0 ) {
              $posts_count = 0;
            }

            delete_post_meta( $postid, 'discourse_comments_count' );
            add_post_meta( $postid, 'discourse_comments_count', $posts_count, true );

            delete_post_meta( $postid, 'discourse_comments_raw' );

            add_post_meta( $postid, 'discourse_comments_raw', esc_sql( $result ) , true );

            delete_post_meta( $postid, 'discourse_last_sync' );
            add_post_meta( $postid, 'discourse_last_sync', $time, true );
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
      return dirname(__FILE__) . '/comments.php';
    }

    return $old;
  }

  /*
  * Settings
  */
  public function admin_init() {
    register_setting( 'discourse', 'discourse', array( $this, 'discourse_validate_options' ) );
    add_settings_section( 'default_discourse', 'Default Settings', array( $this, 'init_default_settings' ), 'discourse' );

    add_settings_field( 'discourse_url', 'Url', array( $this, 'url_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_api_key', 'API Key', array( $this, 'api_key_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_publish_username', 'Publishing username', array( $this, 'publish_username_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_publish_category', 'Published category', array( $this, 'publish_category_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_publish_format', 'Publish format', array( $this, 'publish_format_textarea' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_auto_publish', 'Auto Publish', array( $this, 'auto_publish_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_auto_track', 'Auto Track Published Topics', array( $this, 'auto_track_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_allowed_post_types', 'Post Types to publish to Discourse', array( $this, 'post_types_select' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_use_discourse_comments', 'Use Discourse Comments', array( $this, 'use_discourse_comments_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_max_comments', 'Max visible comments', array( $this, 'max_comments_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_min_replies', 'Min number of replies', array( $this, 'min_replies_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_min_score', 'Min score of posts', array( $this, 'min_score_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_min_trust_level', 'Min trust level', array( $this, 'min_trust_level_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_bypass_trust_level_score', 'Bypass trust level score', array( $this, 'bypass_trust_level_input' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_custom_excerpt_length', 'Custom excerpt length', array( $this, 'custom_excerpt_length' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_debug_mode', 'Debug mode', array( $this, 'debug_mode_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_full_post_content', 'Use full post content', array( $this, 'full_post_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_only_show_moderator_liked', 'Only import comments liked by a moderator', array( $this, 'only_show_moderator_liked_checkbox' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_template_replies', 'HTML Template to use when there are replies', array( $this, 'template_replies_html' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_template_no_replies', 'HTML Template to use when there are no replies', array( $this, 'template_no_replies_html' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_template_comment', 'HTML Template to use for each comment', array( $this, 'template_comment_html' ), 'discourse', 'default_discourse' );
    add_settings_field( 'discourse_participant_comment', 'HTML Template to use for each participant', array( $this, 'template_participant_html' ), 'discourse', 'default_discourse' );

    add_action( 'post_submitbox_misc_actions', array( $this, 'publish_to_discourse' ) );

    add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1);
  }

  function extend_user_profile( $fields ) {
    $fields['discourse_username'] = 'Discourse Username';
    return $fields;
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
    $selected_post_types = get_option( 'discourse_allowed_post_types' );

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

    $data = array(
      'wp-id' => $postid,
      'embed_url' => get_permalink( $postid ),
      'api_key' => $options['api-key'],
      'api_username' => $username,
      'title' => $title,
      'raw' => $baked,
      'category' => $options['publish-category'],
      'skip_validations' => 'true',
      'auto_track' => ( $options['auto-track'] == "1" ? 'true' : 'false' )
    );

    if( ! $discourse_id > 0 ) {
      $url =  $options['url'] .'/posts';

      // use key 'http' even if you send the request to https://...
      $soptions = array(
        'http' => array(
          'ignore_errors' => true,
          'method'  => 'POST',
          'content' => http_build_query( $data ),
          'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
        )
      );
      $context = stream_context_create( $soptions );
      $result = file_get_contents( $url, false, $context );
      $json = json_decode( $result );

      // todo may have $json->errors with list of errors

      if( property_exists( $json, 'id' ) ) {
        $discourse_id = (int) $json->id;
      }

      if( isset( $discourse_id ) && $discourse_id > 0 ) {
        add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
      }
    } else {
      // for now the updates are just causing grief, leave'em out
      return;
      $url = $options['url'] .'/posts/' . $discourse_id ;
      $soptions = array( 'http' => array( 'ignore_errors' => true, 'method'  => 'PUT','content' => http_build_query( $data) ));
      $context = stream_context_create( $soptions);
      $result = file_get_contents( $url, false, $context );
      $json = json_decode( $result );

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


  function publish_to_discourse() {
    global $post;

    $options = self::get_plugin_options();

    if( in_array( $post->post_type, $options['allowed_post_types'] ) ) {
      if( $post->post_status == 'auto-draft' ) {
        $value = $options['auto-publish'];
      } else {
        $value = get_post_meta( $post->ID, 'publish_to_discourse', true );
      }

      echo '<div class="misc-pub-section misc-pub-section-last">
           <span>'
           . '<input type="hidden" name="showed_publish_option" value="1">'
           . '<label><input type="checkbox"' . (( $value == "1") ? ' checked="checked" ' : null) . 'value="1" name="publish_to_discourse" /> Publish to Discourse</label>'
      .'</span></div>';
    }
  }


  function init_default_settings() {

  }

  function url_input() {
    self::text_input( 'url', 'Enter your discourse url Eg: http://discuss.mysite.com' );
  }

  function api_key_input() {
    self::text_input( 'api-key', '' );
  }

  function publish_username_input() {
    self::text_input( 'publish-username', 'Discourse username of publisher (will be overriden if Discourse Username is specified on user)' );
  }

  function publish_category_input() {
    self::text_input( 'publish-category', 'Category post will be published in Discourse (optional)' );
  }

  function publish_format_textarea() {
    self::text_area( 'publish-format', 'Markdown format for published articles, use {excerpt} for excerpt and {blogurl} for the url of the blog post' );
  }

  function max_comments_input() {
    self::text_input( 'max-comments', 'Maximum number of comments to display' );
  }

  function auto_publish_checkbox() {
    self::checkbox_input( 'auto-publish', 'Publish all new posts to Discourse' );
  }

  function auto_track_checkbox() {
    self::checkbox_input( 'auto-track', 'Author automatically tracks published Discourse topics' );
  }

  function post_types_select() {
    self::post_type_select_input( 'allowed_post_types', get_post_types() );
  }

  function use_discourse_comments_checkbox() {
    self::checkbox_input( 'use-discourse-comments', 'Use Discourse to comment on Discourse published posts (hiding existing comment section)' );
  }

  function min_replies_input() {
    self::text_input( 'min-replies', 'Minimum replies required prior to pulling comments across' );
  }

  function min_trust_level_input() {
    self::text_input( 'min-trust-level', 'Minimum trust level required prior to pulling comments across (0-5)' );
  }

  function min_score_input() {
    self::text_input( 'min-score', 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read)' );
  }

  function custom_excerpt_length() {
    self::text_input( 'custom-excerpt-length', 'Custom excerpt length in words (default: 55)' );
  }

  function bypass_trust_level_input() {
    self::text_input( 'bypass-trust-level-score', 'Bypass trust level check on posts with this score' );
  }

  function debug_mode_checkbox() {
    self::checkbox_input( 'debug-mode', '(always refresh comments)' );
  }

  function full_post_checkbox() {
    self::checkbox_input( 'full-post-content', 'Use the full post for content rather than an excerpt.' );
  }

  function only_show_moderator_liked_checkbox() {
    self::checkbox_input( 'only-show-moderator-liked', 'Yes' );
  }

  function template_replies_html() {
    self::text_area( 'replies-html', 'HTML template to use when there are replies<br/>Available tags: <small>{comments}, {discourse_url}, {discourse_url_name}, {topic_url}, {more_replies}, {participants}</small>' );
  }

  function template_no_replies_html() {
    self::text_area( 'no-replies-html', 'HTML template to use when there are no replies<br/>Available tags: <small>{comments}, {discourse_url}, {discourse_url_name}, {topic_url}</small>' );
  }

  function template_comment_html() {
    self::text_area( 'comment-html', 'HTML template to use for each comment<br/>Available tags: <small>{discourse_url}, {discourse_url_name}, {topic_url}, {avatar_url}, {user_url}, {username}, {fullname}, {comment_body}, {comment_created_at}, {comment_url}</small>' );
  }

  function template_participant_html() {
    self::text_area( 'participant-html', 'HTML template to use for each participant<br/>Available tags: <small>{discourse_url}, {discourse_url_name}, {topic_url}, {avatar_url}, {user_url}, {username}, {fullname}</small>' );
  }

  function checkbox_input( $option, $description) {
    $options = get_option( 'discourse' );
    if (array_key_exists( $option, $options) and $options[$option] == 1) {
      $value = 'checked="checked"';
    } else {
      $value = '';
    }

    ?>
    <input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='checkbox' value='1' <?php echo $value?> /> <?php echo $description ?>
    <?php
  }

  function post_type_select_input( $option, $post_types) {
    $options = get_option( 'discourse' );

    echo "<select multiple id='discourse_allowed_post_types' name='discourse[allowed_post_types][]'>";

    foreach ( $post_types as $post_type ) {

      if (array_key_exists( $option, $options) and in_array( $post_type, $options[$option] ) ) {
        $value = 'selected';
      } else {
        $value = '';
      }

      echo "<option ".$value." value='".$post_type."''>".$post_type."</option>";
    }

    echo '</select>';

  }

  function text_input( $option, $description ) {
    $options = get_option( 'discourse' );

    if ( array_key_exists( $option, $options ) ) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
    <input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='text' value='<?php echo esc_attr( $value ); ?>' /> <?php echo $description ?>
    <?php

  }

  function text_area( $option, $description) {
    $options = get_option( 'discourse' );

    if ( array_key_exists( $option, $options ) ) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
    <textarea cols=100 rows=6 id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]'><?php echo esc_textarea( $value ); ?></textarea><br><?php echo $description ?>
    <?php

  }

  function discourse_validate_options( $input ) {
    $input['url'] = untrailingslashit( $input['url'] );
    return $input;
  }

  function discourse_admin_menu(){
    add_options_page( 'Discourse', 'Discourse', 'manage_options', 'discourse', array ( $this, 'discourse_options_page' ) );
  }

  function discourse_options_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    ?>
    <div class="wrap">
        <h2>Discourse Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'discourse' ); ?>
            <?php do_settings_sections( 'discourse' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
  }
}

$discourse = new Discourse();
