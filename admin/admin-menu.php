<?php
/**
 * Adds the admin menu page and sub-menu pages.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

/**
 * Class AdminMenu
 */
class AdminMenu {

	/**
	 * An instance of the OptionsPage class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\OptionsPage
	 */
	protected $options_page;

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

	/**
	 * AdminMenu constructor.
	 *
	 * @param \WPDiscourse\Admin\OptionsPage $options_page An instance of the OptionsPage class.
	 * @param \WPDiscourse\Admin\FormHelper  $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
	}

	/**
	 * Add menu and sub-menu pages.
	 */
	public function add_menu_pages() {
		$settings = add_menu_page(
			__( 'Discourse', 'wp-discourse' ),
			__( 'Discourse', 'wp-discourse' ),
			'manage_options',
			'wp_discourse_options',
			array( $this->options_page, 'display' ),
			WPDISCOURSE_LOGO
		);
		add_action( 'load-' . $settings, array( $this->form_helper, 'connection_status_notice' ) );

		$all_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'All Options', 'wp-discourse' ),
			__( 'All Options', 'wp-discourse' ),
			'manage_options',
			'wp_discourse_options'
		);
		add_action( 'load-' . $all_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$connection_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Connection', 'wp-discourse' ),
			__( 'Connection', 'wp-discourse' ),
			'manage_options',
			'connection_options',
			array( $this, 'connection_options_tab' )
		);
		add_action( 'load-' . $connection_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$publishing_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Publishing', 'wp-discourse' ),
			__( 'Publishing', 'wp-discourse' ),
			'manage_options',
			'publishing_options',
			array( $this, 'publishing_options_tab' )
		);
		add_action( 'load-' . $publishing_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$commenting_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Commenting', 'wp-discourse' ),
			__( 'Commenting', 'wp-discourse' ),
			'manage_options',
			'commenting_options',
			array( $this, 'commenting_options_tab' )
		);
		add_action( 'load-' . $commenting_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$configurable_text_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Text Content', 'wp-discourse' ),
			__( 'Text Content', 'wp-discourse' ),
			'manage_options',
			'text_content_options',
			array( $this, 'text_content_options_tab' )
		);
		add_action( 'load-' . $configurable_text_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$webhook_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'Webhooks', 'wp-discourse' ),
			__( 'Webhooks', 'wp-discourse' ),
			'manage_options',
			'webhook_options',
			array( $this, 'webhook_options_tab' )
		);
		add_action( 'load-' . $webhook_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$sso_settings = add_submenu_page(
			'wp_discourse_options',
			__( 'DiscourseConnect', 'wp-discourse' ),
			__( 'DiscourseConnect', 'wp-discourse' ),
			'manage_options',
			'sso_options',
			array( $this, 'sso_options_tab' )
		);
		add_action( 'load-' . $sso_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$log_viewer = add_submenu_page(
			'wp_discourse_options',
			__( 'Logs', 'wp-discourse' ),
			__( 'Logs', 'wp-discourse' ),
			'manage_options',
			'log_viewer',
			array( $this, 'log_viewer_tab' )
		);
		add_action( 'load-' . $log_viewer, array( $this->form_helper, 'connection_status_notice' ) );
	}

	/**
	 * Called to display the 'connection_options' tab.
	 */
	public function connection_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'connection_options' );
		}
	}

	/**
	 * Called to display the 'publishing_options' tab.
	 */
	public function publishing_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'publishing_options' );
		}
	}

	/**
	 * Called to display the 'commenting_options' tab.
	 */
	public function commenting_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'commenting_options' );
		}
	}

	/**
	 * Called to display the 'text_content_options' tab.
	 */
	public function text_content_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'text_content_options' );
		}
	}

	/**
	 * Called to display the 'webhook_options' tab.
	 */
	public function webhook_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'webhook_options' );
		}
	}

	/**
	 * Called to display the 'sso_options' tab.
	 */
	public function sso_options_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'sso_options' );
		}
	}

	/**
	 * Called to display the 'log_viewer' tab.
	 */
	public function log_viewer_tab() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'log_viewer' );
		}
	}
}
