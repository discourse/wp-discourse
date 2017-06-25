<?php
/**
 * Uses a Discourse webhook to sync topics with their associated WordPress posts.
 *
 * @package WPDiscourse\DiscourseWebhookRefresh
 */

namespace WPDiscourse\DiscourseWebhookRefresh;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseWebhookRefresh
 */
class DiscourseWebhookRefresh {

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
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Registers the Rest API route wp-discourse/v1/update-topic-content.
	 */
	public function initialize_comment_route() {
		if ( ! empty( $this->options['use-discourse-webhook'] ) ) {
			register_rest_route( 'wp-discourse/v1', 'update-topic-content', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_topic_content' ),
				),
			) );
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
		$data = $this->verify_discourse_request( $data );

		if ( is_wp_error( $data ) ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'Unable to process Discourse webhook.', 'wp-discourse' ) );
		}

		$json = $data->get_json_params();

		if ( ! empty( $json['post'] ) ) {
			$post_data                   = $json['post'];
			$use_multisite_configuration = is_multisite() && ! empty( $this->options['multisite-configuration'] ) && 1 === intval( $this->options['multisite-configuration'] );

			if ( $use_multisite_configuration ) {
				global $wpdb;
				$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
				$topic_id   = $post_data['topic_id'];
				$blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM {$table_name} WHERE topic_id = %d", $topic_id ) );

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
			$webhook_enabled             = ( 1 === intval( get_site_option( 'wpdc_site_use_discourse_webhook' ) ) );
			$use_multisite_configuration = ( 1 === intval( get_site_option( 'wpdc_site_multisite_configuration' ) ) );
			$create_or_update_db         = get_site_option( 'wpdc_topic_blog_db_version' ) !== $this->db_version;

			if ( $use_multisite_configuration && $webhook_enabled && $create_or_update_db ) {
				$table_name      = $wpdb->base_prefix . 'wpdc_topic_blog';
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
                  topic_id mediumint(9) NOT NULL,
                  blog_id mediumint(9) NOT NULL,
                  PRIMARY KEY  (topic_id)
	             ) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
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
	 */
	protected function update_post_metadata( $post_data ) {
		$topic_id    = $post_data['topic_id'];
		$post_number = $post_data['post_number'];
		$post_title  = $post_data['topic_title'];

		$post_id = DiscourseUtilities::get_post_id_by_topic_id( $topic_id );
		if ( ! $post_id ) {
			$this->get_post_id_by_title( $post_title, $topic_id );
		}
		if ( $post_id ) {
			$current_comment_count = get_post_meta( $post_id, 'discourse_comments_count', true );
			if ( $current_comment_count < $post_number - 1 ) {
				update_post_meta( $post_id, 'discourse_comments_count', $post_number - 1 );
				update_post_meta( $post_id, 'wpdc_sync_post_comments', 1 );
			}
		} elseif ( ! empty( $this->options['webhook-sync-notification'] ) ) {
			add_option( 'wpdc_webhook_sync_failures', array() );
			$failures                    = get_option( 'wpdc_webhook_sync_failures' );
			$failure_message             = array();
			$failure_message['title']    = $post_title;
			$failure_message['topic_id'] = $topic_id;
			$failure_message['time']     = date( 'l F jS h:i A' );
			$failures[] = $failure_message;

			// Used to create the content for the sync_failure notification - deleted after the notification is sent.
			update_option( 'wpdc_webhook_sync_failures', $failures );

			if ( ! wp_next_scheduled( 'wpdc_topic_sync_failure_notification' ) ) {
				$sync_period = apply_filters( 'wpdc_topic_sync_failure_notification_period', 4 * HOUR_IN_SECONDS );
				wp_schedule_single_event( time() + $sync_period, 'wpdc_topic_sync_failure_notification' );
			}
		}
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
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	protected function verify_discourse_request( $data ) {
		// The X-Discourse-Event-Signature consists of 'sha256=' . hamc of raw payload.
		// It is generated by computing `hash_hmac( 'sha256', $payload, $secret )`.
		$sig = substr( $data->get_header( 'X-Discourse-Event-Signature' ), 7 );
		if ( $sig ) {
			$payload = $data->get_body();
			// Key used for verifying the request - a matching key needs to be set on the Discourse webhook.
			$secret = ! empty( $this->options['webhook-secret'] ) ? $this->options['webhook-secret'] : '';

			if ( ! $secret ) {
				return new \WP_Error( 'discourse_webhook_configuration_error', 'The webhook secret key has not been set.' );
			}

			if ( hash_hmac( 'sha256', $payload, $secret ) === $sig ) {

				return $data;
			} else {

				return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: signatures did not match.' );
			}
		}

		return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: the X-Discourse-Event-Signature was not set for the request.' );
	}
}
