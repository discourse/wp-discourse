<?php
/**
 * Uses a Discourse webhook to sync topics with their associated WordPress posts.
 *
 * @package WPDiscourse\DiscourseWebhookRefresh
 */

namespace WPDiscourse\DiscourseWebhookRefresh;

use WPDiscourse\Webhook\Webhook;

/**
 * Class DiscourseWebhookRefresh
 */
class DiscourseWebhookRefresh extends Webhook {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var array|void
	 */
	protected $options;

	/**
	 * The current version of the wpdc_topic_blog database table.
	 *
	 * @access protected
	 * @var string
	 */
	protected $db_version = '1.0';

	/**
	 * DiscourseWebhookRefresh constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_comment_route' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_create_db' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Registers the Rest API route wp-discourse/v1/update-topic-content.
	 */
	public function initialize_comment_route() {
		if ( ! empty( $this->options['use-discourse-webhook'] ) ) {
			register_rest_route(
				'wp-discourse/v1', 'update-topic-content', array(
					array(
						'methods'  => \WP_REST_Server::CREATABLE,
						'callback' => array( $this, 'update_topic_content' ),
					),
				)
			);
		}
	}

	/**
	 * Handles the REST request.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request data object.
	 *
	 * @return null|\WP_Error
	 */
	public function update_topic_content( $data ) {
		$data = $this->verify_discourse_webhook_request( $data );

		if ( is_wp_error( $data ) ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'Unable to process Discourse webhook.', 'wp-discourse' ) );
		}

		$json = $data->get_json_params();
		do_action( 'wpdc_before_webhook_post_update', $json );

		if ( ! empty( $json['post'] ) ) {
			$post_data                   = $json['post'];
			$use_multisite_configuration = is_multisite() && ! empty( $this->options['multisite-configuration-enabled'] );

			if ( $use_multisite_configuration ) {
				global $wpdb;
				$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
				$topic_id   = $post_data['topic_id'];
				$query      = "SELECT blog_id FROM $table_name WHERE topic_id = %d";
				$blog_id    = $wpdb->get_var( $wpdb->prepare( $query, $topic_id ) );

				if ( $blog_id ) {
					switch_to_blog( $blog_id );
					$this->update_post_metadata( $post_data );
					restore_current_blog();
				}
			} else {
				$this->update_post_metadata( $post_data );
			}
		}

		return null;
	}

	/**
	 * Creates the wpdc_topic_blog database table using the site's base table prefix.
	 *
	 * The database table is only created in multisite installations when the multisite_configuration option is enabled.
	 * The table is used to associate the topic_id that's returned from a Discourse webhook request, with a post that's
	 * been published on a WordPress subsite.
	 */
	public function maybe_create_db() {
		global $wpdb;
		if ( is_multisite() ) {
			$use_multisite_configuration = ( 1 === intval( get_site_option( 'wpdc_multisite_configuration' ) ) );
			$create_or_update_db         = get_site_option( 'wpdc_topic_blog_db_version' ) !== $this->db_version;

			if ( $use_multisite_configuration && $create_or_update_db ) {
				$table_name      = $wpdb->base_prefix . 'wpdc_topic_blog';
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
                  topic_id mediumint(9) NOT NULL,
                  blog_id mediumint(9) NOT NULL,
                  PRIMARY KEY  (topic_id)
	             ) $charset_collate;";

				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				$result = dbDelta( $sql );

				if ( ! empty( $result[ $table_name ] ) ) {
					update_site_option( 'wpdc_topic_blog_db_version', $this->db_version );
				}
			}
		}
	}

	/**
	 * Tries to update some post metadata for WordPress posts that are associated with Discourse topics.
	 *
	 * The function tries to find the post from the Discourse topic_id that's returned with the webhook. For posts that
	 * have been published through the WP Discourse plugin prior to version 1.4.0 the topic_id will not be present. In
	 * this case, it then tries to find the post from its title. If that fails, an optional email notification is sent.
	 *
	 * If a post is found, if the post_number - 1 is greater than the saved discourse_comments_count, the comments count
	 * is updated and the post is marked as needing to be refreshed for the next time DiscourseComment::sync_comments is run.
	 *
	 * @param array $post_data The post_data from the Discourse webhook request.
	 * @return null
	 */
	protected function update_post_metadata( $post_data ) {
		$topic_id       = ! empty( $post_data['topic_id'] ) ? $post_data['topic_id'] : null;
		$post_number    = ! empty( $post_data['post_number'] ) ? $post_data['post_number'] : null;
		$post_title     = ! empty( $post_data['topic_title'] ) ? $post_data['topic_title'] : null;
		$comments_count = ! empty( $post_data['topic_posts_count'] ) ? $post_data['topic_posts_count'] - 1 : null;
		$post_type      = ! empty( $post_data['post_type'] ) ? $post_data['post_type'] : null;

		if ( $topic_id && $post_number && $post_title ) {

			$post_ids = $this->get_post_ids_from_topic_id( $topic_id );

			// For matching posts that were published before the plugin was saving the discourse_topic_id as post_metadata.
			if ( ! $post_ids && ! empty( $this->options['webhook-match-old-topics'] ) ) {
				$post_id = $this->get_post_id_by_title( $post_title, $topic_id );
				if ( $post_id ) {
					$post_ids[] = $post_id;
				}
			}

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					update_post_meta( $post_id, 'wpdc_sync_post_comments', 1 );

					// The topic_posts_count is being returned with the webhook data as of Discourse version 2.0.0.beta1.
					if ( $comments_count ) {
						update_post_meta( $post_id, 'discourse_comments_count', $comments_count );
					} else {
						// If the post_number is > discourse_comments_count, update the comments count.
						$current_comment_count = get_post_meta( $post_id, 'discourse_comments_count', true );
						if ( $current_comment_count < $post_number - 1 ) {
							update_post_meta( $post_id, 'discourse_comments_count', $post_number - 1 );
						}
					}

					$unlisted = get_post_meta( $post_id, 'wpdc_unlisted_topic', true );
					if ( ! empty( $unlisted ) && $comments_count > 0 && 1 === $post_type ) {
						$this->list_topic( $post_id, $topic_id );
					}
				}
			}
		}

		return null;
	}

	/**
	 * Changes a post's Discourse topic status from unlisted to listed.
	 *
	 * @param int $post_id The id of the post that needs to be listed.
	 * @param int $topic_id The id of the topic that needs to be listed.
	 *
	 * @return null|\WP_Error
	 */
	protected function list_topic( $post_id, $topic_id ) {
		$url          = $this->options['url'];
		$status_url   = esc_url( "{$url}/t/{$topic_id}/status" );
		$data         = array(
			'api_key'      => $this->options['api-key'],
			'api_username' => $this->options['publish-username'],
			'status'       => 'visible',
			'enabled'      => 'true',
		);
		$post_options = array(
			'timeout' => 30,
			'method'  => 'PUT',
			'body'    => http_build_query( $data ),
		);

		$response = wp_remote_post( $status_url, $post_options );

		if ( ! $this->validate( $response ) ) {

			return new \WP_Error( 'discourse_response_error', 'Unable to unlist the Discourse topic.' );
		}

		delete_post_meta( $post_id, 'wpdc_unlisted_topic' );

		return null;
	}

	/**
	 * Tries to match a WordPress post with a Discourse topic by the topic title.
	 *
	 * This function is used to match posts that have been published through the WP Discourse plugin prior to version 1.4.0
	 * with their associated Discourse topics. It assumes that the posts are using the 'post' type. There is a filter
	 * available to change the post type. There's also an action that can be hooked into if you'd like to try to match
	 * more than one post type.
	 *
	 * @param string $title The topic_title returned from Discourse.
	 * @param int    $topic_id The topic_id returned from Discourse.
	 *
	 * @return int|null
	 */
	protected function get_post_id_by_title( $title, $topic_id ) {
		$id        = null;
		$title     = strtolower( $title );
		$post_type = apply_filters( 'wpdc_webhook_get_page_by_title_post_type', 'post' );
		$post      = get_page_by_title( $title, 'OBJECT', $post_type );
		if ( $post && ! is_wp_error( $post ) ) {
			$id = $post->ID;
			// Update the 'discourse_topic_id' metadata so that it can be used on the next webhook request.
			update_post_meta( $id, 'discourse_topic_id', $topic_id );
		}

		do_action( 'wpdc_webhook_after_get_page_by_title', $title );

		return $id;
	}

	/**
	 * Tries to find a WordPress posts that are associated with a Discourse topic_id.
	 *
	 * An array is being returned because it's possible for more than one WordPress post to be associated with a Discourse topic.
	 *
	 * @param int $topic_id The topic_id to lookup.
	 *
	 * @return array|null
	 */
	protected function get_post_ids_from_topic_id( $topic_id ) {
		global $wpdb;

		$topic_posts = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'discourse_topic_id' AND meta_value = %d", $topic_id ) );

		if ( ! empty( $topic_posts ) ) {
			$topic_post_ids = [];
			foreach ( $topic_posts as $topic_post ) {
				$topic_post_ids[] = $topic_post->post_id;
			}

			return $topic_post_ids;
		}

		return null;
	}
}
