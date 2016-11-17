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
		'display-subcategories' => 0,
		'publish-category'      => '',
		'publish-category-update' => 0,
		'full-post-content' => 0,
		'custom-excerpt-length' => 55,
		'auto-publish'          => 0,
		'auto-track'            => 1,
		'allowed_post_types'    => array( 'post' ),
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
		'custom-datetime-format' => '',
		'only-show-moderator-liked' => 0,
		'debug-mode'                => 0,
	);

	/**
	 * The configurable text options array.
	 *
	 * @var array
	 */
	protected $discourse_configurable_text = array(
		'discourse-link-text' => '',
		'start-discussion-text'       => 'Start the discussion at',
		'continue-discussion-text'    => 'Continue the discussion',
		'notable-replies-text'        => 'Notable Replies',
		'comments-not-available-text' => 'Comments are not currently available for this post.',
		'participants-text'           => 'Participants',
		'published-at-text'           => 'Originally published at:',
		'single-reply-text'           => 'Reply',
		'many-replies-text'           => 'Replies',
		'more-replies-more-text'      => 'more',
	);

	/**
	 * The SSO options array.
	 *
	 * @var array
	 */
	protected $discourse_sso = array(
		'enable-sso' => 0,
		'sso-secret' => '',
		'login-path' => '',
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
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ), 10, 1 );
	}

	/**
	 * Initializes the plugin configuration, loads the text domain etc.
	 */
	public function initialize_plugin_configuration() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );

		add_option( 'discourse_option_groups', $this->discourse_option_groups );

		// If the plugin is being updated from a version < 1.0.0 the way options are stored has changed.
		if ( get_option( 'discourse' ) ) {
			// Transfer the old options into the new option groups.
			$this->transfer_options();
			delete_option( 'discourse' );
		}

		foreach ( $this->discourse_option_groups as $group_name ) {
			add_option( $group_name, $this->$group_name );
		}

		// Create a backup for the discourse_configurable_text option, use 'update' in case more text is added.
		update_option( 'discourse_configurable_text_backup', $this->discourse_configurable_text );
	}

	/**
	 * Used to transfer data from the 'discourse' options array to the new option_group arrays.
	 */
	protected function transfer_options() {
		$discourse_options = get_option( 'discourse' );
		$transferable_option_groups     = array(
			'discourse_connect',
			'discourse_publish',
			'discourse_comment',
			'discourse_sso',
		);

		foreach ( $transferable_option_groups as $group_name ) {
			$this->transfer_option_group( $discourse_options, $group_name );
		}
	}

	/**
	 * Transfers saved option values to the new options group.
	 *
	 * @param array  $existing_options The old 'discourse' options array.
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

	/**
	 * Adds the options 'discourse' and 'discourse_version'.
	 *
	 * Called with `register_activation_hook` from `wp-discourse.php`.
	 */
	public static function install() {
		global $wp_version;
		$flags = array();

		// Halt activation if requirements aren't met.
		if ( version_compare( PHP_VERSION, MIN_PHP_VERSION, '<' ) ) {
			$flags['php_version'] = 'The WP Discourse plugin requires at least PHP version ' . MIN_PHP_VERSION . '.';
		}

		if ( version_compare( $wp_version, MIN_WP_VERSION, '<' ) ) {
			$flags['wordpress_version'] = 'The WP Discourse plugin requires at least WordPress version ' . MIN_WP_VERSION . '.';
		}

		if ( ! empty( $flags ) ) {
			$message = '';
			foreach ( $flags as $flag ) {
				$message .= '<p><strong>' . $flag . '</strong></p>';
			}

			deactivate_plugins( deactivate_plugins( plugin_basename( __FILE__ ) ) );
			wp_die( esc_html( $message ), 'Plugin Activation Error', array( 'response' => 200, 'back_link' => true ) );
		}

		update_option( 'discourse_version', WPDISCOURSE_VERSION );
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
