<?php
/**
 * Static utility functions used throughout the plugin.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Utilities;

/**
 * Class Utilities
 *
 * @package WPDiscourse
 */
class Utilities {

	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * @return array
	 */
	public static function get_options() {
		static $options = array();

		if ( empty( $options ) ) {
			$discourse_option_groups = get_option( 'discourse_option_groups' );
			if ( $discourse_option_groups ) {
				foreach ( $discourse_option_groups as $option_name ) {
					if ( get_option( $option_name ) ) {
						$option  = get_option( $option_name );
						$options = array_merge( $options, $option );
					}
				}

				$multisite_configuration_enabled = get_site_option( 'wpdc_multisite_configuration' );
				if ( 1 === intval( $multisite_configuration_enabled ) ) {
					$site_options = get_site_option( 'wpdc_site_options' );
					foreach ( $site_options as $key => $value ) {
						$options[ $key ] = $value;
					}
				}
			}
		}

		return apply_filters( 'wpdc_utilities_options_array', $options );
	}

	/**
	 * Validates the response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @param array $response The response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @return int
	 */
	public static function validate( $response ) {
		if ( empty( $response ) ) {

			return 0;
		} elseif ( is_wp_error( $response ) ) {

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}

	/**
	 * Gets the Discourse categories.
	 *
	 * @return \WP_Error|array
	 */
	public static function get_discourse_categories() {
		$options    = self::get_options();
		$categories = get_transient( 'wpdcu_discourse_categories' );

		if ( ! empty( $options['publish-category-update'] ) || ! $categories ) {
			$api_credentials = self::get_api_credentials();
			if ( is_wp_error( $api_credentials ) ) {

				return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
			}

			$base_url     = $api_credentials['url'];
			$api_username = $api_credentials['api_username'];
			$api_key      = $api_credentials['api_key'];

			$site_url = esc_url_raw( "{$base_url}/site.json" );

			$remote = wp_remote_get(
				$site_url,
				array(
					'headers' => array(
						'Api-Key'      => sanitize_key( $api_key ),
						'Api-Username' => sanitize_text_field( $api_username ),
					),
				)
			);

			if ( ! self::validate( $remote ) ) {

				return new \WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'categories', $remote ) ) {
				$categories           = $remote['categories'];
				$discourse_categories = array();
				foreach ( $categories as $category ) {
					if ( ( empty( $options['display-subcategories'] ) ) && array_key_exists( 'parent_category_id', $category ) ) {

						continue;
					}
					$current_category                     = array();
					$current_category['id']               = intval( $category['id'] );
					$current_category['name']             = sanitize_text_field( $category['name'] );
					$current_category['color']            = sanitize_key( $category['color'] );
					$current_category['text_color']       = sanitize_key( $category['text_color'] );
					$current_category['slug']             = sanitize_text_field( $category['slug'] );
					$current_category['topic_count']      = intval( $category['topic_count'] );
					$current_category['post_count']       = intval( $category['post_count'] );
					$current_category['description_text'] = sanitize_text_field( $category['description_text'] );
					$current_category['read_restricted']  = intval( $category['read_restricted'] );

					$discourse_categories[] = $current_category;
				}

				set_transient( 'wpdcu_discourse_categories', $discourse_categories, 10 * MINUTE_IN_SECONDS );

				return $discourse_categories;
			} else {

				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}// End if().

		return $categories;
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

		$api_credentials = self::get_api_credentials();
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
		$response           = wp_remote_post(
			$create_user_url,
			array(
				'method'  => 'POST',
				'body'    => array(
					'name'     => $name,
					'email'    => $email,
					'password' => $password,
					'username' => $username,
					'active'   => $require_activation ? 'false' : 'true',
					'approved' => 'true',
				),
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
				),
			)
		);

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( wp_remote_retrieve_response_code( $response ), 'An error was returned from Discourse when attempting to create a user.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $user_data->success ) ) {

			return new \WP_Error( 'wpdc_response_error', $user_data->message );
		}

		if ( isset( $user_data->user_id ) ) {

			return $user_data->user_id;
		}

		return new \WP_Error( wp_remote_retrieve_response_code( $response ), 'The Discourse user could not be created.' );
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
	 * Helper function for get_discourse_groups().
	 *
	 * @param array $raw_groups raw groups data array.
	 *
	 * @return array
	 */
	private static function extract_groups( $raw_groups ) {
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
	 * Gets the SSO parameters for a user.
	 *
	 * @param object $user The WordPress user.
	 * @param array  $sso_options An optional array of extra SSO parameters.
	 *
	 * @return array
	 */
	public static function get_sso_params( $user, $sso_options = array() ) {
		$plugin_options      = self::get_options();
		$user_id             = $user->ID;
		$require_activation  = get_user_meta( $user_id, 'discourse_email_not_verified', true ) ? true : false;
		$require_activation  = apply_filters( 'discourse_email_verification', $require_activation, $user );
		$force_avatar_update = ! empty( $plugin_options['force-avatar-update'] );
		$avatar_url          = get_avatar_url(
			$user_id,
			array(
				'default' => '404',
			)
		);
		$avatar_url          = apply_filters( 'wpdc_sso_avatar_url', $avatar_url, $user_id );

		if ( ! empty( $plugin_options['real-name-as-discourse-name'] ) ) {
			$first_name = ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name  = ! empty( $user->last_name ) ? $user->last_name : '';

			if ( $first_name || $last_name ) {
				$name = trim( $first_name . ' ' . $last_name );
			}
		}

		if ( empty( $name ) ) {
			$name = $user->display_name;
		}

		$params = array(
			'external_id'         => $user_id,
			'username'            => $user->user_login,
			'email'               => $user->user_email,
			'require_activation'  => $require_activation ? 'true' : 'false',
			'name'                => $name,
			'bio'                 => $user->description,
			'avatar_url'          => $avatar_url,
			'avatar_force_update' => $force_avatar_update ? 'true' : 'false',
		);

		if ( ! empty( $sso_options ) ) {
			foreach ( $sso_options as $option_key => $option_value ) {
				$params[ $option_key ] = $option_value;
			}
		}

		return apply_filters( 'wpdc_sso_params', $params, $user );
	}

	/**
	 * Syncs a user with Discourse through SSO.
	 *
	 * @param array $sso_params The sso params to sync.
	 *
	 * @return int|string|\WP_Error
	 */
	public static function sync_sso_record( $sso_params ) {
		$plugin_options = self::get_options();
		if ( empty( $plugin_options['enable-sso'] ) ) {

			return new \WP_Error( 'wpdc_sso_error', 'The sync_sso_record function can only be used when SSO is enabled.' );
		}
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$url         = $api_credentials['url'] . '/admin/users/sync_sso';
		$sso_secret  = $plugin_options['sso-secret'];
		$sso_payload = base64_encode( http_build_query( $sso_params ) );
		// Create the signature for Discourse to match against the payload.
		$sig = hash_hmac( 'sha256', $sso_payload, $sso_secret );

		$response = wp_remote_post(
			esc_url_raw( $url ),
			array(
				'body'    => array(
					'sso' => $sso_payload,
					'sig' => $sig,
				),
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
				),
			)
		);

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'An error was returned from Discourse while trying to sync the sso record.' );
		}

