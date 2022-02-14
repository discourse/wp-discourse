<?php
/**
 * Handles Discourse User Synchronization.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\SyncDiscourseUser;

use WPDiscourse\DiscourseBase;

/**
 * Class SyncDiscourseUser
 */
class SyncDiscourseUser extends DiscourseBase {
	/**
	 * Logger context
	 *
	 * @access protected
	 * @var string
	 */
	protected $logger_context = 'webhook_user';

	/**
	 * SyncDiscourseUser constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'init', array( $this, 'setup_logger' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_update_user_route' ) );
	}

	/**
	 * Registers the Rest API route wp-discourse/v1/update-user.
	 */
	public function initialize_update_user_route() {
		$initialize_route = ! empty( $this->options['enable-sso'] ) && ! empty( $this->options['use-discourse-user-webhook'] );
		$initialize_route = apply_filters( 'wpdc_use_discourse_user_webhook', $initialize_route );
		if ( $initialize_route ) {
			register_rest_route(
				'wp-discourse/v1',
				'update-user',
				array(
					array(
						'methods'             => \WP_REST_Server::CREATABLE,
						'permission_callback' => function() {
							return true;
						},
						'callback'            => array( $this, 'update_user' ),
					),
				)
			);
		}
	}

	/**
	 * Update WordPress user metadata from a Discourse webhook.
	 *
	 * @param  \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return null|\WP_Error
	 */
	public function update_user( $data ) {
		$use_webhook_sync = ! empty( $this->options['use-discourse-user-webhook'] ) && ( ! empty( $this->options['enable-sso'] ) );
		$use_webhook_sync = apply_filters( 'wpdc_use_discourse_user_webhook', $use_webhook_sync );

		if ( ! $use_webhook_sync ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'The Discourse User webhook is not enabled for your site.' ) );
		}

		// This function call is used to verify the request. For clarity, the permission callback should be updated to call this function.
		$data = $this->verify_discourse_webhook_request( $data );
		if ( is_wp_error( $data ) ) {
			$this->logger->error( 'update_user.webhook_verification_error', array( 'message', $data->get_error_message() ) );
			return $data;
		}

		$event_type   = $data->get_header( 'x_discourse_event_type' );
		$event_action = $data->get_header( 'x_discourse_event' );
		$json         = $data->get_json_params();

		if ( ! is_wp_error( $json ) && ! empty( $json['user'] ) ) {
			$discourse_user  = $json['user'];
			$discourse_email = sanitize_email( $discourse_user['email'] );
			$discourse_id    = intval( $discourse_user['id'] );
			$external_id     = ! empty( $discourse_user['external_id'] ) ? intval( $discourse_user['external_id'] ) : null;
			$wordpress_user  = null;

			if ( 'user_created' === $event_action ) {
				do_action( 'wpdc_webhook_user_created', $discourse_user );

				if ( $external_id ) {
					$wordpress_user = get_user_by( 'id', $external_id );
				} else {
					// It's safe to find the user by email when they are first created through SSO.
					$wordpress_user = get_user_by( 'email', $discourse_email );
				}
			} elseif ( 'user' === $event_type ) {
				do_action( 'wpdc_webhook_user_updated', $discourse_user );

				if ( $external_id ) {
					$wordpress_user = get_user_by( 'id', $external_id );
				} else {
					$user_query = new \WP_User_Query(
						array(
							'meta_key'   => 'discourse_sso_user_id',
							'meta_value' => $discourse_id,
							'number'     => 1,
						)
					);

					$user_query_results = $user_query->get_results();

					// For updating users created prior to version 1.4.0.
					if ( empty( $user_query_results ) && ! empty( $this->options['webhook-match-user-email'] ) ) {
						$wordpress_user = get_user_by( 'email', $discourse_email );
					} elseif ( ! is_wp_error( $user_query_results ) ) {
						$wordpress_user = $user_query_results[0];
					}
				}
			}

			if ( $wordpress_user && ! is_wp_error( $wordpress_user ) ) {
				do_action( 'wpdc_webhook_before_update_user_data', $wordpress_user, $discourse_user, $event_type );

				$user_id = $wordpress_user->ID;
				$this->update_user_data( $user_id, $discourse_user );
			} else {
				$log_args = array(
					'event_type'   => $event_type,
					'event_action' => $event_action,
					'discourse_id' => $discourse_id,
					'external_id'  => $external_id,
				);
				$this->logger->warn( 'update_user.user_not_found', $log_args );
			}
		} else {
			$this->logger->error( 'update_user.response_body_error' );
		}

		return null;
	}

	/**
	 * Update the WordPress user's metadata with values from the Discourse webhook.
	 *
	 * @param int    $user_id The WordPress user's id.
	 * @param object $user_data The json data from the Discourse webhook.
	 */
	protected function update_user_data( $user_id, $user_data ) {
		$discourse_username = sanitize_text_field( $user_data['username'] );
		$discourse_id       = intval( $user_data['id'] );

		update_user_meta( $user_id, 'discourse_username', $discourse_username );
		// The unique flag is important here.
		add_user_meta( $user_id, 'discourse_sso_user_id', $discourse_id, true );

		if ( ! empty( $this->options['verbose-webhook-logs'] ) ) {
			$log_args = array(
				'user_id'           => $user_id,
				'discourse_user_id' => $discourse_id,
			);
			$this->logger->info( 'update_user.update_user_data_success', $log_args );
		}
	}
}
