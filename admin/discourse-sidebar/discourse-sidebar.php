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

	protected $meta_keys = array(
		'publish_to_discourse',
		'publish_post_category',
		'discourse_post_id',
		'discourse_topic_id',
		'discourse_permalink',
		'wpdc_publishing_response',
	);

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options'));
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );

	}

	public function setup_options() {
		$this->options = $this->get_options();
		$allowed_post_types = $this->options['allowed_post_types'];
		$this->register_api_meta( $this->meta_keys, $allowed_post_types );
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



	protected function register_api_meta($meta_keys, $post_types) {
		foreach( $meta_keys as $meta_key ) {
			foreach ( $post_types as $post_type ) {
				// Todo: I'm not sure if 'type' needs to be set here. As it is, they will default to string?
				register_meta( $post_type, $meta_key, array(
					'single' => true,
					'show_in_rest' => true,
				));
			}
		}

	}

}
