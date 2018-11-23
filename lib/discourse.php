<?php
/**
 * Sets up the plugin.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Discourse;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class Discourse
 */
class Discourse {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * The connection options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_connect = array(
		'url'              => '',
		'api-key'          => '',
		'publish-username' => 'system',
	);

	/**
	 * The publishing options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_publish = array(
		'display-subcategories'     => 0,
		'publish-category'          => '',
		'publish-category-update'   => 0,
		'full-post-content'         => 0,
		'allow-tags'                => 0,
		'max-tags'                  => 5,
		'custom-excerpt-length'     => 55,
		'add-featured-link'         => 0,
		'auto-publish'              => 0,
		'force-publish'             => 0,
		'publish-failure-notice'    => 0,
		'publish-failure-email'     => '',
		'auto-track'                => 1,
		'allowed_post_types'        => array( 'post' ),
		'hide-discourse-name-field' => 0,
	);

	/**
	 * The commenting options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_comment = array(
		'enable-discourse-comments' => 0,
		'comment-type'              => 'display-comments',
		'ajax-load'                 => 0,
		'cache-html'                => 0,
		'clear-cached-comment-html' => 0,
		'discourse-new-tab'         => 0,
		'comment-sync-period'       => 10,
		'hide-wordpress-comments'   => 0,
		'show-existing-comments'    => 0,
		'existing-comments-heading' => '',
		'max-comments'              => 5,
		'min-replies'               => 1,
		'min-score'                 => 0,
		'min-trust-level'           => 1,
		'bypass-trust-level-score'  => 50,
		'custom-datetime-format'    => '',
		'only-show-moderator-liked' => 0,
		'load-comment-css'          => 0,
	);

	/**
	 * The configurable text options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_configurable_text = array(
		'discourse-link-text'         => '',
		'start-discussion-text'       => 'Start the discussion at',
		'continue-discussion-text'    => 'Continue the discussion at',
		'join-discussion-text'        => 'Join the discussion at',
		'comments-singular-text'      => 'Comment',
		'comments-plural-text'        => 'Comments',
		'no-comments-text'            => 'Join the Discussion',
		'notable-replies-text'        => 'Notable Replies',
		'comments-not-available-text' => 'Comments are not currently available for this post.',
		'participants-text'           => 'Participants',
		'published-at-text'           => 'Originally published at:',
		'single-reply-text'           => 'Reply',
		'many-replies-text'           => 'Replies',
		'more-replies-more-text'      => 'more',
		'external-login-text'         => 'Log in with Discourse',
		'link-to-discourse-text'      => 'Link your account to Discourse',
		'linked-to-discourse-text'    => "You're already linked to Discourse!",
	);

	/**
	 * The webhook options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_webhook = array(
		'use-discourse-webhook'      => 0,
		'webhook-secret'             => '',
		'webhook-match-old-topics'   => 0,
		'use-discourse-user-webhook' => 0,
		'webhook-match-user-email'   => 0,
	);

	/**
	 * The sso_common options array.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_sso_common = array(
		'sso-secret' => '',
	);

	/**
	 * The sso_provider options.
	 *
	 * @access protected
	 * @var array
	 */
	protected $discourse_sso_provider = array(
		'enable-sso'                  => 0,
		'auto-create-sso-user'        => 0,
		'login-path'                  => '',
		'real-name-as-discourse-name' => 0,
		'force-avatar-update'         => 0,
		'redirect-without-login'      => 0,
	);

	/**
	 * The sso_client options.
	 *
	 * @var array
	 */
	protected $discourse_sso_client = array(
		'sso-client-enabled'             => 0,
		'sso-client-login-form-change'   => 0,
		'sso-client-login-form-redirect' => '',
		'sso-client-sync-by-email'       => 0,
		'sso-client-sync-logout'         => 0,
	);

	/**
	 * The array of option groups, used for assembling the options into a single array.
	 *
	 * @var array
	 */
	protected $discourse_option_groups = array(
		'discourse_connect',
		'discourse_publish',
		'discourse_comment',
		'discourse_configurable_text',
		'discourse_webhook',
		'discourse_sso_common',
		'discourse_sso_provider',
		'discourse_sso_client',
	);

