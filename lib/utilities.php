<?php
/**
 * Static utility functions for external use.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Utilities;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class PublicPluginUtilities
 *
 * @package WPDiscourse
 */
class PublicPluginUtilities {
	use PluginUtilities {
		get_options as public;
		validate as public;
		get_discourse_categories as public;
		get_discourse_user as public;
		get_discourse_user_by_email as public;
		sync_sso as public;
		discourse_request as public;
		get_api_credentials as public;
		get_sso_params as public;
		verify_discourse_webhook_request as public;
	}
}

/**
 * Class Utilities
 *
 * @package WPDiscourse
 */
class Utilities {
	/**
	 * Public static alias for get_options.
	 *
	 * @return array
	 */
	public static function get_options() {
		$utils = new PublicPluginUtilities();
		return $utils->get_options();
	}

	/**
	 * Public static alias for validate.
	 *
	 * @param array $response The response from `wp_remote_request`.
	 *
	 * @return int
	 */
	public static function validate( $response ) {
		$utils = new PublicPluginUtilities();
		return $utils->validate( $response );
	}

	/**
	 * Public static alias for get_discourse_categories.
	 *
	 * @return \WP_Error|array
	 */
	public static function get_discourse_categories() {
		$utils = new PublicPluginUtilities();
		return $utils->get_discourse_categories();
	}

	/**
	 * Public static alias for sync_sso.
	 *
	 * @param array $sso_params The sso params to sync.
	 * @param int   $user_id The WordPress user's ID.
	 *
	 * @return int|string|\WP_Error
	 */
	public static function sync_sso_record( $sso_params, $user_id = null ) {
		$utils = new PublicPluginUtilities();
		return $utils->sync_sso( $sso_params, $user_id );
	}

	/**
	 * Public static alias for get_discourse_user.
	 *
	 * @param int  $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function get_discourse_user( $user_id, $match_by_email = false ) {
		$utils = new PublicPluginUtilities();
		return $utils->get_discourse_user( $user_id, $match_by_email );
	}

	/**
	 * Public static alias for get_discourse_user_by_email.
	 *
	 * @param string $email The email address to search for.
	 *
	 * @return object \WP_Error
	 */
	public static function get_discourse_user_by_email( $email ) {
		$utils = new PublicPluginUtilities();
		return $utils->get_discourse_user_by_email( $email );
	}

	/**
	 * Public static alias for discourse_request.
	 *
	 * @param string $path Request path.
	 * @param array  $args Request args.
	 *
	 * @return array|\WP_Error|void
	 */
	public static function discourse_request( $path, $args = array() ) {
		$utils = new PublicPluginUtilities();
		return $utils->discourse_request( $path, $args );
	}

	/**
	 * Public static alias for get_sso_params.
	 *
	 * @param object $user The WordPress user.
	 * @param array  $sso_options An optional array of extra SSO parameters.
	 *
	 * @return array
	 */
	public static function get_sso_params( $user, $sso_options = array() ) {
		$utils = new PublicPluginUtilities();
		return $utils->get_sso_params( $user, $sso_options );
	}

