<?php
/**
 * Handles Discourse User Synchronization.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseUser;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseUser
 */
class DiscourseUser {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * DiscourseUser constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_update_user_route' ) );
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ) );
	}

	/**
	 * Setup the plugin options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Adds 'discourse_username' to the user_contactmethods array.
	 *
	 * @param array $fields The array of contact methods.
	 *
	 * @return mixed
	 */
	public function extend_user_profile( $fields ) {
		if ( ! empty( $this->options['hide-discourse-name-field'] ) ) {

			return $fields;
		} else {
			$fields['discourse_username'] = 'Discourse Username';
		}

		return $fields;
	}

	/**
	 * Registers the Rest API route wp-discourse/v1/update-topic-content.
	 */
	public function initialize_update_user_route() {
		if ( ! empty( $this->options['use-discourse-user-webhook'] ) ) {
			register_rest_route( 'wp-discourse/v1', 'update-user', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_user' ),
				),
			) );
		}
	}

	public function update_user( $data ) {
		$use_webhook_sync = ! empty( $this->options['use-discourse-user-webhook'] ) &&
		                    ( ! empty( $this->options['enable-sso'] || ! empty( $this->options['webhook-match-user-email'] ) ) );
		$use_webhook_sync = apply_filters( 'wpdc_use_discourse_user_webhook', $use_webhook_sync );

		if ( ! $use_webhook_sync ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'The Discourse User webhook is not enabled for your site.' ) );
		}

		$data = DiscourseUtilities::verify_discourse_webhook_request( $data );
		if ( is_wp_error( $data ) ) {

			return new \WP_Error( 'discourse_webhook_error', __( 'Unable to process Discourse User webhook.', 'wp-discourse' ) );
		}

		$event_type = $data->get_header( 'x_discourse_event' );
		$json       = $data->get_json_params();

		if ( ! empty( $json['user'] ) ) {
			$discourse_user     = $json['user'];
			$discourse_email    = $discourse_user['email'];
			$external_id        = ! empty( $discourse_user['external_id'] ) ? $discourse_user['external_id'] : null;
			$wordpress_user     = null;

			if ( 'user_created' === $event_type ) {
				do_action( 'wpdc_webhook_user_created', $discourse_user );

				if ( $external_id ) {
					$wordpress_user = get_user_by( 'id', $external_id );
				} else {
					$wordpress_user = get_user_by( 'email', $discourse_email );
				}
			}

			if ( 'user_updated' === $event_type ) {
				do_action( 'wpdc_webhook_user_updated', $discourse_user );

				if ( $external_id ) {
					$wordpress_user = get_user_by( 'id', $external_id );
				} elseif ( ! empty( $this->options['webhook-match-user-email'])) {
					$wordpress_user = get_user_by( 'email', $discourse_email );
				}
			}

			if ( $wordpress_user && ! is_wp_error( $wordpress_user ) ) {
				$user_id = $wordpress_user->ID;
				$this->update_user_data( $user_id, $discourse_user );
			}
		}

		return null;
	}

	protected function update_user_data( $user_id, $user_data ) {
		$discourse_username = $user_data['username'];
		$discourse_id = $user_data['id'];

		update_user_meta( $user_id, 'discourse_username', $discourse_username );
		add_user_meta( $user_id, 'discourse_sso_user_id', $discourse_id );
	}
}
