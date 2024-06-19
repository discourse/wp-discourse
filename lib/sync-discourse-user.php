<?php
/**
 * Handles Discourse User Synchronization.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\SyncDiscourseUser;

use WPDiscourse\DiscourseBase;
use WPDiscourse\Shared\WebhookUtilities;

/**
 * Class SyncDiscourseUser
 */
class SyncDiscourseUser extends DiscourseBase {
    use WebhookUtilities;

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

    $this->supported_events = array(
        'user_created',
        'user_updated',
    );
	}

	/**
	 * Registers the Rest API route wp-discourse/v1/update-user.
	 */
	public function initialize_update_user_route() {
		if ( $this->webhook_enabled() ) {
			register_rest_route(
				'wp-discourse/v1',
				'update-user',
				array(
					array(
						'methods'             => \WP_REST_Server::CREATABLE,
						'permission_callback' => function () {
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
	 * @param  \WP_REST_Request $request The WP_REST_Request object.
	 *
	 * @return null|\WP_Error
	 */
	public function update_user( $request ) {
    $data = $this->get_webhook_data( $request );

    if ( is_wp_error( $data ) ) {
				return $this->failed_response( $data->get_error_message() );
    }

    $discourse_user  = $data->json['user'];
    $discourse_email = sanitize_email( $discourse_user['email'] );
    $discourse_id    = intval( $discourse_user['id'] );
    $external_id     = ! empty( $discourse_user['external_id'] ) ? intval( $discourse_user['external_id'] ) : null;
    $wordpress_user  = null;

    if ( 'user_created' === $data->event ) {
			do_action( 'wpdc_webhook_user_created', $discourse_user );

			if ( $external_id ) {
				$wordpress_user = get_user_by( 'id', $external_id );
				}

			if ( ! $wordpress_user && ! empty( $discourse_email ) ) {
        $wordpress_user = get_user_by( 'email', $discourse_email );
				}
    }

    if ( 'user_updated' === $data->event ) {
			do_action( 'wpdc_webhook_user_updated', $discourse_user );

			if ( $external_id ) {
			    $wordpress_user = get_user_by( 'id', $external_id );
				}

			if ( ! $wordpress_user && ! empty( $discourse_id ) ) {
            $user_query = new \WP_User_Query(
                array(
                    'meta_key'   => 'discourse_sso_user_id',
                    'meta_value' => $discourse_id,
                    'number'     => 1,
                )
            );

            $user_query_results = $user_query->get_results();

            if ( ! empty( $user_query_results ) && ! is_wp_error( $user_query_results ) ) {
					$wordpress_user = $user_query_results[0];
            }
					}

			if ( ! $wordpress_user && ! empty( $this->options['webhook-match-user-email'] ) && ! empty( $discourse_email ) ) {
			    $wordpress_user = get_user_by( 'email', $discourse_email );
				}
    }

    if ( $wordpress_user && ! is_wp_error( $wordpress_user ) ) {
				do_action( 'wpdc_webhook_before_update_user_data', $wordpress_user, $discourse_user, $data->event_type );

				$user_id = $wordpress_user->ID;
				$this->update_user_data( $user_id, $discourse_user );

			return $this->success_response( 'User updated.' );
    } else {
				$log_args = array(
					'event_type'   => $data->event_type,
					'event'        => $data->event,
					'discourse_id' => $discourse_id,
					'external_id'  => $external_id,
				);
				$this->logger->warn( 'update_user.user_not_found', $log_args );

				return $this->failed_response( 'User not found.' );
    }
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

    /**
     * Common Webhook enabled function.
     */
    protected function webhook_enabled() {
        $enabled = ! empty( $this->options['use-discourse-user-webhook'] ) && (
            ! empty( $this->options['enable-sso'] ) || ! empty( $this->options['webhook-match-user-email'] )
        );

        return apply_filters( 'wpdc_use_discourse_user_webhook', $enabled );
    }
}
