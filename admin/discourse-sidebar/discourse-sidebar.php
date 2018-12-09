<?php
/**
 * Add the Gutenberg Sidebar.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class DiscourseSidebar
 */
class DiscourseSidebar {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * The discourse_publish object.
	 *
	 * @access protected
	 * @var \WPDiscourse\DiscoursePublish\DiscoursePublish
	 */
	protected $discourse_publish;

	/**
	 * DiscourseSidebar constructor.
	 *
	 * @param object $discourse_publish Required for updating topics through the REST API.
	 */
	public function __construct( $discourse_publish ) {
		$this->discourse_publish = $discourse_publish;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'register_sidebar_routes' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Setup options and register API meta keys.
	 */
	public function setup_options() {
		$this->options      = $this->get_options();
		$allowed_post_types = $this->options['allowed_post_types'];
		$meta_keys          = array(
			'publish_to_discourse',
			'publish_post_category',
			'wpdc_topic_tags',
			'discourse_post_id',
			'discourse_permalink',
			'wpdc_publishing_response',
			'wpdc_publishing_error',
		);
		$this->register_api_meta( $meta_keys, $allowed_post_types );
	}

	/**
	 * Enqueue Sidebar javascript and stylesheet.
	 */
	public function enqueue_scripts() {
		$blockPath = '/dist/block.js';
		$stylePath = '/dist/block.css';

		wp_register_script(
			'discourse-sidebar-js',
			plugins_url( $blockPath, __FILE__ ),
			[ 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api' ],
			filemtime( plugin_dir_path( __FILE__ ) . $blockPath )
		);

		$default_category   = $this->options['publish-category'];
		$allowed_post_types = $this->options['allowed_post_types'];
		$force_publish      = ! empty( $this->options['force-publish'] );
		$allow_tags         = ! empty( $this->options['allow-tags'] );
		$max_tags           = isset( $this->options['max-tags'] ) ? $this->options['max-tags'] : 5;
		$data               = array(
			'defaultCategory'  => $default_category,
			'allowedPostTypes' => $allowed_post_types,
			'forcePublish'     => $force_publish,
			'allowTags'        => $allow_tags,
			'maxTags'          => $max_tags,
		);

		wp_localize_script( 'discourse-sidebar-js', 'pluginOptions', $data );
		wp_enqueue_script( 'discourse-sidebar-js' );

		wp_enqueue_style(
			'discourse-sidebar-css',
			plugins_url( $stylePath, __FILE__ ),
			'',
			filemtime( plugin_dir_path( __FILE__ ) . $stylePath )
		);
	}

	/**
	 * Register meta_keys so that they are returned for REST API requests.
	 *
	 * @param array $meta_keys The meta_keys to register.
	 * @param array $post_types The post types to register the meta_keys for.
	 */
	protected function register_api_meta( $meta_keys, $post_types ) {
		foreach ( $meta_keys as $meta_key ) {
			foreach ( $post_types as $post_type ) {
				register_meta(
					$post_type, $meta_key, array(
						'single'       => true,
						'show_in_rest' => true,
					)
				);
			}
		}
	}

	/**
	 * Register REST API routes for the Sidebar.
	 */
	public function register_sidebar_routes() {
		register_rest_route(
			'wp-discourse/v1', 'get-discourse-categories', array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_discourse_categories' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'update-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'publish-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'publish_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'unlink-post', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'unlink_post' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'link-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'link_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'set-publish-meta', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'set_publish_meta' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'set-category-meta', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'set_category_meta' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'set-tag-meta', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'set_tag_meta' ),
				),
			)
		);
	}

	/**
	 * Updates post_meta to indicate whether or not the post should be published to Discourse.
	 *
	 * Called by `handleToBePublishedChange`.
	 *
	 * @param object $data The data sent with the API request.
	 */
	public function set_publish_meta( $data ) {
		$post_id              = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$publish_to_discourse = intval( wp_unslash( $data['publish_to_discourse'] ) );
		update_post_meta( $post_id, 'publish_to_discourse', $publish_to_discourse );
	}

	/**
	 * Updates the publish_post_category metadata.
	 *
	 * Called by `handleCategoryChange`.
	 *
	 * @param object $data The data received from the API request.
	 */
	public function set_category_meta( $data ) {
		$post_id     = intval( wp_unslash( $data['id'] ) );
		$category_id = intval( wp_unslash( $data['publish_post_category'] ) );
		update_post_meta( $post_id, 'publish_post_category', $category_id );
	}

	/**
	 * Updates the wpdc_topic_tags metadata.
	 *
	 * Called by `handleTagChange`.
	 *
	 * @param object $data The data received from the API request.
	 */
	public function set_tag_meta( $data ) {
		$post_id = intval( wp_unslash( $data['id'] ) );
		$tags    = sanitize_text_field( wp_unslash( $data['wpdc_topic_tags'] ) );
		update_post_meta( $post_id, 'wpdc_topic_tags', $tags );
	}

	/**
	 * Unlinks a post from Discourse by deleting all Discourse metadata.
	 *
	 * Called by `handleUnlinkFromDiscourseChange`.
	 *
	 * @param object $data The data sent with the API request.
	 */
	public function unlink_post( $data ) {
		$post_id = intval( wp_unslash( $data['id'] ) ); // Input var okay.

		delete_post_meta( $post_id, 'discourse_post_id' );
		delete_post_meta( $post_id, 'discourse_topic_id' );
		delete_post_meta( $post_id, 'discourse_permalink' );
		delete_post_meta( $post_id, 'discourse_comments_raw' );
		delete_post_meta( $post_id, 'discourse_comments_count' );
		delete_post_meta( $post_id, 'discourse_last_sync' );
		delete_post_meta( $post_id, 'publish_to_discourse' );
		delete_post_meta( $post_id, 'publish_post_category' );
		delete_post_meta( $post_id, 'update_discourse_topic' );
		delete_post_meta( $post_id, 'wpdc_sync_post_comments' );
		delete_post_meta( $post_id, 'wpdc_publishing_response' );
		delete_post_meta( $post_id, 'wpdc_publishing_error' );
		delete_post_meta( $post_id, 'wpdc_deleted_topic' );
	}

	/**
	 * Updates a post's associated Discourse topic.
	 *
	 * Called by `handleUpdateChange`.
	 *
	 * @param object $data The data sent with the API request.
	 *
	 * @return array
	 */
	public function update_topic( $data ) {
		$post_id = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$post    = get_post( $post_id );
		update_post_meta( $post_id, 'update_discourse_topic', 1 );

		$this->discourse_publish->publish_post_after_save( $post_id, $post );

		delete_post_meta( $post_id, 'update_discourse_topic' );

		$publishing_error = get_post_meta( $post_id, 'wpdc_publishing_error', true );
		$response         = $publishing_error ? $publishing_error : 'success';

		return array(
			'update_response' => $response,
		);
	}

	/**
	 * Publishes a post to Discourse.
	 *
	 * Called by `handlePublishChange`.
	 *
	 * @param object $data The data sent with the API request.
	 *
	 * @return array
	 */
	public function publish_topic( $data ) {
		$post_id = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$post    = get_post( $post_id );
		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$this->discourse_publish->publish_post_after_save( $post_id, $post );

		$publishing_error = get_post_meta( $post_id, 'wpdc_publishing_error', true );
		$response         = $publishing_error ? $publishing_error : 'success';
		$permalink        = get_post_meta( $post_id, 'discourse_permalink', true );

		return array(
			'publish_response'    => $response,
			'discourse_permalink' => $permalink,
		);
	}

	/**
	 * Gets the wpdc_discourse_categories option.
	 *
	 * @return array|null
	 */
	public function get_discourse_categories() {

		return get_option( 'wpdc_discourse_categories' );
	}

	/**
	 * Links a WordPress post to a Discourse topic.
	 *
	 * @param object $data The data sent with the API request.
	 * @return array|\WP_Error
	 */
	public function link_topic( $data ) {
		$post_id   = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$topic_url = esc_url_raw( wp_unslash( $data['topic_url'] ) ); // Input var okay.
		// Remove 'publish_to_discourse' metadata so we don't publish and link to the post.
		delete_post_meta( $post_id, 'publish_to_discourse' );
		$topic_url = explode( '?', $topic_url )[0];

		$topic_domain = wp_parse_url( $topic_url, PHP_URL_HOST );
		if ( get_option( 'wpdc_discourse_domain' ) !== $topic_domain ) {
			update_post_meta( $post_id, 'wpdc_linking_response', 'invalid_url' );

			return new \WP_Error( 'wpdc_configuration_error', 'An invalid topic URL was supplied when attempting to link post to Discourse topic.' );
		}
		$topic = $this->get_discourse_topic( $topic_url );

		// Check for the topic->post_stream here just to make sure it's a valid topic.
		if ( is_wp_error( $topic ) || empty( $topic->post_stream ) ) {
			update_post_meta( $post_id, 'wpdc_linking_response', 'error' );

			return new \WP_Error( 'wpdc_response_error', 'Unable to link to Discourse topic.' );
		}

		update_post_meta( $post_id, 'wpdc_linking_response', 'success' );

		$discourse_post_id        = $topic->post_stream->stream[0];
		$topic_id                 = $topic->id;
		$category_id              = $topic->category_id;
		$discourse_comments_count = $topic->posts_count - 1;
		$topic_slug               = $topic->slug;
		$discourse_permalink      = esc_url_raw( "{$this->options['url']}/t/{$topic_slug}/{$topic_id}" );

		update_post_meta( $post_id, 'discourse_post_id', $discourse_post_id );
		update_post_meta( $post_id, 'discourse_topic_id', $topic_id );
		update_post_meta( $post_id, 'publish_post_category', $category_id );
		update_post_meta( $post_id, 'discourse_permalink', $discourse_permalink );
		update_post_meta( $post_id, 'discourse_comments_count', $discourse_comments_count );
		delete_post_meta( $post_id, 'wpdc_publishing_error' );
		if ( ! empty( $this->options['use-discourse-webhook'] ) ) {
			update_post_meta( $post_id, 'wpdc_sync_post_comments', 1 );
		}

		return array(
			'discourse_permalink' => $discourse_permalink,
		);
	}
}
