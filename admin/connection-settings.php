<?php
/**
 * Connection Settings
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class ConnectionSettings {
	protected $options;
	protected $form_helper;

	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_connection_settings_section', __( 'Connection Settings', 'wp-discourse' ), array(
			$this,
			'connection_settings_tab_details',
		), 'discourse_connect' );

		add_settings_field( 'discourse_url', __( 'Discourse URL', 'wp-discourse' ), array(
			$this,
			'url_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		add_settings_field( 'discourse_api_key', __( 'API Key', 'wp-discourse' ), array(
			$this,
			'api_key_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		add_settings_field( 'discourse_publish_username', __( 'Publishing username', 'wp-discourse' ), array(
			$this,
			'publish_username_input',
		), 'discourse_connect', 'discourse_connection_settings_section' );

		register_setting( 'discourse_connect', 'discourse_connect', array(
			$this->form_helper,
			'validate_options',
		) );
	}
	/**
	 * ---------------------------
	 * Connection settings fields.
	 * ---------------------------
	 */

	/**
	 * Outputs markup for the Discourse-url input.
	 */
	public function url_input() {
		$this->form_helper->input( 'url', 'discourse_connect', __( 'e.g. http://discourse.example.com', 'wp-discourse' ), 'url' );
	}

	/**
	 * Outputs markup for the api-key input.
	 */
	public function api_key_input() {
		$discourse_options = $this->options;
		if ( isset( $discourse_options['url'] ) && ! empty( $discourse_options['url'] ) ) {
			$this->form_helper->input( 'api-key', 'discourse_connect', __( 'Found at ', 'wp-discourse' ) . '<a href="' . esc_url( $discourse_options['url'] ) . '/admin/api" target="_blank">' . esc_url( $discourse_options['url'] ) . '/admin/api</a>' );
		} else {
			$this->form_helper->input( 'api-key', 'discourse_connect', __( 'Found at http://discourse.example.com/admin/api', 'wp-discourse' ) );
		}
	}

	/**
	 * Outputs markup for the publish-username input.
	 */
	public function publish_username_input() {
		$this->form_helper->input( 'publish-username', 'discourse_connect', __( 'The default username under which posts will be published on Discourse. This will be overriden if a Discourse username has been supplied by the user (this can be set on the user\'s WordPress profile page.)', 'wp-discourse' ) );
	}



	/**
	 * Details for the connection_options tab.
	 */
	function connection_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring your site\'s connection to your Discourse forum. For detailed instructions, see the ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}
}
