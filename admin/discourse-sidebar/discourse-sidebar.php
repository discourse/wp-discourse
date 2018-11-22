<?php

// Todo: move to namespaced class, or enqueue in admin.php
/**
 * Enqueue front end and editor JavaScript and CSS
 */
function discourse_sidebar_scripts() {
	$blockPath = '/dist/block.js';
	$stylePath = '/dist/block.css';

	// Enqueue the bundled block JS file
	wp_register_script(
		'discourse-sidebar-js',
		plugins_url( $blockPath, __FILE__ ),
		[ 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api' ],
		filemtime( plugin_dir_path(__FILE__) . $blockPath )
	);

	$publishingOptions = get_option( 'discourse_publish' );
	$data = array(
		'defaultCategory' => $publishingOptions['publish-category'],
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

// Hook scripts function into block editor hook
add_action( 'enqueue_block_editor_assets', 'discourse_sidebar_scripts' );

