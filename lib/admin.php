<?php
/**
 * WP-Discourse admin settings
 */

class DiscourseAdmin {
  protected $options;

  public function __construct() {
    $this->options = get_option( 'discourse' );

    add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'admin_menu', array( $this, 'discourse_admin_menu' ) );
    add_action( 'load-settings_page_discourse', array( $this, 'connection_status_notice' ) );
  }

  /**
   * Settings
   */
  public function admin_init() {
    register_setting( 'discourse', 'discourse', array( $this, 'discourse_validate_options' ) );
    add_settings_section( 'discourse_wp_api', __( 'Common Settings', 'wp-discourse' ), array( $this, 'init_default_settings' ), 'discourse' );

    add_settings_section( 'discourse_wp_publish', __( 'Publishing Settings', 'wp-discourse' ), array( $this, 'init_default_settings' ), 'discourse' );
    add_settings_section( 'discourse_comments', __( 'Comments Settings', 'wp-discourse' ), array( $this, 'init_comment_settings' ), 'discourse' );
    add_settings_section( 'discourse_wp_sso', __( 'SSO Settings', 'wp-discourse' ), array( $this, 'init_default_settings' ), 'discourse' );

    add_settings_field( 'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array( $this, 'url_input' ), 'discourse', 'discourse_wp_api' );
    add_settings_field( 'discourse_api_key', __( 'API Key', 'wp-discourse' ), array( $this, 'api_key_input' ), 'discourse', 'discourse_wp_api' );
    add_settings_field( 'discourse_publish_username', __( 'Publishing username', 'wp-discourse' ), array( $this, 'publish_username_input' ), 'discourse', 'discourse_wp_api' );

    add_settings_field( 'discourse_enable_sso', __( 'Enable SSO', 'wp-discourse' ), array( $this, 'enable_sso_checkbox' ), 'discourse', 'discourse_wp_sso' );
    add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array( $this, 'wordpress_login_path' ), 'discourse', 'discourse_wp_sso' );
    add_settings_field( 'discourse_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array( $this, 'sso_secret_input' ), 'discourse', 'discourse_wp_sso' );

    add_settings_field( 'discourse_display_subcategories', __( 'Display subcategories', 'wp-discourse' ), array( $this, 'display_subcategories' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_publish_category', __( 'Published category', 'wp-discourse' ), array( $this, 'publish_category_input' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_publish_category_update', __( 'Force category update', 'wp-discourse' ), array( $this, 'publish_category_input_update' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_full_post_content', __( 'Use full post content', 'wp-discourse' ), array( $this, 'full_post_checkbox' ), 'discourse', 'discourse_wp_publish' );

    add_settings_field( 'discourse_auto_publish', __( 'Auto Publish', 'wp-discourse' ), array( $this, 'auto_publish_checkbox' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_auto_track', __( 'Auto Track Published Topics', 'wp-discourse' ), array( $this, 'auto_track_checkbox' ), 'discourse', 'discourse_wp_publish' );
    add_settings_field( 'discourse_allowed_post_types', __( 'Post Types to publish to Discourse', 'wp-discourse' ), array( $this, 'post_types_select' ), 'discourse', 'discourse_wp_publish' );

    add_settings_field( 'discourse_use_discourse_comments', __( 'Use Discourse Comments', 'wp-discourse' ), array( $this, 'use_discourse_comments_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_show_existing_comments', __( 'Show Existing WP Comments', 'wp-discourse' ), array( $this, 'show_existing_comments_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_existing_comments_heading', __( 'Existing Comments Heading', 'wp-discourse' ), array( $this, 'existing_comments_heading_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_max_comments', __( 'Max visible comments', 'wp-discourse' ), array( $this, 'max_comments_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_replies', __( 'Min number of replies', 'wp-discourse' ), array( $this, 'min_replies_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_score', __( 'Min score of posts', 'wp-discourse' ), array( $this, 'min_score_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_min_trust_level', __( 'Min trust level', 'wp-discourse' ), array( $this, 'min_trust_level_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_bypass_trust_level_score', __( 'Bypass trust level score', 'wp-discourse' ), array( $this, 'bypass_trust_level_input' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_custom_excerpt_length', __( 'Custom excerpt length', 'wp-discourse' ), array( $this, 'custom_excerpt_length' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_custom_datetime_format', __( 'Custom Datetime Format', 'wp-discourse' ), array( $this, 'custom_datetime_format' ), 'discourse', 'discourse_comments' );

    add_settings_field( 'discourse_only_show_moderator_liked', __( 'Only import comments liked by a moderator', 'wp-discourse' ), array( $this, 'only_show_moderator_liked_checkbox' ), 'discourse', 'discourse_comments' );
    add_settings_field( 'discourse_debug_mode', __( 'Debug mode', 'wp-discourse' ), array( $this, 'debug_mode_checkbox' ), 'discourse', 'discourse_comments' );

    add_action( 'post_submitbox_misc_actions', array( $this, 'publish_to_discourse' ) );

    add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1);
  }

  function extend_user_profile( $fields ) {
    $fields['discourse_username'] = 'Discourse Username';
    return $fields;
  }

  function init_default_settings() {
  }

  function init_comment_settings() {
    ?>

    <p class="documentation-link">
      <em><?php _e( 'For documentation on customizing the plugin\'s html, visit ', 'wp-discourse'); ?></em>
      <a href="https://github.com/discourse/wp-discourse/wiki/Template-Customization">https://github.com/discourse/wp-discourse/wiki/Template-Customization</a>
    </p>

    <?php
  }

  function url_input() {
    self::text_input( 'url', __( 'e.g. http://discourse.example.com', 'wp-discourse' ), 'url' );
  }

  function wordpress_login_path() {
    self::text_input( 'login-path', __( '(Optional) The path to your login page. It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
  }

  function api_key_input() {
    $discourse_options = Discourse::get_plugin_options();
    if ( isset( $discourse_options['url'] ) && ! empty( $discourse_options['url'] ) ) {
      self::text_input( 'api-key', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) . '/admin/api" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api</a>' );
    } else {
      self::text_input( 'api-key', __( 'Found at http://discourse.example.com/admin/api', 'wp-discourse' ) );
    }
  }

  function enable_sso_checkbox() {
    self::checkbox_input( 'enable-sso', __( 'Enable SSO to Discourse', 'wp-discourse' ) );
  }

  function sso_secret_input() {
    self::text_input( 'sso-secret', '' );
  }

  function publish_username_input() {
    self::text_input( 'publish-username', __( 'Discourse username of publisher (will be overriden if Discourse Username is specified on user)', 'wp-discourse' ) );
  }

  function display_subcategories() {
    self::checkbox_input( 'display-subcategories', __( 'Include subcategories in the list of available categories.', 'wp-discourse' ) );
  }

  function publish_category_input() {
    self::category_select( 'publish-category', __( 'Default category used to published in Discourse (optional)', 'wp-discourse' ) );
  }

  function publish_category_input_update() {
    self::checkbox_input( 'publish-category-update', __( 'Update the discourse publish category list, (normally set to refresh every hour)', 'wp-discourse' ) );
  }

  function max_comments_input() {
    self::text_input( 'max-comments', __( 'Maximum number of comments to display', 'wp-discourse' ), 'number' );
  }

  function auto_publish_checkbox() {
    self::checkbox_input( 'auto-publish', __( 'Publish all new posts to Discourse', 'wp-discourse' ) );
  }

  function auto_track_checkbox() {
    self::checkbox_input( 'auto-track', __( 'Author automatically tracks published Discourse topics', 'wp-discourse' ) );
  }

  function post_types_select() {
    self::post_type_select_input( 'allowed_post_types',
      $this->post_types_to_publish( array( 'attachment' ) ),
      __( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple options.', 'wp-discourse' ) );
  }

  function use_discourse_comments_checkbox() {
    self::checkbox_input( 'use-discourse-comments', __( 'Use Discourse to comment on Discourse published posts', 'wp-discourse' ) );
  }

  function show_existing_comments_checkbox() {
    self::checkbox_input( 'show-existing-comments', __( 'Display existing WordPress comments beneath Discourse comments', 'wp-discourse' ) );
  }

  function existing_comments_heading_input() {
    self::text_input( 'existing-comments-heading', __( 'Heading for existing WordPress comments (e.g. "Historical Comment Archive")', 'wp-discourse' ) );
  }

  function min_replies_input() {
    self::text_input( 'min-replies', __( 'Minimum replies required prior to pulling comments across', 'wp-discourse' ), 'number', 0 );
  }

  function min_trust_level_input() {
    self::text_input( 'min-trust-level', __( 'Minimum trust level required prior to pulling comments across (0-5)', 'wp-discourse' ), 'number', 0 );
  }

  function min_score_input() {
    self::text_input( 'min-score', __( 'Minimum score required prior to pulling comments across (score = 15 points per like, 5 per reply, 5 per incoming link, 0.2 per read)', 'wp-discourse' ), 'number', 0 );
  }

  function custom_excerpt_length() {
    self::text_input( 'custom-excerpt-length', __( 'Custom excerpt length in words (default: 55)', 'wp-discourse' ), 'number', 0 );
  }

  function custom_datetime_format() {
    self::text_input( 'custom-datetime-format', __( 'Custom comment meta datetime string format (default: "', 'wp-discourse' ) .
                                                get_option('date_format') . '").' .
                                                __( 'See ', 'wp-discourse' ) . '<a href="https://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' .
                                                __( 'this', 'wp-discourse' ) .'</a>' . __( ' for more info.', 'wp-discourse' ) );
  }

  function bypass_trust_level_input() {
    self::text_input( 'bypass-trust-level-score', __( 'Bypass trust level check on posts with this score', 'wp-discourse' ), 'number', 0 );
  }

  function debug_mode_checkbox() {
    self::checkbox_input( 'debug-mode', __( '(always refresh comments)', 'wp-discourse' ) );
  }

  function full_post_checkbox() {
    self::checkbox_input( 'full-post-content', __( 'Use the full post for content rather than an excerpt.', 'wp-discourse' ) );
  }

  function only_show_moderator_liked_checkbox() {
    self::checkbox_input( 'only-show-moderator-liked', __( 'Yes', 'wp-discourse' ) );
  }

  function checkbox_input( $option, $label, $description = '' ) {
    $options = $this->options;
    if (array_key_exists( $option, $options) and $options[$option] == 1) {
      $checked = 'checked="checked"';
    } else {
      $checked = '';
    }

    ?>
    <label>
      <input id='discourse_<?php echo esc_attr( $option ); ?>' name='discourse[<?php echo esc_attr( $option ); ?>]' type='checkbox' value='1' <?php echo $checked; ?> />
      <?php echo esc_html( $label ); ?>
    </label>
    <p class="description"><?php echo esc_html( $description ); ?></p>
    <?php
  }

  function post_type_select_input( $option, $post_types, $description = '' ) {
    $options = $this->options;
    $allowed = array(
      'strong' => array()
    );

    echo "<select multiple id='discourse_allowed_post_types' class='discourse-allowed-types' name='discourse[allowed_post_types][]'>";

    foreach ( $post_types as $post_type ) {

      if ( array_key_exists( $option, $options) and in_array( $post_type, $options[$option] ) ) {
        $value = 'selected';
      } else {
        $value = '';
      }

      echo "<option ".$value." value='" . esc_attr( $post_type ) . "'>" . esc_html( $post_type ) . "</option>";
    }

    echo '</select>';
    echo '<p class="description">'. wp_kses( $description, $allowed ) . '</p>';
  }

  function get_discourse_categories() {
    $options = get_option( 'discourse' );

    $url = add_query_arg( array(
      'api_key' => $options['api-key'],
      'api_username' => $options['publish-username']
    ), $options['url'] . '/site.json' );

    $force_update = isset($options['publish-category-update']) ? $options['publish-category-update'] : '0';
    $remote = get_transient( 'discourse_settings_categories_cache' );
    $cache = $remote;

    if ( empty( $remote ) || $force_update ) {
      $remote = wp_remote_get( $url );

      if ( ! $this->validate_response( $remote ) ) {
        if ( ! empty( $cache ) ) {
          return $cache;

        }
        return new WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
      }

      $remote = json_decode( wp_remote_retrieve_body( $remote ), true );

      if ( array_key_exists( 'categories', $remote ) ) {
        $remote = $remote['categories'];

        if ( ! isset( $options['display-subcategories'] ) ) {
          foreach ( $remote as $category => $values ) {
            if ( array_key_exists( 'parent_category_id', $values ) ) {
              unset( $remote[$category] );
            }
          }
        }

        set_transient( 'discourse_settings_categories_cache', $remote, HOUR_IN_SECONDS );
      } else {
        return new WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
      }
    }

    return $remote;
  }

  function category_select( $option, $description ) {
    $options = get_option( 'discourse' );

    $categories = self::get_discourse_categories();

    // todo: this is being set with the numerical value of the option ('1'). The categories become available
    // when a connection with Discourse is established.
    if( is_wp_error( $categories ) ) {
     self::text_input( $option, $description );
     return;
    }

   $selected = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
   $name = "discourse[$option]";
   self::option_input($name, $categories, $selected);
  }

  function option_input( $name, $group, $selected ) {
    echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';

    foreach( $group as $item ) {
      printf( '<option value="%s"%s>%s</option>',
       esc_attr( $item['id'] ),
       selected( $selected, $item['id'], false ),
       esc_html( $item['name'] )
      );
    }

    echo '</select>';
  }

  function text_input( $option, $description, $type = null, $min = null ) {
    $options = $this->options;
    $allowed = array(
      'a' => array(
        'href' => array(),
        'target' => array()
      )
    );

    if ( array_key_exists( $option, $options ) ) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
    <input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]'
           type="<?php echo isset( $type ) ? $type : 'text'; ?>"
           <?php if ( isset( $min ) ) echo 'min="' . $min . '"'; ?>
           value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr" />
    <p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
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
    <textarea cols=100 rows=6 id='discourse_<?php echo esc_attr( $option ); ?>' name='discourse[<?php echo esc_attr( $option ); ?>]'><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php echo esc_html( $description ); ?></p>
    <?php

  }

  function discourse_validate_options( $inputs ) {
    $output = array();
    foreach ( $inputs as $key => $input ) {
      $filter = 'validate_' . str_replace( '-', '_', $key );
      $output[$key] = apply_filters( $filter, $input );
    }

    return $output;
  }

  function discourse_admin_menu() {
    add_options_page( __( 'Discourse', 'wp-discourse' ), __( 'Discourse', 'wp-discourse' ), 'manage_options', 'discourse', array ( $this, 'discourse_options_page' ) );
  }

  function discourse_options_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-discourse' ) );
    }
    ?>
    <div class="wrap">
      <h2>Discourse Options</h2>
      <p class="documentation-link">
        <em><?php _e( 'The WP Discourse plugin documentation can be found at ', 'wp-discourse'); ?></em>
        <a href="https://github.com/discourse/wp-discourse/wiki">https://github.com/discourse/wp-discourse/wiki</a>
      </p>
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

      $categories = self::get_discourse_categories();
      if( is_wp_error( $categories ) ) {
        echo '<span>' . __ ( 'Unable to retrieve Discourse categories. Please check the wp-discourse plugin settings page to establish a connection.', 'wp-discourse' ) . '</span>';
      }
      else {

        echo '<div class="misc-pub-section misc-pub-section-discourse">';
        echo '<label>'. __( 'Publish to Discourse: ', 'wp-discourse' ) .'</label>';
        echo  '<input type="checkbox"' . (( $value == "1") ? ' checked="checked" ' : null) . 'value="1" name="publish_to_discourse" />';
        echo  '</div>';

        echo '<div class="misc-pub-section misc-pub-section-category">' .
             '<input type="hidden" name="showed_publish_option" value="1">';
        echo '<label>' . __( 'Discourse Category: ', 'wp-discourse' ) . '</label>';

        $publish_post_category = get_post_meta( $post->ID, 'publish_post_category', true);
        $default_category = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
        $selected = (! empty( $publish_post_category ) ) ? $publish_post_category : $default_category;

        self::option_input('publish_post_category', $categories, $selected);
        echo '</div>';
      }
    }
  }

  function connection_status_notice() {
    if ( ! $this->test_api_credentials() ) {
      add_action( 'admin_notices' , array( $this, 'disconnected' ) );
    } else {
      add_action( 'admin_notices', array($this, 'connected' ) );
    }
  }

  function disconnected() {
    ?>
    <div class="notice notice-warning is-dismissible">
      <p>
        <strong><?php _e( "You are not currently connected to a Discourse forum. " .
                          "To establish a connection, check your settings for 'Discourse URL', 'API Key', and 'Publishing username'. " .
                          "Also, make sure that your Discourse forum is online.", 'wp-discourse' ); ?></strong>
      </p>
    </div>
    <?php
  }

  function connected() {
    ?>
    <div class="notice notice-success is-dismissible">
      <p>
        <strong><?php _e( "You are connected to Discourse!", 'wp-discourse' ); ?></strong>
      </p>
    </div>
    <?php
  }

  protected function test_api_credentials() {
    $options = $this->options;
    $url = array_key_exists( 'url', $options ) ? $options['url'] . '/categories.json' : '';

    $url = add_query_arg( array(
      "api_key" => array_key_exists( 'api-key', $options  ) ? $options['api-key'] : '' ,
      "api_username" => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : ''
    ), $url );
    $response = wp_remote_get( $url );

    if ( ! $this->validate_response( $response ) ) {
      return false;
    }
    return true;
  }

  protected function post_types_to_publish( $excluded_types = array() ) {
    $post_types = get_post_types( array( 'public' => true ) );
    foreach ( $excluded_types as $excluded ) {
      unset( $post_types[$excluded] );
    }
    return apply_filters( 'discourse_post_types_to_publish', $post_types );
  }

  protected function validate_response( $response ) {
    if ( is_wp_error( $response ) ) {
      error_log( $response->get_error_message() );
      return 0;

    } elseif ( wp_remote_retrieve_response_code( $response ) != 200 ) {
      $error_message = wp_remote_retrieve_response_message( $response );
      error_log( "There has been a problem accessing your Discourse forum. Error Message: " . $error_message );
      return 0;

    }
    // valid response
    return 1;
  }

}