	/**
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	public static function verify_discourse_webhook_request( $data ) {
		$utils = new PublicPluginUtilities();
		return $utils->verify_discourse_webhook_request( $data );
	}

	/**
	 * Creates a Discourse user through the API.
	 *
	 * @param \WP_User $user The WordPress user.
	 * @param bool     $require_activation Whether or not to require an activation email to be sent.
	 *
	 * @return int|\WP_Error
	 */
	public static function create_discourse_user( $user, $require_activation = true ) {
		$utils           = new PublicPluginUtilities();
		$api_credentials = $utils->get_api_credentials();

		if ( is_wp_error( $api_credentials ) ) {
			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		if ( empty( $user ) || empty( $user->ID ) || is_wp_error( $user ) ) {
			return new \WP_Error( 'wpdc_user_not_set_error', 'The Discourse user you are attempting to create does not exist on WordPress.' );
		}

		$require_activation = apply_filters( 'wpdc_auto_create_user_require_activation', $require_activation, $user );
		$create_user_url    = esc_url_raw( "{$api_credentials['url']}/users" );
		$username           = $user->user_login;
		$name               = $user->display_name;
		$email              = $user->user_email;
		$password           = wp_generate_password( 20 );
		$body               = array(
			'name'     => $name,
			'email'    => $email,
			'password' => $password,
			'username' => $username,
			'active'   => $require_activation ? 'false' : 'true',
			'approved' => 'true',
		);

		$user_data = static::discourse_request(
             $create_user_url, array(
				 'body'   => $body,
				 'method' => 'POST',
			 )
            );

		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		if ( empty( $user_data->success ) ) {

			return new \WP_Error( 'wpdc_response_error', $user_data->message );
		}

		if ( isset( $user_data->user_id ) ) {

			return $user_data->user_id;
		}

		return null;
	}

	const GROUP_SCHEMA = array(
		'id'                          => 'int',
		'name'                        => 'text',
		'full_name'                   => 'text',
		'user_count'                  => 'int',
		'mentionable_level'           => 'int',
		'messageable_level'           => 'int',
		'visibility_level'            => 'int',
		'primary_group'               => 'bool',
		'title'                       => 'text',
		'grant_trust_level'           => 'int',
		'incoming_email'              => 'text',
		'has_messages'                => 'bool',
		'flair_url'                   => 'text',
		'flair_bg_color'              => 'text',
		'flair_color'                 => 'text',
		'bio_raw'                     => 'textarea',
		'bio_cooked'                  => 'html',
		'bio_excerpt'                 => 'html',
		'public_admission'            => 'bool',
		'public_exit'                 => 'bool',
		'allow_membership_requests'   => 'bool',
		'default_notification_level'  => 'int',
		'membership_request_template' => 'text',
		'members_visibility_level'    => 'int',
		'publish_read_state'          => 'bool',
	);

	/**
	 * Gets the non-automatic Discourse groups and saves them in a transient.
	 *
	 * The transient has an expiry time of 10 minutes.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_discourse_groups() {
		$groups = get_transient( 'wpdc_non_automatic_groups' );
		if ( ! empty( $groups ) ) {
			return $groups;
		}

		$path                = '/groups';
		$response            = static::discourse_request( $path );
		$discourse_page_size = 36;

		if ( ! is_wp_error( $response ) && ! empty( $response->groups ) ) {
			$groups         = static::extract_groups( $response->groups );
			$total_groups   = $response->total_rows_groups;
			$load_more_path = $response->load_more_groups;

			if ( ( $total_groups > $discourse_page_size ) ) {
				$last_page = ( ceil( $total_groups / $discourse_page_size ) ) - 1;

				foreach ( range( 1, $last_page ) as $index ) {
					if ( $load_more_path ) {
						$response       = static::discourse_request( $load_more_path );
						$load_more_path = $response->load_more_groups;

						if ( ! is_wp_error( $response ) && ! empty( $response->groups ) ) {
						 	$groups = array_merge( $groups, static::extract_groups( $response->groups ) );
						}
					}
				}
			}

			set_transient( 'wpdc_non_automatic_groups', $groups, 10 * MINUTE_IN_SECONDS );

			return $groups;
		} else {
			return new \WP_Error( 'wpdc_response_error', 'No groups were returned from Discourse.' );
		}
	}

	/**
	 * Adds a user to a Discourse groups.
	 *
	 * @param int    $user_id The user's ID.
	 * @param string $group_names A comma separated list of group names.
	 *
	 * @return int|string
	 */
	public static function add_user_to_discourse_group( $user_id, $group_names ) {
		$options = self::get_options();
		if ( empty( $options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The add_user_to_discourse_group function can only be used when DiscourseConnect is enabled.' );
		}
		$user       = get_user_by( 'id', $user_id );
		$sso_params = self::get_sso_params(
			$user,
			array(
				'add_groups' => $group_names,
			)
		);

		return static::sync_sso_record( $sso_params );
	}

	/**
	 * Removes a user from Discourse groups.
	 *
	 * @param int    $user_id The user's ID.
	 * @param string $group_names A comma separated list of group names.
	 *
	 * @return int|string
	 */
	public static function remove_user_from_discourse_group( $user_id, $group_names ) {
		$options = self::get_options();
		if ( empty( $options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The remove_user_from_discourse_group function can only be used when DiscourseConnect is enabled.' );
		}
		$user       = get_user_by( 'id', $user_id );
		$sso_params = self::get_sso_params(
			$user,
			array(
				'remove_groups' => $group_names,
			)
		);

		return self::sync_sso_record( $sso_params );
	}

	/**
	 * Get the Discourse comment HTML so that it can be displayed without loading the comments template.
	 *
	 * @param int    $post_id The post ID to display the comments for.
	 * @param bool   $perform_sync Determines whether a comment sync is maybe performed when loading comments.
	 * @param bool   $force_sync Determines whether comment sync cache is bypassed when loading comments.
	 * @param string $comment_type Type of comment display.
	 *
	 * @return string
	 */
	public static function get_discourse_comments( $post_id, $perform_sync = true, $force_sync = false, $comment_type = null ) {
		$comment_formatter = new \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter();
		$comment_formatter->setup_options();
		$comment_formatter->setup_logger();

		return $comment_formatter->format( $post_id, $perform_sync, $force_sync, $comment_type );
	}

	/**
	 * Helper function for get_discourse_groups().
	 *
	 * @param array $raw_groups raw groups data array.
	 *
	 * @return array
	 */
	public static function extract_groups( $raw_groups ) {
		return array_reduce(
						 $raw_groups,
						function( $result, $group ) {
			if ( empty( $group->automatic ) ) {
									$result[] = static::discourse_munge( $group, static::GROUP_SCHEMA );
			}
			return $result;
		},
						array()
						);
	}

	/**
	 * Munge raw discourse data
	 *
	 * @param array  $data Data to munge.
	 * @param   schema $schema Schema to apply.
	 *
	 * @return object
	 */
	public static function discourse_munge( $data, $schema ) {
		$result = (object) array();

		foreach ( $data as $key => $value ) {
			if ( isset( $schema[ $key ] ) ) {
				if ( null !== $value ) {
					switch ( $schema[ $key ] ) {
						case 'int':
												$result->{$key} = intval( $value );
                            break;
						case 'bool':
												$result->{$key} = true == $value;
                            break;
						case 'text':
												$result->{$key} = sanitize_text_field( $value );
                            break;
						case 'textarea':
												$result->{$key} = sanitize_textarea_field( $value );
                            break;
						case 'html':
												$result->{$key} = $value;
                            break;
						default:
                            break;
					}
				} else {
					$result->{$key} = null;
				}
			}
		}

		return $result;
	}
}
