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

		add_settings_field( 'auto_create_sso_user', __( 'Create Discourse User on Login', 'wp-discourse' ), array(
			$this,
			'auto_create_sso_user_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'auto_create_login_redirect', __( 'Redirect After Discourse Login', 'wp-discourse' ), array(
			$this,
			'auto_create_login_redirect_input',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'auto_create_welcome_redirect', __( 'Path to the New User Welcome Page', 'wp-discourse' ), array(
		        $this,
            'auto_create_welcome_redirect',
        ), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array(
			$this,
			'wordpress_login_path',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_enable_discourse_sso', __( 'Enable SSO Client', 'wp-discourse' ), array(
			$this,
			'enable_sso_client_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'enable_discourse_sso_login_form_change', __( 'Add "Login with Discourse" to the Login Form', 'wp-discourse' ), array(
			$this,
			'enable_discourse_sso_login_form_change_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_enable_sso_sync', __( 'Sync Existing Users by Email', 'wp-discourse' ), array(
			$this,
			'sso_client_sync_by_email_checkbox',
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
	 * Outputs markup for the auto-create-sso-user checkbox.
	 */
	public function auto_create_sso_user_checkbox() {
		$description = __( "Automatically login users to Discourse when then login to WordPress. If the user does not yet
	    exist on Discourse, create the user. For this setting to work, you must enable the Discourse setting 'enable all return paths.'", 'wp-discourse' );
		$this->form_helper->checkbox_input( 'auto-create-sso-user', 'discourse_sso', __( 'Auto Create user.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the auto-create-login-redirect input.
	 */
	public function auto_create_login_redirect_input() {
	    $description = __( "Where users will be redirected to after being logged in to Discourse. Note: to have users redirected
	    back to your WordPress site after being logged in to Discourse, you must enable the 'enable all return paths' setting on your Discourse forum", 'wp-discourse' );
	    $this->form_helper->input( 'auto-create-login-redirect', 'discourse_sso', $description );
    }

	/**
	 * Outputs markup for the auto-create-welcome-redirect input.
	 */
	public function auto_create_welcome_redirect() {
	    $description = __( "An optional path to redirect users on when their Discourse account if first created.", 'wp-discourse' );
	    $this->form_helper->input( 'auto-create-welcome-redirect', 'discourse_sso', $description );
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
		$this->form_helper->checkbox_input( 'sso-client-login-form-change', 'discourse_sso', __( 'Add login link.', 'wp-discourse' ), __( 'When using Discourse as the SSO provider for your site, 
		enabling this setting will add a "Login with Discourse" link to your WordPress login form.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for sso-client-sync-by-email checkbox.
	 */
	public function sso_client_sync_by_email_checkbox() {
		$this->form_helper->checkbox_input( 'sso-client-sync-by-email', 'discourse_sso', __( 'Sync existing users.', 'wp-discourse' ), __( "When using Discourse as the SSO provider for your site,
	    enabling this setting will sync existing accounts based on the user's email address.", 'wp-discourse' ) );
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
			$this->form_helper->input( 'sso-secret', 'discourse_sso', __( 'Found at http://discourse.example.com/admin/site_settings/category/login', 'wp-discourse' ) );
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
