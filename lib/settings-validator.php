<?php
namespace WPDiscourse\Validator;

/*
 * Validation methods for the settings page.
 *
 * The methods are invoked by the call to `apply_filters( $filter, $input );`
 * in `DiscourseAdmin#discourse_validate_options`
 */

class SettingsValidator {

  protected $sso_enabled = false;
  protected $use_discourse_comments = false;

  public function __construct() {
    add_filter( 'validate_url', array( $this, 'validate_url' ) );
    add_filter( 'validate_api_key', array( $this, 'validate_api_key' ) );
    add_filter( 'validate_publish_username', array(
      $this,
      'validate_publish_username'
    ) );
    add_filter( 'validate_publish_category', array(
      $this,
      'validate_publish_category'
    ) );
    add_filter( 'validate_publish_category_update', array(
      $this,
      'validate_publish_category_update'
    ) );
    add_filter( 'validate_publish_format', array(
      $this,
      'validate_publish_format'
    ) );
    add_filter( 'validate_full_post_content', array(
      $this,
      'validate_full_post_content'
    ) );
    add_filter( 'validate_auto_publish', array(
      $this,
      'validate_auto_publish'
    ) );
    add_filter( 'validate_auto_track', array( $this, 'validate_auto_track' ) );
    add_filter( 'validate_allowed_post_types', array(
      $this,
      'validate_allowed_post_types'
    ) );
    add_filter( 'validate_use_discourse_comments', array(
      $this,
      'validate_use_discourse_comments'
    ) );
    add_filter( 'validate_show_existing_comments', array(
      $this,
      'validate_show_existing_comments'
    ) );
    add_filter( 'validate_existing_comments_heading', array(
      $this,
      'validate_existing_comments_heading'
    ) );
    add_filter( 'validate_max_comments', array(
      $this,
      'validate_max_comments'
    ) );
    add_filter( 'validate_min_replies', array(
      $this,
      'validate_min_replies'
    ) );
    add_filter( 'validate_min_score', array( $this, 'validate_min_score' ) );
    add_filter( 'validate_min_trust_level', array(
      $this,
      'validate_min_trust_level'
    ) );
    add_filter( 'validate_bypass_trust_level_score', array(
      $this,
      'validate_bypass_trust_level_score'
    ) );
    add_filter( 'validate_custom_excerpt_length', array(
      $this,
      'validate_custom_excerpt_length'
    ) );
    add_filter( 'validate_custom_datetime_format', array(
      $this,
      'validate_custom_datetime_format'
    ) );
    add_filter( 'validate_only_show_moderator_liked', array(
      $this,
      'validate_only_show_moderator_liked'
    ) );
    add_filter( 'validate_replies_html', array(
      $this,
      'validate_replies_html'
    ) );
    add_filter( 'validate_no_replies_html', array(
      $this,
      'validate_no_replies_html'
    ) );
    add_filter( 'validate_comment_html', array(
      $this,
      'validate_comment_html'
    ) );
    add_filter( 'validate_participant_html', array(
      $this,
      'validate_participant_html'
    ) );
    add_filter( 'validate_debug_mode', array( $this, 'validate_debug_mode' ) );
    add_filter( 'validate_enable_sso', array( $this, 'validate_enable_sso' ) );
    add_filter( 'validate_sso_secret', array( $this, 'validate_sso_secret' ) );
    add_filter( 'validate_login_path', array( $this, 'validate_login_path' ) );
  }

  public function validate_url( $input ) {
    $escaped_url = esc_url_raw( $input );

    // FIlTER_VALIDATE_URL doesn't check the protocol / esc_url_raw returns an empty string
    // unless the protocol is in the default protocol array.
    if ( filter_var( $input, FILTER_VALIDATE_URL ) && $escaped_url ) {
      return untrailingslashit( $escaped_url );
    } else {
      add_settings_error( 'discourse', 'discourse_url', __( 'The Discourse URL you provided is not a valid URL.', 'wp-discourse' ) );

      return esc_url_raw( $escaped_url );
    }
  }

  public function validate_api_key( $input ) {
    $regex = '/^\s*([0-9]*[a-z]*|[a-z]*[0-9]*)*\s*$/';

    if ( empty( $input ) ) {
      add_settings_error( 'discourse', 'api_key', __( 'You must provide an API key.', 'wp-discourse' ) );

      return '';

    } elseif ( preg_match( $regex, $input ) ) {
      return trim( $input );

    } else {
      add_settings_error( 'discourse', 'api_key', __( 'The API key you provided is not valid.', 'wp-discourse' ) );

      return $this->sanitize_text( $input );
    }
  }

  public function validate_publish_username( $input ) {
    if ( ! empty( $input ) ) {
      return $this->sanitize_text( $input );
    } else {
      add_settings_error( 'discourse', 'publish_username', __( 'You must provide a Discourse username with which to publish the posts', 'wp-discourse' ) );

      return '';
    }
  }