	/**
	 * Discourse constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_discourse_redirect' ) );
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_time_tag' ) );
		add_action( 'rest_api_init', array( $this, 'register_categories_route' ) );
	}

	// Todo: functions added for the Gutenberg sidebar. Move these.
	// Todo: check the security on the update-meta route.
	public function register_categories_route() {
		register_rest_route(
			'wp-discourse/v1', 'get-discourse-categories', array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_discourse_categories' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'update-meta', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_discourse_metadata' ),
				),
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'update-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_topic' ),
				)
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'unlink-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'unlink_topic' ),
				)
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'link-topic', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'link_topic' ),
				)
			)
		);

		register_rest_route(
			'wp-discourse/v1', 'set-publishing-options', array(
				array(
					'methods' => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'set_publishing_options' ),
				)
			)
		);
	}

	public function set_publishing_options( $data ) {
		write_log('setting publishing options. postId:', $data['id'], 'category', $data['publish_post_category'], 'publish to discourse', $data['publish_to_discourse']);
		$post_id = $data['id'];
		update_post_meta( $post_id, 'publish_to_discourse', $data['publish_to_discourse'] );
		update_post_meta( $post_id, 'publish_post_category', $data['publish_post_category'] );
	}

	public function unlink_topic( $data ) {
		$post_id = $data['id'];
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
		delete_post_meta( $post_id, 'wpdc_deleted_topic' );
	}

	public function update_topic( $data ) {
		write_log( 'post_id', $data['id']);
		update_post_meta( $data['id'], 'update_discourse_topic', 1 );
		// Todo: this seems wrong. Should just call the wp-discourse publish method.
		$update = wp_update_post( array( 'ID' => $data['id'] ) );
		delete_post_meta( $data['id'], 'update_discourse_topic' );

		return $update;
	}

	// Todo: can this function be protected?
	public function update_discourse_metadata( $data ) {
		$post_id = $data['id'];
		if ( $data['linked_topic_url'] && empty( $data['unlink_from_discourse'] ) ) {
			//$discourse_url = $data['linked_topic_url'];
			//$response      = $this->link_to_discourse_topic( $data['id'], $discourse_url );
		} elseif ( empty ( $data['unlink_from_discourse'] ) ) {
			write_log( 'if this is called, the post should be published' );
			update_post_meta( $data['id'], 'publish_to_discourse', $data['publish_to_discourse'] );
			update_post_meta( $data['id'], 'publish_post_category', $data['publish_post_category'] );
		}

	}

	public function get_discourse_categories() {

		return get_option( 'wpdc_discourse_categories' );
	}

	/**
	 * Links a WordPress post to a Discourse topic.
	 *
	 * @param int $post_id The WordPress post_id to link to.
	 * @param string $topic_url The Discourse topic URL.
	 *
	 * @return array|\WP_Error
	 */
	public function link_topic( $data ) {
		write_log('linking topic', $data['id'], $data['topic_url']);
		$post_id = $data['id'];
		$topic_url = $data['topic_url'];
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

	// Todo: end of functions added for Gutenberg

	/**
	 * Initializes the plugin configuration, loads the text domain etc.
	 */
	public function initialize_plugin() {
		load_plugin_textdomain( 'wp-discourse', false, basename( dirname( __FILE__ ) ) . '/languages' );
		$this->options = $this->get_options();

		// Set the Discourse domain name option.
		$discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$domain_name   = wp_parse_url( $discourse_url, PHP_URL_HOST );
		update_option( 'wpdc_discourse_domain', $domain_name );

		update_option( 'discourse_option_groups', $this->discourse_option_groups );

		foreach ( $this->discourse_option_groups as $group_name ) {
			if ( 'discourse_configurable_text' === $group_name && get_option( 'discourse_configurable_text' ) ) {
				$saved_values   = get_option( 'discourse_configurable_text' );
				$default_values = $this->discourse_configurable_text;
				$merged_values  = array_merge( $default_values, $saved_values );
				update_option( $group_name, $merged_values );
			} else {
				add_option( $group_name, $this->$group_name );
			}
		}

		// Transfer 'use-discourse-comments' and 'add-join-link' options. Plugin version 1.7.7.
		$commenting_options = get_option( 'discourse_comment' );
		if ( ! empty( $commenting_options['use-discourse-comments'] ) || ! empty( $commenting_options['add-join-link'] ) ) {
			$commenting_options['enable-discourse-comments'] = 1;
			$commenting_options['comment-type']              = ! empty( $this->options['use-discourse-comments'] ) ? 'display-comments' : 'display-comments-link';
		}
		unset( $commenting_options['use-discourse-comments'] );
		unset( $commenting_options['add-join-link'] );
		update_option( 'discourse_comment', $commenting_options );

		// Create a backup for the discourse_configurable_text option.
		update_option( 'discourse_configurable_text_backup', $this->discourse_configurable_text );
		update_option( 'discourse_version', WPDISCOURSE_VERSION );

		//Todo: metadata registered for the Gutenberg sidebar. Possibly this should be moved.
		register_meta( 'post', 'publish_to_discourse', array(
			'type'         => 'integer',
			'single'       => true,
			'show_in_rest' => true,
		) );
		register_meta( 'post', 'publish_post_category', array(
			'type'         => 'integer',
			'single'       => true,
			'show_in_rest' => true,
		) );
		register_meta( 'post', 'discourse_post_id', array(
			'type'         => 'integer',
			'single'       => true,
			'show_in_rest' => true,
		) );
		register_meta( 'post', 'discourse_topic_id', array(
			'type'         => 'integer',
			'single'       => true,
			'show_in_rest' => true,
		) );
		register_meta( 'post', 'discourse_permalink', array(
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => true,
		) );
		register_meta( 'post', 'wpdc_publishing_response', array(
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => true,
		) );
		// Todo: end of register_meta.
	}

	/**
	 * Adds the Discourse forum domain name to the allowed hosts for wp_safe_redirect().
	 *
	 * @param array $hosts The array of allowed hosts.
	 *
	 * @return array
	 */
	public function allow_discourse_redirect( $hosts ) {
		$discourse_domain = get_option( 'wpdc_discourse_domain' );

		if ( $discourse_domain ) {
			$hosts[] = $discourse_domain;
		}

		return $hosts;
	}

	/**
	 * Allow the time tag - used in Discourse comments.
	 *
	 * @param array $allowedposttags The array of allowed html tags.
	 *
	 * @return array
	 */
	public function allow_time_tag( $allowedposttags ) {
		$allowedposttags['time'] = array(
			'datetime' => array(),
		);

		return $allowedposttags;
	}
}
