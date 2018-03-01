<?php
/**
 * Connection Settings
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class ConnectionSettings
 */
class ConnectionSettings {
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
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Whether or not to display the connection_options fields.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $display_connection_options;

	/**
	 * ConnectionSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_connection_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_connection_settings() {
		$this->options                    = $this->get_options();
		$this->display_connection_options = ! is_multisite() || empty( $this->options['multisite-configuration-enabled'] );

		add_settings_section(
			'discourse_connection_settings_section', __( 'Connecting With Discourse', 'wp-discourse' ), array(
				$this,
				'connection_settings_tab_details',
			), 'discourse_connect'
		);

		if ( $this->display_connection_options ) {
			add_settings_field(
				'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array(
					$this,
					'url_input',
				), 'discourse_connect', 'discourse_connection_settings_section'
			);

			add_settings_field(
				'discourse_api_key', __( 'API Key', 'wp-discourse' ), array(
					$this,
					'api_key_input',
				), 'discourse_connect', 'discourse_connection_settings_section'
			);

			add_settings_field(
				'discourse_publish_username', __( 'Publishing Username', 'wp-discourse' ), array(
					$this,
					'publish_username_input',
				), 'discourse_connect', 'discourse_connection_settings_section'
			);
		}// End if().

		register_setting(
			'discourse_connect', 'discourse_connect', array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	public function url_input() {
		$this->form_helper->input( 'url', 'discourse_connect', __( 'The base URL of your forum, for example http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	public function api_key_input() {
		$discourse_options = $this->options;
		if ( ! empty( $discourse_options['url'] ) ) {
			$this->form_helper->input(
				'api-key', 'discourse_connect', __( 'Found on your forum at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) .
																	   '/admin/api/keys" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api/keys</a>. ' .
				"If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse'
			);
		} else {
			$this->form_helper->input(
				'api-key', 'discourse_connect', __(
					"Found on your forum at /admin/api/keys.
			If you haven't yet created an API key, Click 'Generate Master API Key'. Copy and paste the API key here.", 'wp-discourse'
				)
			);
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->form_helper->input(
			'publish-username', 'discourse_connect', __(
				'The default Discourse username under which WordPress posts will be published on your forum.
		The Publishing Username is also used for making API calls to Discourse. It must be set to a Discourse admin username.', 'wp-discourse'
			)
		);
	}

	/**
	 * Details for the connection_options tab.
	 */
	public function connection_settings_tab_details() {
		$self_install_url          = 'https://github.com/discourse/discourse/blob/master/docs/INSTALL-cloud.md';
		$community_install_url     = 'https://www.literatecomputing.com/product/discourse-install/';
		$discourse_org_install_url = 'https://payments.discourse.org/buy/';
		$setup_howto_url           = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url        = 'https://meta.discourse.org/';
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php
				esc_html_e(
					"The WP Discourse plugin is used to connect an existing Discourse forum with your WordPress site.
                If you don't already have a Discourse forum, here are some options for setting one up:", 'wp-discourse'
				);
?>
			</em>
		</p>
		<ul class="wpdc-documentation-list">
			<em>
				<li>
					<a href="<?php echo esc_url( $self_install_url ); ?>" target="_blank">install it yourself for
						free</a>
				</li>
				<li>
					<a href="<?php echo esc_url( $community_install_url ); ?>" target="_blank">self-supported community
						installation</a>
				</li>
				<li>
					<a href="<?php echo esc_url( $discourse_org_install_url ); ?>" target="_blank">discourse.org
						hosting</a>
				</li>
			</em>
		</ul>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'For detailed instructions on setting up the plugin, please see the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $setup_howto_url ); ?>"
				   target="_blank"><?php esc_html_e( 'WP Discourse plugin installation and setup', 'wp-discourse' ); ?></a>
				<?php esc_html_e( 'topic on the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $discourse_meta_url ); ?>" target="_blank">Discourse Meta</a>
				<?php esc_html_e( 'forum.', 'wp-discourse' ); ?>
			</em>
		</p>
		<?php if ( $this->display_connection_options ) : ?>
			<p class="wpdc-options-documentation">
				<em>
					<strong><?php esc_html_e( 'The following settings are used to establish a connection between your site and your forum:', 'wp-discourse' ); ?></strong>
				</em>
			</p>
		<?php else : ?>
			<p class="wpdc-options-documentation wpdc-subsite-documentation">
				<em>
					<strong>
					<?php
					esc_html_e(
						"You are using the WP Discourse plugin in a subsite of a multisite installation.
                    The plugin's API credentials are being managed through the installation's main site. If you have difficulty
                    connecting to the Discourse forum. Please contact the network administrator.", 'wp-discourse'
					);
?>
</strong>
				</em>
			</p>
		<?php endif; ?>

		<?php
	}
}
