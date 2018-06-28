<?php
/**
 * SSO Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class SSOSettings
 */
class SSOSettings {
	use PluginUtilities;

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
	 * @var array|void
	 */
	protected $options;

	/**
	 * The URL on Discourse for the SSO settings section.
	 *
	 * @access protected
	 * @var string|void
	 */
	protected $discourse_sso_settings_url;

	/**
	 * Whether or not to use some network sso settings.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $use_network_sso_settings;

	/**
	 * Whether or not to remove the SSO Client settings.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $remove_sso_client_settings;

	/**
	 * SSOSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_sso_settings' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'sso_settings_secondary_tabs' ), 10, 2 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'sso_settings_fields' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_sso_settings() {
		$this->options                    = $this->get_options();
		$this->use_network_sso_settings   = is_multisite() && ! empty( $this->options['multisite-configuration-enabled'] );
		$this->remove_sso_client_settings = is_multisite() && empty( $this->options['multisite-configuration-enabled'] );

		$this->discourse_sso_settings_url = ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/site_settings/category/all_results?filter=sso' : null;

		add_settings_section(
			'discourse_sso_common_settings_section', __( 'WP Discourse SSO', 'wp-discourse' ), array(
				$this,
				'common_settings_details',
			), 'discourse_sso_common'
		);

		add_settings_field(
			'discourse_sso_secret', __( 'SSO Secret Key', 'wp-discourse' ), array(
				$this,
				'sso_secret_input',
			), 'discourse_sso_common', 'discourse_sso_common_settings_section'
		);

		register_setting(
			'discourse_sso_common', 'discourse_sso_common', array(
				$this->form_helper,
				'validate_options',
			)
		);

		add_settings_section(
			'discourse_sso_provider_settings_section', __( 'SSO Provider Settings', 'wp-discourse' ), array(
				$this,
				'sso_provider_settings_details',
			), 'discourse_sso_provider'
		);

		add_settings_field(
			'discourse_enable_sso', __( 'Enable SSO Provider', 'wp-discourse' ), array(
				$this,
				'enable_sso_provider_checkbox',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		add_settings_field(
			'auto_create_sso_user', __( 'Create or Sync Discourse Users on Login', 'wp-discourse' ), array(
				$this,
				'auto_create_sso_user_checkbox',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		add_settings_field(
			'discourse_wp_login_path', __( 'Path to your Login Page', 'wp-discourse' ), array(
				$this,
				'wordpress_login_path',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		add_settings_field(
			'discourse_real_name_as_discourse_name', __( 'Use Real Name for Discourse Name', 'wp-discourse' ), array(
				$this,
				'use_real_name_checkbox',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		add_settings_field(
			'discourse_force_avatar_update', __( 'Force Avatar Update', 'wp-discourse' ), array(
				$this,
				'force_avatar_update_checkbox',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		add_settings_field(
			'discourse_redirect_without_login', __( 'Disable Comment Login Links', 'wp-discourse' ), array(
				$this,
				'redirect_without_login_checkbox',
			), 'discourse_sso_provider', 'discourse_sso_provider_settings_section'
		);

		register_setting(
			'discourse_sso_provider', 'discourse_sso_provider', array(
				$this->form_helper,
				'validate_options',
			)
		);

		add_settings_section(
			'discourse_sso_client_settings_section', __( 'SSO Client Settings', 'wp-discourse' ), array(
				$this,
				'sso_client_settings_details',
			), 'discourse_sso_client'
		);

		if ( ! $this->remove_sso_client_settings ) {
			add_settings_field(
				'discourse_enable_discourse_sso', __( 'Enable SSO Client', 'wp-discourse' ), array(
					$this,
					'enable_sso_client_checkbox',
				), 'discourse_sso_client', 'discourse_sso_client_settings_section'
			);

			add_settings_field(
				'enable_discourse_sso_login_form_change', __( 'Add Login Link', 'wp-discourse' ), array(
					$this,
					'enable_discourse_sso_login_form_change_checkbox',
				), 'discourse_sso_client', 'discourse_sso_client_settings_section'
			);

			add_settings_field(
				'discourse_sso_login_form_redirect', __( 'Login Link Redirect', 'wp-discourse' ), array(
					$this,
					'discourse_sso_login_form_redirect_url_input',
				), 'discourse_sso_client', 'discourse_sso_client_settings_section'
			);

			add_settings_field(
				'discourse_enable_sso_sync', __( 'Sync Existing Users by Email', 'wp-discourse' ), array(
					$this,
					'sso_client_sync_by_email_checkbox',
				), 'discourse_sso_client', 'discourse_sso_client_settings_section'
			);

			add_settings_field(
				'discourse_sso_client_sync_logout', __( 'Sync Logout with Discourse', 'wp-discourse' ), array(
					$this,
					'sso_client_sync_logout_checkbox',
				), 'discourse_sso_client', 'discourse_sso_client_settings_section'
			);
		}// End if().

		// If SSO Client is disabled, make sure that discourse_sso_client['sso-client-enabled'] is set to 0.
		if ( $this->remove_sso_client_settings ) {
			$discourse_sso_client = get_option( 'discourse_sso_client' );
			if ( is_array( $discourse_sso_client ) ) {
				$discourse_sso_client['sso-client-enabled'] = 0;
				update_option( 'discourse_sso_client', $discourse_sso_client );
			}
		}

		register_setting(
			'discourse_sso_client', 'discourse_sso_client', array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs settings sections for the current tab.
	 *
	 * Hooked into 'wpdc_options_page_after_tab_switch'.
	 *
	 * @param string|null $tab The current tab.
	 */
	public function sso_settings_fields( $tab ) {
		if ( 'sso_common' === $tab || 'sso_options' === $tab ) {
			settings_fields( 'discourse_sso_common' );
			do_settings_sections( 'discourse_sso_common' );
		}
		if ( 'sso_provider' === $tab ) {
			settings_fields( 'discourse_sso_provider' );
			do_settings_sections( 'discourse_sso_provider' );
		}
		if ( 'sso_client' === $tab ) {
			settings_fields( 'discourse_sso_client' );
			do_settings_sections( 'discourse_sso_client' );
		}
	}

