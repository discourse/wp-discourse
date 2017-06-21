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
	 * @access protected
	 * @var array
	 */
	protected $discourse_connect = array(
		'url'              => '',
		'api-key'          => '',
		'publish-username' => 'system',
		'use-discourse-webhook'      => 0,
		'webhook-secret' => '',
		'multisite-configuration' => 0,
	);

	/**
	 * The publishing options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_publish = array(
		'display-subcategories'   => 0,
		'publish-category'        => '',
		'publish-category-update' => 0,
		'full-post-content'       => 0,
		'custom-excerpt-length'   => 55,
		'auto-publish'            => 0,
		'publish-failure-notice'  => 0,
		'publish-failure-email'   => '',
		'auto-track'              => 1,
		'hide-discourse-name-field' => 0,
		'username-as-discourse-name' => 0,
		'allowed_post_types'      => array( 'post' ),
	);

	/**
	 * The commenting options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_comment = array(
		'use-discourse-comments'    => 0,
		'comment-sync-period'       => 10,
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
	 * @access protected
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
	 * The sso_common options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_sso_common = array(
		'sso-secret' => '',
	);

	/**
	 * The sso_provider options.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_sso_provider = array(
		'enable-sso'                   => 0,
		'auto-create-sso-user'         => 0,
		'auto-create-login-redirect'   => '',
		'auto-create-welcome-redirect' => '',
		'login-path'                   => '',
		'real-name-as-discourse-name'  => 0,
		'force-avatar-update'          => 0,
		'redirect-without-login'       => 0,
	);

	/**
	 * The sso_client options.
	 *
	 * @var array
	 */
	protected $discourse_sso_client = array(
		'sso-client-enabled'           => 0,
		'sso-client-login-form-change' => 0,
		'sso-client-sync-by-email'     => 0,
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
		'discourse_sso_common',
		'discourse_sso_provider',
		'discourse_sso_client',
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_discourse_redirect' ) );
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_time_tag' ) );
	}

	/**
	 * Initializes the plugin configuration, loads the text domain etc.
	 */
	public function initialize_plugin() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		// Set the Discourse domain name option.
		$connection_options = get_option( 'discourse_connect' );
		if ( $connection_options && ! empty( $connection_options['url'] ) ) {

			$discourse_url = $connection_options['url'];
		} else {

			$discourse_url = null;
		}

		$domain_name = wp_parse_url( $discourse_url, PHP_URL_HOST );
		update_option( 'wpdc_discourse_domain', $domain_name );
		update_option( 'discourse_option_groups', $this->discourse_option_groups );

		// The 'discourse_sso' option has been moved into three separate arrays. If the plugin is being updated
		// from a previous version, transfer the 'discourse_sso' options into the new arrays.
		if ( get_option( 'discourse_sso' ) ) {
			$this->transfer_options( 'discourse_sso', array(
				'discourse_sso_common',
				'discourse_sso_provider',
				'discourse_sso_client',
			) );
			delete_option( 'discourse_sso' );
		}

		foreach ( $this->discourse_option_groups as $group_name ) {
			if ( 'discourse_configurable_text' === $group_name && get_option( 'discourse_configurable_text' ) ) {
				$saved_values   = get_option( 'discourse_configurable_text' );
				$default_values = $this->discourse_configurable_text;
				$merged_values  = array_merge( $default_values, $saved_values );
				update_option( $group_name, $merged_values );
			} else {
				add_option( $group_name, $this->$group_name );
			}
		}

		// Create a backup for the discourse_configurable_text option.
		update_option( 'discourse_configurable_text_backup', $this->discourse_configurable_text );
		update_option( 'discourse_version', WPDISCOURSE_VERSION );
	}

	/**
	 * Adds the Discourse forum domain name to the allowed hosts for wp_safe_redirect().
	 *
	 * @param array $hosts The array of allowed hosts.
	 *
	 * @return array
	 */
	public function allow_discourse_redirect( $hosts ) {
		$discourse_domain = get_option( 'wpdc_discourse_domain' );

		if ( $discourse_domain ) {
			$hosts[] = $discourse_domain;
		}

		return $hosts;
	}

	/**
	 * Allow the time tag - used in Discourse comments.
	 *
	 * @param array $allowedposttags The array of allowed html tags.
	 *
	 * @return array
	 */
	public function allow_time_tag( $allowedposttags ) {
		$allowedposttags['time'] = array(
			'datetime' => array(),
		);

		return $allowedposttags;
	}

	/**
	 * Used to transfer data from the 'discourse' options array to the new option_group arrays.
	 *
	 * @param string $old_option The name of the old option_group.
	 * @param array $transferable_option_groups The array of transferable_option_group names.
	 */
	protected function transfer_options( $old_option, $transferable_option_groups ) {
		$discourse_options = get_option( $old_option );

		foreach ( $transferable_option_groups as $group_name ) {
			$this->transfer_option_group( $discourse_options, $group_name );
		}
	}

	/**
	 * Transfers saved option values to the new options group.
	 *
	 * @param array $existing_options The old 'discourse' options array.
	 * @param string $group_name The name of the current options group.
	 */
	protected function transfer_option_group( $existing_options, $group_name ) {
		$transferred_options = array();

		foreach ( $this->$group_name as $key => $value ) {
			if ( isset( $existing_options[ $key ] ) ) {
				$transferred_options[ $key ] = $existing_options[ $key ];
			}
		}

		add_option( $group_name, $transferred_options );
	}
}
