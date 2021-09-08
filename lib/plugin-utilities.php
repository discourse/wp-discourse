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
	 * Gets the 'discourse_configurable_text' options.
	 *
	 * @param string $option The option key.
	 *
	 * @return string
	 */
	protected static function get_text_options( $option ) {
		$text_options = get_option( 'discourse_configurable_text' );
		$text         = ! empty( $text_options[ $option ] ) ? $text_options[ $option ] : '';
		return self::apply_text_translations( $text, $option );
	}

	/**
	 * Applies translations of configurable text.
	 *
	 * @param string $text The text to be translated.
	 * @param string $option The text option to be translated.
	 *
	 * @return string
	 */
	protected static function apply_text_translations( $text, $option ) {
		// See https://wpml.org/wpml-hook/wpml_translate_single_string/.
		$text = apply_filters( 'wpml_translate_single_string', $text, 'wp-discourse', $option );

		return $text;
	}

	/**
	 * Registers translations of configurable text.
	 *
	 * @param string $text The text to be translated.
	 * @param string $option The text option to be translated.
	 *
	 * @return void
	 */
	 protected static function register_text_translations( $text, $option ) {
		 // See https://wpml.org/wpml-hook/wpml_register_single_string/.
		 do_action( 'wpml_register_single_string', 'wp-discourse', $option, $text );
	 }

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
		$api_credentials = $this->get_api_credentials();
		if ( is_wp_error( $api_credentials ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The Discourse Connection options are not properly configured.' );
		}

		$api_username = sanitize_text_field( $api_credentials['api_username'] );

		$url      = esc_url_raw( "{$api_credentials['url']}/users/{$api_username}.json" );
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => $api_username,
				),
			)
		);

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
		$options    = isset( $this->options ) ? $this->options : $this->get_options();
		$categories = get_transient( 'wpdc_discourse_categories' );

		if ( ! empty( $options['publish-category-update'] ) || ! $categories ) {
			$api_credentials = $this->get_api_credentials();
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

			if ( ! $this->validate( $remote ) ) {

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

				// Note that setting the cache to 0 will disable transient expiration.
				$category_cache_period = apply_filters( 'wpdc_category_cache_minutes', 10 );

				set_transient( 'wpdc_discourse_categories', $discourse_categories, intval( $category_cache_period ) * MINUTE_IN_SECONDS );

				return $discourse_categories;
			} else {

				return new \WP_Error( 'key_not_found', 'The categories key was not found in the response from Discourse.' );
			}
		}// End if().

		return $categories;
	}

	/**
	 * Returns a category from the wpdc_discourse_categories array if it is available.
	 *
	 * @param int $category_id The id of the post's Discourse category.
	 *
	 * @return mixed|\WP_Error|null
	 */
	protected function get_discourse_category_by_id( $category_id ) {
		$category_result = $this->get_discourse_categories();
		if ( ! is_wp_error( $category_result ) ) {
			foreach ( $category_result as $category ) {
				if ( intval( $category_id ) === $category['id'] ) {

					return $category;
				}
			}

			return null;
		} else {
			return $category_result;
		}
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

		$users_url = esc_url_raw( "{$api_credentials['url']}/admin/users/list/all.json" );
		$users_url = add_query_arg(
			array(
				'email'  => rawurlencode_deep( $email ),
				'filter' => rawurlencode_deep( $email ),
			),
			$users_url
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

		$response = wp_remote_get(
			$topic_url,
			array(
				'headers' => array(
					'Api-Key'      => sanitize_key( $api_credentials['api_key'] ),
					'Api-Username' => sanitize_text_field( $api_credentials['api_username'] ),
				),
			)
		);

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
		$options      = isset( $this->options ) ? $this->options : $this->get_options();
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
