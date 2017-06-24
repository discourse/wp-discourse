<?php

namespace WPDiscourse\DiscourseWebhook;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseWebhook {

	protected $options;

	protected $db_version = '1.0';

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_comment_route' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_create_db' ) );

	}

	public function maybe_create_db() {
		global $wpdb;
		if ( is_multisite() ) {
			$webhook_enabled             = 1 === intval( get_site_option( 'wpdc_site_multisite_configuration' ) );
			$use_multisite_configuration = 1 === intval( get_site_option( 'wpdc_site_multisite_configuration' ) );
			$create_or_update_db         = get_site_option( 'wpdc_topic_blog_db_version' ) !== $this->db_version;

			if ( $use_multisite_configuration && $webhook_enabled && $create_or_update_db ) {
				$table_name      = $wpdb->base_prefix . 'wpdc_topic_blog';
				$charset_collate = $wpdb->get_charset_collate();

				// Todo: don't create the table if it already exists!
				$sql = "CREATE TABLE $table_name (
                  topic_id mediumint(9) NOT NULL,
                  blog_id mediumint(9) NOT NULL,
                  PRIMARY KEY  (topic_id)
	             ) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );

				update_site_option( 'wpdc_topic_blog_db_version', $this->db_version );
			}
		}
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	public function initialize_comment_route() {
		if ( ! empty( $this->options['use-discourse-webhook'] ) && 1 === intval( $this->options['use-discourse-webhook'] ) ) {
			register_rest_route( 'wp-discourse/v1', 'update-topic-content', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_topic_content' ),
				),
			) );
		}
	}

	public function update_topic_content( $data ) {
		$data = $this->verify_discourse_request( $data );

		if ( is_wp_error( $data ) ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'Unable to process Discourse webhook.', 'wp-discourse' ) );
		}

		$json = $data->get_json_params();

		if ( ! empty( $json['post'] ) ) {
			$post_data = $json['post'];
			$use_multisite_configuration = is_multisite() && ! empty( $this->options['multisite-configuration'] ) && 1 === intval( $this->options['multisite-configuration'] );

			if ( $use_multisite_configuration ) {
				global $wpdb;
				$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
				$topic_id   = $post_data['topic_id'];

				$query   = $wpdb->prepare( "SELECT blog_id FROM $table_name WHERE topic_id = %d", $topic_id );
				$blog_id = $wpdb->get_var( $query );

				if ( $blog_id ) {
					switch_to_blog( $blog_id );
					$this->update_post_metadata( $post_data );
					restore_current_blog();
				}
			} else {
				$this->update_post_metadata( $post_data );
			}
		}
	}

	protected function update_post_metadata( $post_data ) {
		$topic_id    = $post_data['topic_id'];
		$post_number = $post_data['post_number'];
		$post_title  = $post_data['topic_title'];

		$post_id = DiscourseUtilities::get_post_id_by_topic_id( $topic_id );
		if ( ! $post_id ) {
			$this->get_post_id_by_title( $post_title );
		}
		if ( $post_id ) {
			$current_comment_count = get_post_meta( $post_id, 'discourse_comments_count', true );
			if ( $current_comment_count < $post_number - 1 ) {
				update_post_meta( $post_id, 'discourse_comments_count', $post_number - 1 );
				update_post_meta( $post_id, 'wpdc_sync_post_comments', 1 );
			}
		} else {
			add_option( 'wpdc_webhook_sync_failures', array() );
			$failures                    = get_option( 'wpdc_webhook_sync_failures' );
			$failure_message             = array();
			$failure_message['title']    = $post_title;
			$failure_message['topic_id'] = $topic_id;
			$failure_message['time']     = date('l F jS h:i A');;
			$failures[]                  = $failure_message;

			update_option( 'wpdc_webhook_sync_failures', $failures );

			if ( ! wp_next_scheduled( 'wpdc_topic_sync_failure_notification' ) ) {
				// Todo: increase this to a sane time period (12 hours?)
				wp_schedule_single_event( time() + 600, 'wpdc_topic_sync_failure_notification' );
			}
		}
	}

	protected function get_post_id_by_title( $title ) {
		$id        = null;
		$title     = strtolower( $title );
		$post_type = apply_filters( 'wpdc_webhook_get_page_by_title_post_type', 'post' );
		$post      = get_page_by_title( $title, 'OBJECT', $post_type );
		if ( $post && ! is_wp_error( $post ) ) {
			$id = $post->ID;
		}

		do_action( 'wpdc_webhook_after_get_page_by_title', $title );

		return $id;
	}

	/**
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	protected function verify_discourse_request( $data ) {
		// The X-Discourse-Event-Signature consists of 'sha256=' . hamc of raw payload.
		// It is generated by computing `hash_hmac( 'sha256', $payload, $secret )`
		if ( $sig = substr( $data->get_header( 'X-Discourse-Event-Signature' ), 7 ) ) {
			$payload = $data->get_body();
			// Key used for verifying the request - a matching key needs to be set on the Discourse webhook.
			$secret = ! empty( $this->options['webhook-secret'] ) ? $this->options['webhook-secret'] : '';

			if ( ! $secret ) {
				return new \WP_Error( 'discourse_webhook_configuration_error', 'The webhook secret key has not been set.' );
			}

			if ( $sig === hash_hmac( 'sha256', $payload, $secret ) ) {

				return $data;
			} else {

				return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: signatures did not match.' );
			}
		}

		return new \WP_Error( 'discourse_webhook_authentication_error', 'Discourse Webhook Request Error: the X-Discourse-Event-Signature was not set for the request.' );
	}
}