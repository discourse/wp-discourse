<?php
/**
 * SSO Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class SSOSettings
 */
class SSOSettings {

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * SSOSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_sso_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_sso_settings() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_sso_settings_section', __( 'SSO Settings', 'wp-discourse' ), array(
			$this,
			'sso_settings_tab_details',
		), 'discourse_sso' );

		add_settings_field( 'discourse_enable_sso', __( 'Enable SSO Provider', 'wp-discourse' ), array(
			$this,
			'enable_sso_provider_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array(
			$this,
			'wordpress_login_path',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_sync_avatars', __( 'Send avatar URL to Discourse', 'wp-discourse' ), array(
			$this,
			'sync_avatars_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_enable_discourse_sso', __( 'Enable SSO Client', 'wp-discourse' ), array(
			$this,
			'enable_sso_client_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'enable_discourse_sso_login_form_change', __( 'Add "Login with Discourse" on the login form', 'wp-discourse' ), array(
			$this,
			'enable_discourse_sso_login_form_change_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array(
			$this,
			'sso_secret_input',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_redirect_without_login', __( 'Redirect Without Login', 'wp-discourse' ), array(
			$this,
			'redirect_without_login_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		register_setting( 'discourse_sso', 'discourse_sso', array(
			$this->form_helper,
			'validate_options',
		) );

	}

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_provider_checkbox() {
		$description = __( 'Use this WordPress instance as the SSO provider for your Discourse forum. 
		To use this functionality, you must fill SSO Secret key field.', 'wp-discourse' );
		$this->form_helper->checkbox_input( 'enable-sso', 'discourse_sso', __( 'Enable SSO provider.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the sync-avatars checkbox.
	 */
	public function sync_avatars_checkbox() {
	    $description = __( 'Sends the WordPress avatar URL to Discourse so that it can be used in place of the Discourse avatar.', 'wp-discourse' );
	    $this->form_helper->checkbox_input( 'sync-avatars', 'discourse_sso', __( 'Send avatar URL.', 'wp-discourse' ), $description );
    }

	/**
	 * Outputs markup for sso-client-enabled checkbox.
	 */
	public function enable_sso_client_checkbox() {
		$description = __( 'Use your Discourse instance as an SSO provider for your WordPress site.
		To use this functionality, you must fill SSO Secret key field. (Currently, not working with multisite installations.)', 'wp-discourse' );
		$this->form_helper->checkbox_input( 'sso-client-enabled', 'discourse_sso', __( 'Enable SSO client.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for sso-client-login-form-change
	 */
	public function enable_discourse_sso_login_form_change_checkbox() {
		$this->form_helper->checkbox_input( 'sso-client-login-form-change', 'discourse_sso', __( 'When using your site as as an SSO client for Discourse, 
		this setting will add a "Login with Discourse" link to your WordPress login form.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	public function wordpress_login_path() {
		$this->form_helper->input( 'login-path', 'discourse_sso', __( '(Optional) When using WordPress as the SSO provider, you can set the path to your login page here. 
		It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		$options = $this->options;
		if ( isset( $options['url'] ) && ! empty( $options['url'] ) ) {
			$this->form_helper->input( 'sso-secret', 'discourse_sso', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $options['url'] ) . '/admin/site_settings/category/login" target="_blank">' . esc_url( $options['url'] ) . '/admin/site_settings/category/login</a>' );
		} else {
			$this->form_helper->input( 'sso-secret', 'discourse_connect', __( 'Found at http://discourse.example.com/admin/site_settings/category/login', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	public function redirect_without_login_checkbox() {
		$this->form_helper->checkbox_input( 'redirect-without-login', 'discourse_sso', __( 'Do not force login for link to Discourse comments thread.' ) );
	}

	/**
	 * Details for the 'sso_options' tab.
	 */
	function sso_settings_tab_details() {
		?>
        <p class="documentation-link">
            <em><?php esc_html_e( 'This section is for configuring WordPress as either the Single Sign On provider, 
            or a Single Sign On client, for your Discourse forum. Unless you have a need to manage your forum\'s users
            through your WordPress site, or to log users into your WordPress site through Discourse, you can leave this setting alone. 
            For more information, see the ', 'wp-discourse' ); ?></em>
            <a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
            <em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
        </p>
		<?php
	}
}
