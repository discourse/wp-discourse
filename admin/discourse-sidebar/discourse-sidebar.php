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
	 *
	 * @return null
	 */
	public function setup_options() {
		$this->options = $this->get_options();
		if ( ! isset( $this->options['allowed_post_types'] ) ) {

			return null;
		}

		$allowed_post_types = isset( $this->options['allowed_post_types'] ) ? $this->options['allowed_post_types'] : null;
		$meta_keys          = array(
			'publish_to_discourse',
			'publish_post_category',
			'wpdc_auto_publish_overridden',
			'wpdc_topic_tags',
			'wpdc_pin_topic',
			'wpdc_pin_until',
			'discourse_post_id',
			'discourse_permalink',
			'wpdc_publishing_response',
			'wpdc_publishing_error',
		);

		$this->register_api_meta( $meta_keys, $allowed_post_types );

		return null;
	}

	/**
	 * Enqueue Sidebar javascript and stylesheet.
	 *
	 * @return null
	 */
	public function enqueue_scripts() {
		if ( ! isset( $this->options['allowed_post_types'] ) ) {

			return null;
		}
		$block_path = '/build/index.js';
		$style_path = '/styles.css';

		wp_register_script(
			'discourse-sidebar-js',
			plugins_url( $block_path, __FILE__ ),
			array( 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api' ),
			filemtime( plugin_dir_path( __FILE__ ) . $block_path ),
			true
		);

		$plugin_unconfigured = empty( $this->options['url'] ) || empty( $this->options['api-key'] ) || empty( $this->options['publish-username'] );
		$auto_publish        = ! empty( $this->options['auto-publish'] );
		$default_category    = $this->options['publish-category'];
		$allowed_post_types  = $this->options['allowed_post_types'];
		$force_publish       = ! empty( $this->options['force-publish'] );
		$allow_tags          = ! empty( $this->options['allow-tags'] );
		$max_tags            = isset( $this->options['max-tags'] ) ? $this->options['max-tags'] : 5;
		$data                = array(
			'pluginUnconfigured'      => $plugin_unconfigured,
			'autoPublish'             => $auto_publish,
			'defaultCategory'         => $default_category,
			'allowedPostTypes'        => $allowed_post_types,
			'forcePublish'            => $force_publish,
			'allowTags'               => $allow_tags,
			'maxTags'                 => $max_tags,
			'get_categories_nonce'    => wp_create_nonce( 'get_categories_nonce' ),
			'publish_topic_nonce'     => wp_create_nonce( 'publish_topic_nonce' ),
			'update_topic_nonce'      => wp_create_nonce( 'update_topic_nonce' ),
			'set_publish_meta_nonce'  => wp_create_nonce( 'set_publish_meta_nonce' ),
			'set_category_meta_nonce' => wp_create_nonce( 'set_category_meta_nonce' ),
			'set_pin_meta_nonce'      => wp_create_nonce( 'set_pin_meta_nonce' ),
			'set_tag_meta_nonce'      => wp_create_nonce( 'set_tag_meta_nonce' ),
			'link_topic_nonce'        => wp_create_nonce( 'link_topic_nonce' ),
			'unlink_post_nonce'       => wp_create_nonce( 'unlink_post_nonce' ),
			'logo'                    => WPDISCOURSE_LOGO,
		);

		wp_localize_script( 'discourse-sidebar-js', 'pluginOptions', $data );
		wp_enqueue_script( 'discourse-sidebar-js' );

		wp_enqueue_style(
			'discourse-sidebar-css',
			plugins_url( $style_path, __FILE__ ),
			'',
			filemtime( plugin_dir_path( __FILE__ ) . $style_path )
		);

		return null;
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
					$post_type,
					$meta_key,
					array(
						'single'        => true,
						'show_in_rest'  => true,
						'auth_callback' => __return_false(),
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
			'wp-discourse/v1',
			'get-discourse-categories',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'permission_callback' => function( $data ) {

						return $this->get_api_category_permissions( $data, 'get_categories_nonce' );
					},
					'callback'            => array( $this, 'get_categories' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'update-topic',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {
						return $this->get_api_post_permissions( $data, 'update_topic_nonce' );
					},
					'callback'            => array( $this, 'update_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'publish-topic',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {
						return $this->get_api_post_permissions( $data, 'publish_topic_nonce' );
					},
					'callback'            => array( $this, 'publish_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'unlink-post',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {
						return $this->get_api_post_permissions( $data, 'unlink_post_nonce' );
					},
					'callback'            => array( $this, 'unlink_post' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'link-topic',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {
						return $this->get_api_post_permissions( $data, 'link_topic_nonce' );
					},
					'callback'            => array( $this, 'link_topic' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'set-publish-meta',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {
						return $this->get_api_post_permissions( $data, 'set_publish_meta_nonce' );
					},
					'callback'            => array( $this, 'set_publish_meta' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'set-category-meta',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {

						return $this->get_api_post_permissions( $data, 'set_category_meta_nonce' );
					},
					'callback'            => array( $this, 'set_category_meta' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'set-tag-meta',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {

						return $this->get_api_post_permissions( $data, 'set_tag_meta_nonce' );
					},
					'callback'            => array( $this, 'set_tag_meta' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1',
			'set-pin-meta',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => function( $data ) {

						return $this->get_api_post_permissions( $data, 'set_pin_meta_nonce' );
					},
					'callback'            => array( $this, 'set_pin_meta' ),
				),
			)
		);
	}


	/**
	 * Checks the permissions for requests made to the `get_discourse_categories` route.
	 *
	 * @param object $data The data sent with the API Request.
	 * @param string $nonce_name The nonce action name to validate.
	 *
	 * @return bool|\WP_Error
	 */
	public function get_api_category_permissions( $data, $nonce_name ) {
		$nonce = ! empty( $data[ $nonce_name ] ) ? sanitize_key( $data[ $nonce_name ] ) : null;
		if ( ! wp_verify_nonce( $nonce, $nonce_name ) || ! current_user_can( 'publish_posts' ) ) {

			return $this->rest_authorization_error();
		}

		return true;
	}

	/**
	 * Checks the permissions for requests made to Rest Routes that act on posts.
	 *
	 * @param object $data The data sent with the API Request.
	 * @param string $nonce_name The nonce action name to validate.
	 * @return bool|\WP_Error
	 */
	public function get_api_post_permissions( $data, $nonce_name ) {
		$nonce   = ! empty( $data[ $nonce_name ] ) ? sanitize_key( $data[ $nonce_name ] ) : null;
		$post_id = isset( $data['id'] ) ? intval( wp_unslash( $data['id'] ) ) : 0; // Input var okay.

		if ( $post_id <= 0 ) {
			return new \WP_Error(
				'rest_invalid_param',
				// translators: Discourse invalid parameter message. Placeholder: the post ID.
				sprintf( __( 'Invalid parameter: %s', 'wp-discourse' ), $post_id ),
				array(
					'status' => 400,
				)
			);
		}

		if ( ! wp_verify_nonce( $nonce, $nonce_name ) || ! current_user_can( 'edit_post', $post_id ) ) {

			return $this->rest_authorization_error();
		}

		return true;
	}

	/**
	 * The error that is returned for unauthorized requests.
	 *
	 * @return \WP_Error
	 */
	protected function rest_authorization_error() {
		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to do that.', 'wp-discourse' ),
			array(
				'status' => rest_authorization_required_code(),
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
		$publish_to_discourse = intval( wp_unslash( $data['publish_to_discourse'] ) ); // Input var okay.
		update_post_meta( $post_id, 'publish_to_discourse', $publish_to_discourse );

		// The auto-publish option is overridden if the author explicitly sets the publish state in the Discourse sidebar.
		if ( ! empty( $this->options['auto-publish'] ) ) {
			update_post_meta( $post_id, 'wpdc_auto_publish_overridden', 1 );
		}
	}

	/**
	 * Updates the publish_post_category metadata.
	 *
	 * Called by `handleCategoryChange`.
	 *
	 * @param object $data The data received from the API request.
	 */
	public function set_category_meta( $data ) {
		$post_id     = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$category_id = intval( wp_unslash( $data['publish_post_category'] ) ); // Input var okay.
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
		$post_id = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$tags    = sanitize_text_field( wp_unslash( $data['wpdc_topic_tags'] ) ); // Input var okay.
		update_post_meta( $post_id, 'wpdc_topic_tags', $tags );
	}

	/**
	 * Updates the pin-topic metadata fields.
	 *
	 * Called by `handlePinChange`.
	 *
	 * @param object $data The data received from the API request.
	 */
	public function set_pin_meta( $data ) {
		$post_id   = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$pin_topic = intval( wp_unslash( $data['wpdc_pin_topic'] ) ); // Input var okay.
		if ( ! empty( $data['wpdc_pin_until'] ) ) { // Input var okay.
			$pin_until = sanitize_text_field( wp_unslash( $data['wpdc_pin_until'] ) ); // Input var okay.
		} else {
			$now = new \DateTime( 'now' );
			try {
				$pin_until = $now->add( new \DateInterval( 'P2D' ) )->format( 'Y-m-d' );
			} catch ( \Exception $e ) {
				$pin_until = null;
			}
		}

		update_post_meta( $post_id, 'wpdc_pin_topic', $pin_topic );
		update_post_meta( $post_id, 'wpdc_pin_until', $pin_until );
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

		if ( ! empty( $this->options['auto-publish'] ) ) {
			update_post_meta( $post_id, 'wpdc_auto_publish_overridden', 1 );
		}

		// We need to return something here so that the UI gets updated.
		return 1;
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

		if ( empty( $post->post_title ) ) {

			return array(
				'update_response' => 'You need to add a title to the post before it can be updated on Discourse.',
			);
		}
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

		if ( empty( $post->post_title ) ) {

			return array(
				'publish_response' => 'You need to add a title to the post before it can be published to Discourse.',
			);
		}

		update_post_meta( $post_id, 'publish_to_discourse', 1 );

		$this->discourse_publish->publish_post_after_save( $post_id, $post );

		$publishing_error = get_post_meta( $post_id, 'wpdc_publishing_error', true );
		$response         = $publishing_error ? $publishing_error : 'success';
		$permalink        = get_post_meta( $post_id, 'discourse_permalink', true );

		return array(
			'publish_response'    => $response,
			'discourse_permalink' => esc_url( $permalink ),
		);
	}

	/**
	 * Gets the wpdc_discourse_categories option.
	 *
	 * @param object $data The data sent with the API request.
	 * @return array|null
	 */
	public function get_categories( $data ) {
		$post_id = intval( wp_unslash( $data['id'] ) ); // Input var okay.
		$post    = get_post( $post_id );

		return apply_filters( 'wp_discourse_publish_categories', $this->get_discourse_categories(), $post );
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
		if ( is_wp_error( $topic ) || empty( $topic->post_stream ) || 'regular' !== $topic->archetype ) {
			update_post_meta( $post_id, 'wpdc_linking_response', 'error' );

			return new \WP_Error( 'wpdc_response_error', 'Unable to link to Discourse topic.' );
		}

		update_post_meta( $post_id, 'wpdc_linking_response', 'success' );

		$discourse_post_id        = intval( $topic->post_stream->stream[0] );
		$topic_id                 = intval( $topic->id );
		$category_id              = intval( $topic->category_id );
		$discourse_comments_count = intval( $topic->posts_count ) - 1;
		$topic_slug               = sanitize_text_field( $topic->slug );
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
