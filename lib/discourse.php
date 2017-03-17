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
	 * The connection options array.
	 *
	 * @var array
	 */
	protected $discourse_connect = array(
		'url'              => '',
		'api-key'          => '',
		'publish-username' => 'system',
	);

	/**
	 * The publishing options array.
	 *
	 * @var array
	 */
	protected $discourse_publish = array(
		'display-subcategories'   => 0,
		'publish-category'        => '',
		'publish-category-update' => 0,
		'full-post-content'       => 0,
		'custom-excerpt-length'   => 55,
		'auto-publish'            => 0,
		'auto-track'              => 1,
		'allowed_post_types'      => array( 'post' ),
	);

	/**
	 * The commenting options array.
	 *
	 * @var array
	 */
	protected $discourse_comment = array(
		'use-discourse-comments'    => 0,
		'show-existing-comments'    => 0,
		'existing-comments-heading' => '',
		'max-comments'              => 5,
		'min-replies'               => 1,
		'min-score'                 => 0,
		'min-trust-level'           => 1,
		'bypass-trust-level-score'  => 50,
		'custom-datetime-format'    => '',
		'only-show-moderator-liked' => 0,
		'debug-mode'                => 0,
	);

	/**
	 * The configurable text options array.
	 *
	 * @var array
	 */
	protected $discourse_configurable_text = array(
		'discourse-link-text'         => '',
		'start-discussion-text'       => 'Start the discussion at',
		'continue-discussion-text'    => 'Continue the discussion',
		'notable-replies-text'        => 'Notable Replies',
		'comments-not-available-text' => 'Comments are not currently available for this post.',
		'participants-text'           => 'Participants',
		'published-at-text'           => 'Originally published at:',
		'single-reply-text'           => 'Reply',
		'many-replies-text'           => 'Replies',
		'more-replies-more-text'      => 'more',
		'external-login-text'         => 'Log in with Discourse',
		'link-to-discourse-text'      => 'Link your account to Discourse',
		'linked-to-discourse-text'    => "You're already linked to Discourse!",
	);

	/**
	 * The SSO options array.
	 *
	 * @var array
	 */
	protected $discourse_sso = array(
		'enable-sso'                   => 0,
		'sso-client-enabled'           => 0,
		'sso-client-login-form-change' => 0,
		'sso-client-sync-by-email'     => 0,
		'sso-secret'                   => '',
		'login-path'                   => '',
	);

	/**
	 * The array of option groups, used for assembling the options into a single array.
	 *
	 * @var array
	 */
	protected $discourse_option_groups = array(
		'discourse_connect',
		'discourse_publish',
		'discourse_comment',
		'discourse_configurable_text',
		'discourse_sso',
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin_configuration' ) );
		add_action( 'admin_init', array( $this, 'initialize_plugin_options' ) );
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_discourse_redirect' ) );
	}

	/**
	 * Initializes the plugin configuration, loads the text domain etc.
	 */
	public function initialize_plugin_configuration() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );


		// Set the Discourse domain name option.
		$discourse_url = ! empty( get_option( 'discourse_connect' )['url'] ) ? get_option( 'discourse_connect' )['url'] : null;
		$domain_name   = wp_parse_url( $discourse_url, PHP_URL_HOST );
		update_option( 'wpdc_discourse_domain', $domain_name );
	}

	// See: https://codex.wordpress.org/Function_Reference/register_activation_hook
	public function initialize_plugin_options() {
		if ( is_admin() && 'wpdc-activated' === get_option( 'wpdc_plugin_activated' ) ) {
			delete_option( 'wpdc_plugin_activated' );
			add_option( 'discourse_option_groups', $this->discourse_option_groups );
			update_option( 'discourse_version', WPDISCOURSE_VERSION );

			foreach ( $this->discourse_option_groups as $group_name ) {
				$saved_option    = get_option( $group_name );
				$option_defaults = $this->$group_name;
				$option          = $saved_option ? array_merge( $option_defaults, $saved_option ) : $option_defaults;
				update_option( $group_name, $option );
			}
		}

		// Create a backup for the discourse_configurable_text option.
		update_option( 'discourse_configurable_text_backup', $this->discourse_configurable_text );
	}

	/**
	 * Adds the Discourse forum domain name to the allowed hosts for wp_safe_redirect().
	 *
	 * @param array $hosts The array of allowed hosts.
	 *
	 * @return array
	 */
	public function allow_discourse_redirect( $hosts ) {
		if ( $discourse_domain = get_option( 'wpdc_discourse_domain', true ) ) {
			$hosts[] = $discourse_domain;
		}

		return $hosts;
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
