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

	protected $discourse_sso_common = array(
		'sso-secret' => '',
	);

	protected $discourse_sso_provider = array(
		'enable-sso'                   => 0,
		'auto-create-sso-user'         => 0,
		'auto-create-login-redirect'   => '',
		'auto-create-welcome-redirect' => '',
		'login-path'                   => '',
		'redirect-without-login' => 0,
	);

	/**
	 * The SSO options array.
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
		add_action( 'admin_init', array( $this, 'set_plugin_options' ) );
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1 );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_discourse_redirect' ) );
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_time_tag' ) );
	}

	/**
	 * Initializes the plugin configuration, loads the text domain etc.
	 */
	public function initialize_plugin() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		// Set the Discourse domain name option.
		$discourse_url = ! empty( get_option( 'discourse_connect' )['url'] ) ? get_option( 'discourse_connect' )['url'] : null;
		$domain_name   = wp_parse_url( $discourse_url, PHP_URL_HOST );
		update_option( 'wpdc_discourse_domain', $domain_name );
		update_option( 'discourse_option_groups', $this->discourse_option_groups );

	}

	/**
	 * Sets the plugin options on activation.
	 *
	 * Merges the default values of the 'configurable_text_options' with the saved values.
	 *
	 * The code in this function will only run once - while the option 'wpdc_plugin_activated' is set.
	 * The 'wpdc_plugin_activated' option is set in the plugins activation hook function.
	 *
	 * See: https://codex.wordpress.org/Function_Reference/register_activation_hook under the 'Process Flow' heading.
	 */
	public function set_plugin_options() {
		if ( is_admin() && 'wpdc-activated' === get_option( 'wpdc_plugin_activated' ) ) {
			delete_option( 'wpdc_plugin_activated' );

			update_option( 'discourse_option_groups', $this->discourse_option_groups );
			update_option( 'discourse_version', WPDISCOURSE_VERSION );

			if ( get_option( 'discourse_sso' ) ) {
				$this->transfer_options( 'discourse_sso', array(
					'discourse_sso_common',
					'discourse_sso_provider',
					'discourse_sso_client',
				) );
				delete_option( 'discourse_sso' );
			}

			foreach ( $this->discourse_option_groups as $group_name ) {
				$option_defaults = $this->$group_name;
				$saved_option    = get_option( $group_name );
				if ( $saved_option ) {
					// For now, only the configurable_text_options are being merged. In the future it will
					// be possible to merge the values of all option groups. Previously, unset checkboxes weren't
					// being set, so merging option groups that contain checkboxes could end up changing a site's settings.
					$option = 'discourse_configurable_text' === $group_name ? array_merge( $option_defaults, $saved_option ) : $saved_option;
				} else {
					$option = $option_defaults;
				}

				update_option( $group_name, $option );
			}
		}

		// Create a backup for the discourse_configurable_text option.
		update_option( 'discourse_configurable_text_backup', $this->discourse_configurable_text );

		// Set the Discourse domain name option.
		$discourse_url = ! empty( get_option( 'discourse_connect' )['url'] ) ? get_option( 'discourse_connect' )['url'] : null;
		$domain_name   = wp_parse_url( $discourse_url, PHP_URL_HOST );
		update_option( 'wpdc_discourse_domain', $domain_name );
	}

	/**
	 * Adds the Discourse forum domain name to the allowed hosts for wp_safe_redirect().
	 *
	 * @param array $hosts The array of allowed hosts.
	 *
	 * @return array
	 */
	public function allow_discourse_redirect( $hosts ) {
		$discourse_domain = get_option( 'wpdc_discourse_domain', true );

		if ( $discourse_domain ) {
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
	public function extend_user_profile( $fields ) {
		$fields['discourse_username'] = 'Discourse Username';

		return $fields;
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
