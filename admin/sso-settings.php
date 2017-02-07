<?php
/**
 * SSO Settings.
 */

namespace WPDiscourse\SSOSettings;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class SSOSettings {
	protected $options;
	protected $option_input;

	public function __construct( $option_input ) {
		$this->option_input = $option_input;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		$this->options = DiscourseUtilities::get_options();

		// SSO settings.
		add_settings_section( 'discourse_sso_settings_section', __( 'SSO Settings', 'wp-discourse' ), array(
			$this,
			'sso_settings_tab_details',
		), 'discourse_sso' );

		add_settings_field( 'discourse_enable_sso', __( 'Enable SSO', 'wp-discourse' ), array(
			$this,
			'enable_sso_checkbox',
		), 'discourse_sso', 'discourse_sso_settings_section' );

		add_settings_field( 'discourse_wp_login_path', __( 'Path to your login page', 'wp-discourse' ), array(
			$this,
			'wordpress_login_path',
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
			$this,
			'discourse_validate_options',
		) );
	}

	/**
	 * --------------------
	 * SSO settings fields.
	 * --------------------
	 */

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_checkbox() {
		$this->option_input->checkbox_input( 'enable-sso', 'discourse_sso', __( 'Enable SSO to Discourse.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	public function wordpress_login_path() {
		$this->option_input->text_input( 'login-path', 'discourse_sso', __( '(Optional) The path to your login page. It should start with \'/\'. Leave blank to use the default WordPress login page.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		$options = $this->options;
		if ( isset( $options['url'] ) && ! empty( $options['url'] ) ) {
			$this->option_input->text_input( 'sso-secret', 'discourse_sso', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $options['url'] ) . '/admin/site_settings/category/login" target="_blank">' . esc_url( $options['url'] ) . '/admin/site_settings/category/login</a>' );
		} else {
			$this->option_input->text_input( 'sso-secret', 'discourse_connect', __( 'Found at http://discourse.example.com/admin/site_settings/category/login', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	public function redirect_without_login_checkbox() {
		$this->option_input->checkbox_input( 'redirect-without-login', 'discourse_sso', __( 'Do not force login for link to Discourse comments thread.' ) );
	}

	/**
	 * Details for the 'sso_options' tab.
	 */
	public function sso_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring WordPress as the Single Sign On provider for Discourse. Unless you have a need to manage your forum\'s users through your WordPress site, you can leave this setting alone. For more information, see the ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}

	/**
	 * The callback for validating the 'discourse' options.
	 *
	 * @param array $inputs The inputs to be validated.
	 *
	 * @return array
	 */
	function discourse_validate_options( $inputs ) {
		$output = array();
		foreach ( $inputs as $key => $input ) {
			$filter = 'validate_' . str_replace( '-', '_', $key );

			if ( ! has_filter( $filter ) ) {
				error_log( 'Missing validation filter: ' . $filter );
			}
			$output[ $key ] = apply_filters( $filter, $input );
		}

		return $output;
	}
}