	/**
	 * Outputs the tab-menu for the sso_options page.
	 *
	 * Hooked into 'wpdc_options_page_append_settings_tabs'.
	 *
	 * @param string|null $tab The current tab.
	 * @param string|null $parent_tab The current parent tab.
	 */
	public function sso_settings_secondary_tabs( $tab, $parent_tab ) {
		if ( 'sso_options' === $tab || 'sso_options' === $parent_tab ) {
			?>
			<h3 class="nav-tab-wrapper nav-tab-second-level">
				<a href="?page=wp_discourse_options&tab=sso_common&parent_tab=sso_options"
				   class="nav-tab <?php echo 'sso_common' === $tab || 'sso_options' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'SSO Secret Key', 'wpdc' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=sso_provider&parent_tab=sso_options"
				   class="nav-tab <?php echo 'sso_provider' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'SSO Provider', 'wpdc' ); ?>
				</a>
				<?php if ( ! $this->remove_sso_client_settings ) : ?>
					<a href="?page=wp_discourse_options&tab=sso_client&parent_tab=sso_options"
					   class="nav-tab <?php echo 'sso_client' === $tab ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e( 'SSO Client', 'wpdc' ); ?>
					</a>
				<?php endif; ?>
			</h3>
			<?php

		}
	}

	/**
	 * **************************
	 *
	 * Common SSO settings fields.
	 *****************************/

	/**
	 * Outputs markup for the sso-secret input.
	 */
	public function sso_secret_input() {
		if ( $this->use_network_sso_settings ) {
			?>
			<p>
				<em>
					<?php esc_html_e( 'The SSO Secret Key for your site has been set on the main site in the network.', 'wp-discourse' ); ?>
				</em>
			</p>

			<?php
		} else {
			$this->form_helper->input(
				'sso-secret', 'discourse_sso_common', __(
					"A string of text (numbers, letters, and symbols)
		at least 10 characters long. Use the same value in your forum's 'sso secret' setting.", 'wp-discourse'
				)
			);
		}
	}

	/**
	 * ****************************
	 *
	 * SSO Provider settings fields.
	 *******************************/