		$discourse_user = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $discourse_user->id ) ) {
			$wordpress_user_id = $sso_params['external_id'];
			update_user_meta( $wordpress_user_id, 'discourse_sso_user_id', intval( $discourse_user->id ) );
			update_user_meta( $wordpress_user_id, 'discourse_username', sanitize_text_field( $discourse_user->username ) );
		}

		return wp_remote_retrieve_response_code( $response );
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

			return new \WP_Error( 'wpdc_sso_error', 'The add_user_to_discourse_group function can only be used when SSO is enabled.' );
		}
		$user       = get_user_by( 'id', $user_id );
		$sso_params = self::get_sso_params(
			$user,
			array(
				'add_groups' => $group_names,
			)
		);

		return self::sync_sso_record( $sso_params );
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

			return new \WP_Error( 'wpdc_sso_error', 'The remove_user_from_discourse_group function can only be used when SSO is enabled.' );
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
	 * Get a Discourse user object.
	 *
	 * @param int  $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public static function get_discourse_user( $user_id, $match_by_email = false ) {
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse connection options are not properly configured.' );
		}

		$external_user_url = esc_url_raw( "{$api_credentials['url']}/users/by-external/{$user_id}.json" );

		$response = wp_remote_get(
			$external_user_url,
			array(
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
				),
			)
		);

		if ( self::validate( $response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $body->user ) ) {

				return $body->user;
			}
		}

		if ( $match_by_email ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {

				return self::get_discourse_user_by_email( $user->user_email );
			} else {

				return new \WP_Error( 'wpdc_param_error', 'There is no WordPress user with the supplied id.' );
			}
		}

		return new \WP_Error( 'wpdc_response_error', 'The Discourse user could not be retrieved.' );
	}

	/**
	 * Gets a Discourse user by their email address.
	 *
	 * @param string $email The email address to search for.
	 *
	 * @return object \WP_Error
	 */
	public static function get_discourse_user_by_email( $email ) {
		$api_credentials = self::get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$users_url = "{$api_credentials['url']}/admin/users/list/all.json";
		$users_url = esc_url_raw(
			add_query_arg(
				array(
					'email'  => rawurlencode_deep( $email ),
					'filter' => rawurlencode_deep( $email ),
				),
				$users_url
			)
		);

		$response = wp_remote_get(
			$users_url,
			array(
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
				),
			)
		);
		if ( self::validate( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			// The reqest returns a valid response even if the user isn't found, so check for empty.
			if ( ! empty( $body ) && ! empty( $body[0] ) ) {

				return $body[0];
			} else {

				// A valid response was returned, but the user wasn't found.
				return new \WP_Error( 'wpdc_response_error', 'The user could not be retrieved by their email address.' );
			}
		} else {

			return new \WP_Error( 'wpdc_response_error', 'An invalid response was returned when trying to find the user by email address.' );
		}
	}

	/**
	 * Get the Discourse comment HTML so that it can be displayed without loading the comments template.
	 *
	 * @param int $post_id The post ID to display the comments for.
	 *
	 * @return string
	 */
	public static function get_discourse_comments( $post_id ) {
		$comment_formatter = new \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter();

		return $comment_formatter->format( $post_id );
	}

	/**
	 * Gets the Discourse API credentials from the options array.
	 *
	 * @return array|\WP_Error
	 */
	protected static function get_api_credentials() {
		$options      = self::get_options();
		$url          = ! empty( $options['url'] ) ? $options['url'] : null;
		$api_key      = ! empty( $options['api-key'] ) ? $options['api-key'] : null;
		$api_username = ! empty( $options['publish-username'] ) ? $options['publish-username'] : null;

		if ( ! ( $url && $api_key && $api_username ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse configuration options have not been set.' );
		}

		return array(
			'url'          => $url,
			'api_key'      => $api_key,
			'api_username' => $api_username,
		);
	}

	/**
	 * Verify that the request originated from a Discourse webhook and the the secret keys match.
	 *
	 * @param \WP_REST_Request $data The WP_REST_Request object.
	 *
	 * @return \WP_Error|\WP_REST_Request
	 */
	public static function verify_discourse_webhook_request( $data ) {
		$options = self::get_options();
		// The X-Discourse-Event-Signature consists of 'sha256=' . hamc of raw payload.
		// It is generated by computing `hash_hmac( 'sha256', $payload, $secret )`.
		$sig = substr( $data->get_header( 'X-Discourse-Event-Signature' ), 7 );
		if ( $sig ) {
			$payload = $data->get_body();
			// Key used for verifying the request - a matching key needs to be set on the Discourse webhook.
			$secret = ! empty( $options['webhook-secret'] ) ? $options['webhook-secret'] : '';

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
						default:
												;
                            break;
					}
				} else {
					$result->{$key} = null;
				}
			}
		}

		return $result;
	}

	/**
	 * Perform a Discourse request
	 *
	 * @param string $path Discourse request path.
	 *
	 * @return array|\WP_Error|void
	 */
	public static function discourse_request( $path ) {
		if ( ! $path ) {
			return; }

		$api_credentials = self::get_api_credentials();

		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$response = wp_remote_get(
			esc_url_raw( $api_credentials['url'] . $path ),
			array(
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
					'Accept'       => 'application/json',
				),
			)
		);

		if ( ! self::validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'An invalid response was returned from Discourse when retrieving Discourse groups data' );
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}
}