  public function validate_publish_category( $input ) {
    return sanitize_text_field( $input );
  }

  public function validate_publish_category_update( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_publish_format( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_full_post_content( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_auto_publish( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_auto_track( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_allowed_post_types( $input ) {
    $output = array();
    foreach ( $input as $post_type ) {
      $output[] = sanitize_text_field( $post_type );
    }

    return $output;
  }

  // This is only called if the checkbox is 'checked'.
  public function validate_use_discourse_comments( $input ) {
    $this->use_discourse_comments = true;

    return $this->sanitize_checkbox( $input );
  }

  public function validate_show_existing_comments( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_existing_comments_heading( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_max_comments( $input ) {
    return $this->validate_int( $input, 'max_comments', 1, null,
      __( 'The max visible comments setting requires a positive integer.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }

  public function validate_min_replies( $input ) {
    return $this->validate_int( $input, 'min_replies', 0, null,
      __( 'The min number of replies setting requires a number greater than or equal to 0.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }

  public function validate_min_score( $input ) {
    return $this->validate_int( $input, 'min_score', 0, null,
      __( 'The min score of posts setting requires a number greater than or equal to 0.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }


  public function validate_min_trust_level( $input ) {
    return $this->validate_int( $input, 'min_trust_level', 0, 5,
      __( 'The trust level setting requires a number between 0 and 5.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }

  public function validate_bypass_trust_level_score( $input ) {
    return $this->validate_int( $input, 'bypass_trust_level', 0, null,
      __( 'The bypass trust level score setting requires an integer greater than or equal to 0.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }

  public function validate_custom_excerpt_length( $input ) {
    return $this->validate_int( $input, 'excerpt_length', 1, null,
      __( 'The custom excerpt length setting requires a positive integer.', 'wp-discourse' ),
      $this->use_discourse_comments );
  }

  // Tricky to validate. We could show the user an example of what their format translates into.
  public function validate_custom_datetime_format( $input ) {
    return sanitize_text_field( $input );
  }

  public function validate_only_show_moderator_liked( $input ) {
    return $this->sanitize_checkbox( $input );
  }

  public function validate_replies_html( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_no_replies_html( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_comment_html( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_participant_html( $input ) {
    return $this->sanitize_html( $input );
  }

  public function validate_debug_mode( $input ) {
    return $this->sanitize_html( $input );
  }

  // This is only called if the checkbox is 'checked'.
  public function validate_enable_sso( $input ) {
    $this->sso_enabled = true;

    return $this->sanitize_checkbox( $input );
  }

  public function validate_sso_secret( $input ) {
    if ( strlen( sanitize_text_field( $input) ) >= 10 ) {
      return sanitize_text_field( $input );

      // Only add a settings error if sso is enabled, otherwise just sanitize the input.
    } elseif ( $this->sso_enabled ) {
      add_settings_error( 'discourse', 'sso_secret', __( 'The SSO secret key setting must be at least 10 characters long.', 'wp-discourse' ) );

      return sanitize_text_field( $input );

    } else {
      return sanitize_text_field( $input );
    }
  }

  public function validate_login_path( $input ) {
    if ( $this->sso_enabled && $input ) {
      
      $regex = '/^\/([a-z0-9\-]+)*(\/[a-z0-9\-]+)*(\/)?$/';
      if ( ! preg_match( $regex, $input ) ) {
        add_settings_error( 'discourse', 'login_path', __( 'The path to login page setting needs to be a valid file path, starting with \'/\'.', 'wp-discourse' ) );
        return $this->sanitize_text( $input );
        
      }
      // It's valid
      return $this->sanitize_text( $input );
    }
    // Sanitize, but don't validate. SSO is not enabled.
    return $this->sanitize_text( $input );
  }

  // Helper methods

  protected function sanitize_text( $input ) {
    return sanitize_text_field( $input );
  }

  protected function sanitize_checkbox( $input ) {
    return $input == 1 ? 1 : 0;
  }

  protected function sanitize_html( $input ) {
    return wp_kses_post( $input );
  }

  protected function validate_int( $input, $option_id, $min = null, $max = null, $error_message = '', $add_error = 1 ) {
    $options = array();

    if ( isset( $min ) ) {
      $options['min_range'] = $min;
    }
    if ( isset( $max ) ) {
      $options['max_range'] = $max;
    }

    if ( filter_var( $input, FILTER_VALIDATE_INT, array( 'options' => $options ) ) === false ) {
      if ( $add_error ) {
        add_settings_error( 'discourse', $option_id, $error_message );

        return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
      }

      // The input is not valid, but the setting's section is not being used, sanitize the input and return it.
      return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
    } else {
      // Valid input
      return filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
    }
  }
}