	/**
	 * Outputs markup for the enable-sso checkbox.
	 */
	public function enable_sso_provider_checkbox() {
		if ( $this->use_network_sso_settings ) {
			$sso_provider_enabled = ! empty( $this->options['enable-sso'] ) && 1 === intval( $this->options['enable-sso'] );
			?>
			<?php if ( $sso_provider_enabled ) : ?>
				<p>
					<em>
						<?php
						esc_html_e(
							"Your site has been enabled as the SSO provider for your Discourse forum through the main
                        site on this network. You can configure your site's SSO settings on this tab.", 'wp-discourse'
						);
?>
					</em>
				</p>
			<?php else : ?>
				<p>
					<em>
						<?php
						esc_html_e(
							'The use of all sites on this network as SSO providers for your Discourse forum
                        has been disabled by the main site on this network. Enabling any of thethe settings on this tab will
                        have no effect.', 'wp-discourse'
						);
?>
					</em>
				</p>

			<?php endif; ?>

			<?php
		} else {
			$description = __( 'Use this WordPress instance as the SSO provider for your Discourse forum.', 'wp-discourse' );
			$this->form_helper->checkbox_input( 'enable-sso', 'discourse_sso_provider', $description );
		}
	}

	/**
	 * Outputs markup for the auto-create-sso-user checkbox.
	 */
	public function auto_create_sso_user_checkbox() {
		$description = __(
			"Create a Discourse user after WordPress login. Users who already exist on Discourse will have their Discourse user's data synced with their WordPress data.", 'wp-discourse'
		);
		$this->form_helper->checkbox_input( 'auto-create-sso-user', 'discourse_sso_provider', __( 'Sync user data.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the login-path input.
	 */
	public function wordpress_login_path() {
		$this->form_helper->input(
			'login-path', 'discourse_sso_provider', __(
				"(Optional) If your site doesn't use the
		default WordPress login page at '/wp-login.php', you can set the path to your login page here. 
		It should start with '/'. Leave blank to use the default WordPress login page.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs the markup for the 'real-name-as-discourse-name' checkbox.
	 */
	public function use_real_name_checkbox() {
		$this->form_helper->checkbox_input(
			'real-name-as-discourse-name', 'discourse_sso_provider', __(
				"Set the Discourse
		name field to the WordPress user's real name.", 'wp-discourse'
			), __(
				"If neither first or last name have been set on WordPress,
	    the user's Display Name will be used instead.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the force-avatar-update checkbox.
	 */
	public function force_avatar_update_checkbox() {
		$this->form_helper->checkbox_input(
			'force-avatar-update', 'discourse_sso_provider', __( 'Update Discourse avatars from the WordPress avatar_url on each login request.', 'wp-discourse' ),
			__( 'Enabling this setting will keep the user avatars on Discourse in sync with the avatars on WordPress.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the redirect-without-login checkbox.
	 */
	public function redirect_without_login_checkbox() {
		$description = __(
			'By default, when using WordPress as the SSO provider, links to the Discourse comments automatically log
        the user into Discourse.'
		);
		$this->form_helper->checkbox_input( 'redirect-without-login', 'discourse_sso_provider', __( 'Do not force login for links to Discourse comments.' ), $description );
	}

	/**
	 * **************************
	 *
	 * SSO Client settings fields.
	 *****************************/

	/**
	 * Outputs markup for sso-client-enabled checkbox.
	 */
	public function enable_sso_client_checkbox() {
		if ( $this->use_network_sso_settings ) {
			$sso_client_enabled = ! empty( $this->options['sso-client-enabled'] ) && 1 === intval( $this->options['sso-client-enabled'] );
			if ( $sso_client_enabled ) {
				?>
				<p>
					<em>
						<?php esc_html_e( 'The use of your site as an SSO client for Discourse has been enabled by the main site on this network.', 'wp-discourse' ); ?>
					</em>
				</p>
				<?php
			} else {
				?>
				<p>
					<em>
						<?php
						esc_html_e(
							'The use of all sites on this network to function as an SSO client for Discourse has been
                        disabled by the main site on this network. Enabling any of the setting on this tab will have no effect.', 'wp-discourse'
						);
?>
					</em>
				</p>

				<?php
			}
		} else {
			$this->form_helper->checkbox_input( 'sso-client-enabled', 'discourse_sso_client', __( 'Allow your WordPress site to function as an SSO client to Discourse.', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for sso-client-login-form-change
	 */
	public function enable_discourse_sso_login_form_change_checkbox() {
		$this->form_helper->checkbox_input(
			'sso-client-login-form-change', 'discourse_sso_client', __( "Add a 'Login with Discourse' link to the WordPress login page.", 'wp-discourse' ),
			__(
				"Clicking on this link will allow users to authenticate themselves through Discourse, instead of through the
            WordPress login process. The text for this link can be customized on the 'Text Content' settings tab.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for sso-client-login-form-redirect.
	 */
	public function discourse_sso_login_form_redirect_url_input() {
		$this->form_helper->input(
			'sso-client-login-form-redirect', 'discourse_sso_client',
			__(
				'The full URL of the WordPress page that users will be redirected to after logging in through Discourse.
			(Leave this setting empty to redirect to your home page.)', 'wp-discourse'
			), 'url'
		);
	}

	/**
	 * Outputs markup for sso-client-sync-by-email checkbox.
	 */
	public function sso_client_sync_by_email_checkbox() {
		$this->form_helper->checkbox_input(
			'sso-client-sync-by-email', 'discourse_sso_client', __( 'Sync existing users by matching their Discourse email to their WordPress email.', 'wp-discourse' ),
			__(
				"Used for syncing a WordPress account created through the WordPress registration process with
            an account created by the same user on Discourse. If not enabled, accounts can be synced by clicking on the
            'Link account with Discourse' link on the user's profile page. Accounts created through the SSO login process
             are automatically synced. Note: WordPress email addresses can be changed without requiring confirmation.", 'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the sso-client-sync-logout checkbox.
	 */
	public function sso_client_sync_logout_checkbox() {
		$this->form_helper->checkbox_input(
			'sso-client-sync-logout', 'discourse_sso_client', __( 'Logout users from Discourse when they logout on WordPress.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs the markup for the sso_common tab details.
	 */
	public function common_settings_details() {
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					'Your WordPress site can be used as either the SSO provider, or as an SSO client with your Discourse forum.
                When used as the SSO provider, all user authentication for your forum will be handled through WordPress.
                When used as an SSO client, user authentication for your WordPress site can either be handled through your forum or WordPress.', 'wp-discourse'
				);
?>
			</em>
		</p>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					"All SSO functionality requires you to create a secret key that's shared between your forum
                and your website. Set the secret key on both before enabling SSO.", 'wp-discourse'
				);
?>
			</em>
		</p>
		<?php if ( $this->discourse_sso_settings_url ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<?php esc_html_e( "You can find your forum's SSO settings ", 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $this->discourse_sso_settings_url ); ?>"
					   target="_blank"><?php esc_html_e( 'here', 'wp-discourse' ); ?></a><?php echo esc_html( '.' ); ?>
				</em>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Outputs the markup for the sso_provider tab details.
	 */
	public function sso_provider_settings_details() {
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					'Enabling your site to function as the SSO provider transfers all user authentication from
                Discourse to WordPress. Use of this setting requires some configuration on Discourse:', 'wp-discourse'
				);
?>
			</em>
		</p>
		<ul class="wpdc-documentation-list">
			<li>
				<em><?php esc_html_e( "select the 'enable sso' setting", 'wp-discourse' ); ?></em>
			</li>
			<li>
				<em><?php esc_html_e( 'add the home URL of your site ', 'wp-discourse' ); ?></em>
				<code class="wpdc-select-all"><?php echo esc_url( home_url() ); ?></code>
				<em><?php esc_html_e( " to the 'sso url' setting", 'wp-discourse' ); ?></em>
			</li>
			<li>
				<em><?php esc_html_e( "make sure that the 'sso secret' has been set, and that it's value matches the 'SSO Secret Key' setting on your WordPress site", 'wp-discourse' ); ?></em>
			</li>
		</ul>
		<p class="wpdc-options-documentation">
			<em>
				<strong><?php esc_html_e( 'Note: ', 'wp-discourse' ); ?></strong>
											<?php
											esc_html_e(
												"Discourse has a very good user management system.
                Don't enable this setting unless you have a reason to manage your users through WordPress.", 'wp-discourse'
											);
?>
			</em>
		</p>
		<?php if ( $this->discourse_sso_settings_url ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<?php esc_html_e( "Your forum's SSO settings are ", 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $this->discourse_sso_settings_url ); ?>"
					   target="_blank"><?php esc_html_e( 'here', 'wp-discourse' ); ?></a><?php echo esc_html( '.' ); ?>
				</em>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Outputs the markup for the sso_client tab details.
	 */
	public function sso_client_settings_details() {
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					"Enabling your site to function as an SSO client allows WordPress user authentication to be handled
                through either your Discourse forum, or your WordPress site. If a Discourse user logs into WordPress through an SSO link,
                they will be authenticated based on their Discourse credentials. If that user doesn't yet exist on your WordPress site, a new
                user will be created.", 'wp-discourse'
				);
?>
			</em>
		</p>
		<p class="wpdc-options-documentation">
			<em>
				<strong><?php esc_html_e( 'Note: ', 'wp-discourse' ); ?></strong><?php esc_html_e( 'this setting does not yet work with multisite installations.', 'wp-discourse' ); ?>
			</em>
		</p>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'Use of this setting requires some configuration on Discourse:' ); ?>
			</em>
		</p>
		<ul class="wpdc-documentation-list">
			<em>
				<li>
					<?php esc_html_e( "select the 'enable sso provider' setting", 'wp-discourse' ); ?>
				</li>
				<li>
					<?php esc_html_e( "make sure that the 'sso secret' has been set, and that it's value matches the 'SSO Secret Key' setting on your WordPress site", 'wp-discourse' ); ?>
				</li>
			</em>
		</ul>
		<h3>SSO Client Shortcode</h3>
		<p class="wpdc-options-documentation">
			<em><?php esc_html_e( 'You can add a ', 'wp-discourse' ); ?></em>
			<code class="wpdc-select-all"><?php esc_html_e( '[discourse_sso_client]' ); ?></code>
			<em>
			<?php
			esc_html_e(
				'shortcode to your WordPress posts to create a Discourse login link on the front end of your site.
            The shortcode has three optional attributes: ', 'wp-discourse'
			);
?>
</em>
		</p>
		<ul class="wpdc-documentation-list">
			<li>
				<code><?php esc_html_e( 'login', 'wp-discourse' ); ?></code><em>
										<?php
										esc_html_e(
											"- sets the link text for non-logged in users.
                (Defaults to 'Log in with Discourse'.)", 'wp-discourse'
										);
?>
</em>
			</li>
			<li><code><?php esc_html_e( 'link' ); ?></code><em>
										<?php
										esc_html_e(
											"- sets the link text for logged in users who have not
            yet linked their WordPress account to their Discourse account. (Defaults to 'Link your account to Discourse'.)", 'wp-discourse'
										);
?>
</em>
			</li>
			<li>
				<code><?php esc_html_e( 'redirect', 'wp-discourse' ); ?></code><em>
					<?php
					esc_html_e( '- sets the page the user is redirected to after logging in. (Defaults to the page the shortcode is embedded on.)', 'wp-discourse' );
					?>
				</em>
			</li>
		</ul>
		<p class="wpdc-options-documentation">
			<em><?php esc_html_e( 'Example: ', 'wp-discourse' ); ?></em><code><?php esc_html_e( "[discourse_sso_client login='Login Through the Forum' redirect=https://example.com/welcome]", 'wp-discourse' ); ?></code>
		</p>
		<?php if ( $this->discourse_sso_settings_url ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<?php esc_html_e( "Your forum's SSO settings are ", 'wp-discourse' ); ?>
					<a href="<?php echo esc_url( $this->discourse_sso_settings_url ); ?>"
					   target="_blank"><?php esc_html_e( 'here', 'wp-discourse' ); ?></a><?php echo esc_html( '.' ); ?>
				</em>
			</p>
		<?php endif; ?>
		<?php
	}
}
