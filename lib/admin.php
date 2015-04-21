<?php
/**
 * WP-Discourse admin settings
 */
require_once('discourse.php');

class DiscourseAdmin {
  protected $options;

  public function __construct() {
    $this->options = get_option( 'discourse' );

    add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'admin_menu', array( $this, 'discourse_admin_menu' ) );
  }

  /**
   * Settings
   */
  public function admin_init() {
    register_setting( 'discourse', 'discourse', array( $this, 'discourse_validate_options' ) );
    add_settings_section( 'discourse_wp_api', 'Common Settings', array( $this, 'init_default_settings' ), 'discourse' );

    add_settings_section( 'discourse_wp_publish', 'Publishing Settings', array( $this, 'init_default_settings' ), 'discourse' );
    add_settings_section( 'discourse_comments', 'Comments Settings', array( $this, 'init_default_settings' ), 'discourse' );
    add_settings_section( 'discourse_wp_sso', 'SSO Settings', array( $this, 'init_default_settings' ), 'discourse' );

    add_settings_field( 'discourse_url', 'Discourse URL', array( $this, 'url_input' ), 'discourse', 'discourse_wp_api' );
    add_settings_field( 'discourse_api_key', 'API Key', array( $this, 'api_key_input' ), 'discourse', 'discourse_wp_api' );
    add_settings_field( 'discourse_publish_username', 'Publishing username', array( $this, 'publish_username_input' ), 'discourse', 'discourse_wp_api' );

    add_settings_field( 'discourse_enable_sso', 'Enable SSO', array( $this, 'enable_sso_checkbox' ), 'discourse', 'discourse_wp_sso' );
    add_settings_field( 'discourse_sso_secret', 'SSO Secret Key', array( $this, 'sso_secret_input' ), 'discourse', 'discourse_wp_sso' );

    add_settings_field( 'discourse_publish_category', 'Published category', array( $this, 'publish_category_input' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_publish_format', 'Publish format', array( $this, 'publish_format_textarea' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_full_post_content', 'Use full post content', array( $this, 'full_post_checkbox' ), 'discourse', 'discourse_wp_publish' );

    add_settings_field( 'discourse_auto_publish', 'Auto Publish', array( $this, 'auto_publish_checkbox' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_auto_track', 'Auto Track Published Topics', array( $this, 'auto_track_checkbox' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_allowed_post_types', 'Post Types to publish to Discourse', array( $this, 'post_types_select' ), 'discourse', 'discourse_wp_publish' );

    add_settings_field( 'discourse_use_discourse_comments', 'Use Discourse Comments', array( $this, 'use_discourse_comments_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_show_existing_comments', 'Show Existing WP Comments', array( $this, 'show_existing_comments_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_existing_comments_heading', 'Existing Comments Heading', array( $this, 'existing_comments_heading_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_max_comments', 'Max visible comments', array( $this, 'max_comments_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_replies', 'Min number of replies', array( $this, 'min_replies_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_score', 'Min score of posts', array( $this, 'min_score_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_trust_level', 'Min trust level', array( $this, 'min_trust_level_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_bypass_trust_level_score', 'Bypass trust level score', array( $this, 'bypass_trust_level_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_custom_excerpt_length', 'Custom excerpt length', array( $this, 'custom_excerpt_length' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_custom_datetime_format', 'Custom Datetime Format', array( $this, 'custom_datetime_format' ), 'discourse', 'discourse_comments' );

    add_settings_field( 'discourse_only_show_moderator_liked', 'Only import comments liked by a moderator', array( $this, 'only_show_moderator_liked_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_template_replies', 'HTML Template to use when there are replies', array( $this, 'template_replies_html' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_template_no_replies', 'HTML Template to use when there are no replies', array( $this, 'template_no_replies_html' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_template_comment', 'HTML Template to use for each comment', array( $this, 'template_comment_html' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_participant_comment', 'HTML Template to use for each participant', array( $this, 'template_participant_html' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_debug_mode', 'Debug mode', array( $this, 'debug_mode_checkbox' ), 'discourse', 'discourse_comments' );

    add_action( 'post_submitbox_misc_actions', array( $this, 'publish_to_discourse' ) );

    add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1);
  }

  function extend_user_profile( $fields ) {
    $fields['discourse_username'] = 'Discourse Username';
    return $fields;
  }

  function init_default_settings() {

  }

  function url_input() {
    self::text_input( 'url', '' );
  }

  function api_key_input() {
    self::text_input( 'api-key', '' );
  }

  function enable_sso_checkbox() {
    self::checkbox_input( 'enable-sso', 'Enable SSO to Discourse' );
  }

  function sso_secret_input() {
    self::text_input( 'sso-secret', '' );
  }

  function publish_username_input() {
    self::text_input( 'publish-username', 'Discourse username of publisher (will be overriden if Discourse Username is specified on user)' );
  }

  function publish_category_input() {
    self::category_select( 'publish-category', 'Category post will be published in Discourse (optional)' );
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
    self::checkbox_input( 'use-discourse-comments', 'Use Discourse to comment on Discourse published posts' );
  }

  function show_existing_comments_checkbox() {
    self::checkbox_input( 'show-existing-comments', 'Display existing WordPress comments beneath Discourse comments' );
  }

  function existing_comments_heading_input() {
    self::text_input( 'existing-comments-heading', 'Heading for existing WordPress comments (e.g. "Historical Comment Archive")' );
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

  function custom_datetime_format(){
    self::text_input( 'custom-datetime-format', 'Custom comment meta datetime string format (default: "' . get_option('date_format') . '"). See <a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">this</a> for more info.' );
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
    self::text_area( 'comment-html', 'HTML template to use for each comment<br/>Available tags: <small>{discourse_url}, {discourse_url_name}, {topic_url}, {avatar_url}, {user_url}, {username}, {fullname}, {comment_body}, {comment_created_at}</small>' );
  }

  function template_participant_html() {
    self::text_area( 'participant-html', 'HTML template to use for each participant<br/>Available tags: <small>{discourse_url}, {discourse_url_name}, {topic_url}, {avatar_url}, {user_url}, {username}, {fullname}</small>' );
  }

  function checkbox_input( $option, $label, $description = '' ) {
    $options = $this->options;
    if (array_key_exists( $option, $options) and $options[$option] == 1) {
      $value = 'checked="checked"';
    } else {
      $value = '';
    }

    ?>
    <label>
      <input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='checkbox' value='1' <?php echo $value?> />
      <?php echo $label ?>
    </label>
    <p class="description"><?php echo $description ?></p>
    <?php
  }

  function post_type_select_input( $option, $post_types) {
    $options = $this->options;

    echo "<select multiple id='discourse_allowed_post_types' name='discourse[allowed_post_types][]'>";

    foreach ( $post_types as $post_type ) {

      if ( array_key_exists( $option, $options) and in_array( $post_type, $options[$option] ) ) {
        $value = 'selected';
      } else {
        $value = '';
      }

      echo "<option ".$value." value='".$post_type."'>".$post_type."</option>";
    }

    echo '</select>';
  }


  function category_select( $option, $description ) {
    $options = get_option( 'discourse' );
    $url = $options['url'] . '/categories.json';

    $url = add_query_arg( array(
      "api_key" => $options['api-key'] ,
      "api_username" => $options['publish-username']
    ), $url );

    $remote = get_transient( "discourse_settings_categories_cache" );

    if( empty( $remote ) ){
      $remote = wp_remote_get( $url );

      if( is_wp_error( $remote ) ) {
        self::text_input( $option, $description );
        return;
      }

      $remote = wp_remote_retrieve_body( $remote );

      if( is_wp_error( $remote ) ) {
        self::text_input( $option, $description );
        return;
      }

      $remote = json_decode( $remote, true );

      set_transient( "discourse_settings_categories_cache", $remote, HOUR_IN_SECONDS );
    }

    $categories = $remote['category_list']['categories'];
    $selected = isset( $options['publish-category'] ) ? $options['publish-category'] : '';

    echo "<select id='discourse[{$option}]' name='discourse[{$option}]'>";
    echo '<option></option>';

    foreach( $categories as $category ){
      printf( '<option value="%s"%s>%s</option>',
        $category['id'],
        selected( $selected, $category['id'], false ),
        $category['name']
      );
    }

    echo '</select>';
  }

  function text_input( $option, $description ) {
    $options = $this->options;

    if ( array_key_exists( $option, $options ) ) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
    <input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='text' value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr" />
    <p class="description"><?php echo $description ?></p>
    <?php

  }

  function text_area( $option, $description) {
    $options = $this->options;

    if ( array_key_exists( $option, $options ) ) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
    <textarea cols=100 rows=6 id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]'><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php echo $description ?></p>
    <?php

  }

  function discourse_validate_options( $inputs ) {
    foreach ( $inputs as $key => $input ) {
      $inputs[ $key ] = is_string( $input ) ? trim( $input ) : $input;
    }

    $inputs['url'] = untrailingslashit( $inputs['url'] );
    return $inputs;
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

  function publish_to_discourse() {
    global $post;

    $options = Discourse::get_plugin_options();

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
}
