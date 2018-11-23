<?php

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

class DiscourseSidebar {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

//	protected $meta_keys = array(
//		'publish_to_discourse',
//		'publish_post_category',
//		'discourse_post_id',
//		'discourse_topic_id',
//		'discourse_permalink',
//		'wpdc_publishing_response',
//	);

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options'));
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );

	}

	public function setup_options() {
		$this->options = $this->get_options();
	}

	public function enqueue_scripts() {
		$blockPath = '/dist/block.js';
		$stylePath = '/dist/block.css';

		// Enqueue the bundled block JS file
		wp_register_script(
			'discourse-sidebar-js',
			plugins_url( $blockPath, __FILE__ ),
			[ 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api' ],
			filemtime( plugin_dir_path(__FILE__) . $blockPath )
		);

		$default_category = $this->options['publish-category'];
		$allowed_post_types = $this->options['allowed_post_types'];
		$data = array(
			'defaultCategory' => $default_category,
			'allowedPostTypes' => $allowed_post_types,
		);

		wp_localize_script( 'discourse-sidebar-js', 'pluginOptions', $data );

		wp_enqueue_script( 'discourse-sidebar-js' );

		// Enqueue frontend and editor block styles
		wp_enqueue_style(
			'discourse-sidebar-css',
			plugins_url( $stylePath, __FILE__ ),
			'',
			filemtime( plugin_dir_path(__FILE__) . $stylePath )
		);

	}
}
