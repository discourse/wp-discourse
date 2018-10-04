<?php
/**
 * Shared Utility methods.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Shared;

/**
 * Trait PluginUtilities
 */
trait PluginUtilities {

	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * @return array
	 */
	protected function get_options() {
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
	 * Checks the connection status to Discourse.
	 *
	 * @return int|\WP_Error
	 */
	public function check_connection_status() {
		$options      = $this->get_options();
		$url          = ! empty( $options['url'] ) ? $options['url'] : null;
		$api_key      = ! empty( $options['api-key'] ) ? $options['api-key'] : null;
		$api_username = ! empty( $options['publish-username'] ) ? $options['publish-username'] : null;

		if ( ! ( $url && $api_key && $api_username ) ) {

			return 0;
		}

		$url = add_query_arg(
			array(
				'api_key'      => $api_key,
				'api_username' => $api_username,
			), $url . '/users/' . $api_username . '.json'
		);

		$url      = esc_url_raw( $url );
		$response = wp_remote_get( $url );

		return $this->validate( $response );
	}

	/**
	 * Validates the response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @param array $response The response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @return int
	 */
	protected function validate( $response ) {
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
	 * @return array|\WP_Error
	 */
	protected function get_discourse_categories() {
		$options      = $this->get_options();
		$force_update = false;

		$categories = get_option( 'wpdc_discourse_categories' );

		if ( ! empty( $options['publish-category-update'] ) || ! $categories ) {
			$force_update = true;
		}

		if ( $force_update ) {
			$base_url     = ! empty( $options['url'] ) ? $options['url'] : null;
			$api_key      = ! empty( $options['api-key'] ) ? $options['api-key'] : null;
			$api_username = ! empty( $options['publish-username'] ) ? $options['publish-username'] : null;

			if ( ! ( $base_url && $api_key && $api_username ) ) {

				return new \WP_Error( 'discourse_configuration_error', 'The Discourse connection options have not been configured.' );
			}

			$site_url = esc_url_raw( "{$base_url}/site.json" );
			$site_url = add_query_arg(
				array(
					'api_key'      => $api_key,
					'api_username' => $api_username,
				), $site_url
			);

			$remote = wp_remote_get( $site_url );

			if ( ! $this->validate( $remote ) ) {

				return new \WP_Error( 'connection_not_established', 'There was an error establishing a connection with Discourse' );
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'categories', $remote ) ) {
				$categories = $remote['categories'];
				if ( empty( $options['display-subcategories'] ) ) {
					foreach ( $categories as $category => $values ) {
						if ( array_key_exists( 'parent_category_id', $values ) ) {
							unset( $categories[ $category ] );
						}
					}
				}
				update_option( 'wpdc_discourse_categories', $categories );
			} else {

				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}// End if().

		return $categories;
	}

	/**
	 * Get a Discourse user object.
	 *
	 * @param int  $user_id The WordPress user_id.
	 * @param bool $match_by_email Whether or not to attempt to get the user by their email address.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function get_discourse_user( $user_id, $match_by_email = false ) {
		$api_credentials = $this->get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse connection options are not properly configured.' );
		}

		$external_user_url = "{$api_credentials['url']}/users/by-external/{$user_id}.json";
		$external_user_url = esc_url_raw(
			add_query_arg(
				array(
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				), $external_user_url
			)
		);

		$response = wp_remote_get( $external_user_url );

		if ( $this->validate( $response ) ) {

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $body->user ) ) {

				return $body->user;
			}
		}

		if ( $match_by_email ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {

				return $this->get_discourse_user_by_email( $user->user_email );
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
	protected function get_discourse_user_by_email( $email ) {
		$api_credentials = $this->get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$users_url = "{$api_credentials['url']}/admin/users/list/all.json";
		$users_url = esc_url_raw(
			add_query_arg(
				array(
					'email'        => rawurlencode_deep( $email ),
					'filter'       => rawurlencode_deep( $email ),
					'api_key'      => $api_credentials['api_key'],
					'api_username' => $api_credentials['api_username'],
				), $users_url
			)
		);

		$response = wp_remote_get( $users_url );
		if ( $this->validate( $response ) ) {
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
	 * Gets a Discourse topic's json from its URL.
	 *
	 * @param string $topic_url The Discourse topic URL.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function get_discourse_topic( $topic_url ) {
		$api_credentials = $this->get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$topic_url = esc_url_raw( "{$topic_url}.json" );
		$topic_url = add_query_arg(
			array(
				'api_key'      => $api_credentials['api_key'],
				'api_username' => $api_credentials['api_username'],
			), $topic_url
		);

		$response = wp_remote_get( $topic_url );

		if ( ! $this->validate( $response ) ) {

			return new \WP_Error( 'wpdc_response_error', 'The topic could not be retrieved from Discourse.' );
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Gets the Discourse API credentials from the options array.
	 *
	 * @return array|\WP_Error
	 */
	protected function get_api_credentials() {
		$options      = $this->get_options();
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
	 * Used to transfer data from the 'discourse' options array to the new option_group arrays.
	 *
	 * @param string $old_option The name of the old option_group.
	 * @param array  $transferable_option_groups The array of transferable_option_group names.
	 */
	protected function transfer_options( $old_option, $transferable_option_groups ) {
		$discourse_options = get_option( $old_option );

		foreach ( $transferable_option_groups as $group_name ) {
			$this->transfer_option_group( $discourse_options, $group_name );
		}
	}

	/**
	 * Transfers saved option values to the new options group.
	 *
	 * @param array  $existing_options The old 'discourse' options array.
	 * @param string $group_name The name of the current options group.
	 */
	protected function transfer_option_group( $existing_options, $group_name ) {
		$transferred_options = array();

		foreach ( $this->$group_name as $key => $value ) {
			if ( isset( $existing_options[ $key ] ) ) {
				$transferred_options[ $key ] = $existing_options[ $key ];
			}
		}

		add_option( $group_name, $transferred_options );
	}
}
