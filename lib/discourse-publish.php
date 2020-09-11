<?php
/**
 * Publishes a post to Discourse.
 *
 * @package WPDicourse
 */

namespace WPDiscourse\DiscoursePublish;

use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Shared\PluginUtilities;
use WPDiscourse\Shared\TemplateFunctions;

/**
 * Class DiscoursePublish
 */
class DiscoursePublish {
	use PluginUtilities;
	use TemplateFunctions;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * An email_notification object that has a publish_failure_notification method.
	 *
	 * @access protected
	 * @var \WPDiscourse\EmailNotification\EmailNotification
	 */
	protected $email_notifier;

	/**
	 * DiscoursePublish constructor.
	 *
	 * @param object $email_notifier An object for sending an email verification notice.
	 */
	public function __construct( $email_notifier ) {
		$this->email_notifier = $email_notifier;

		add_action( 'init', array( $this, 'setup_options' ) );
		// Priority is set to 13 so that 'publish_post_after_save' is called after the meta-box is saved.
		add_action( 'save_post', array( $this, 'publish_post_after_save' ), 13, 2 );
		add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Published a post to Discourse after it has been saved.
	 *
	 * @param int    $post_id The id of the post that has been saved.
	 * @param object $post The Post object.
	 *
	 * @return null
	 */
	public function publish_post_after_save( $post_id, $post ) {
		$plugin_unconfigured    = empty( $this->options['url'] ) || empty( $this->options['api-key'] ) || empty( $this->options['publish-username'] );
		$publish_status_not_set = 'publish' !== get_post_status( $post_id );
		$publish_private        = apply_filters( 'wpdc_publish_private_post', false, $post_id );
		if ( wp_is_post_revision( $post_id )
			 || ( $publish_status_not_set && ! $publish_private )
			 || $plugin_unconfigured
			 || empty( $post->post_title )
			 || ! $this->is_valid_sync_post_type( $post_id )
		) {

			return null;
		}

		// Clear existing publishing errors.
		delete_post_meta( $post_id, 'wpdc_publishing_error' );

		// If the auto-publish option is enabled publish unpublished topics, unless the setting has been overridden.
		$auto_publish_overridden = intval( get_post_meta( $post_id, 'wpdc_auto_publish_overridden', true ) ) === 1;
		$auto_publish            = ! $auto_publish_overridden && ! empty( $this->options['auto-publish'] );

		$publish_to_discourse = get_post_meta( $post_id, 'publish_to_discourse', true ) || $auto_publish;
		$publish_to_discourse = apply_filters( 'wpdc_publish_after_save', $publish_to_discourse, $post_id, $post );

		$force_publish_enabled = ! empty( $this->options['force-publish'] );
		$force_publish_post    = false;
		if ( $force_publish_enabled ) {
			// The Force Publish setting can't be easily supported with both the Block and Classic editors. The $is_rest_request
			// variable is used to only allow the Force Publish setting to be respected for posts published with the Block Editor.
			$is_rest_request       = defined( 'REST_REQUEST' ) && REST_REQUEST;
			$force_publish_max_age = ! empty( $this->options['force-publish-max-age'] ) ? intval( $this->options['force-publish-max-age'] ) : 0;
			$min_date              = date_create()->modify( "-{$force_publish_max_age} day" )->format( 'U' );
			$post_time             = strtotime( $post->post_date );

			if ( ( ( 0 === $force_publish_max_age ) || $post_time >= $min_date ) && $is_rest_request ) {
				$force_publish_post = true;
				update_post_meta( $post_id, 'publish_post_category', intval( $this->options['publish-category'] ) );
			}
		}

		$already_published      = get_post_meta( $post_id, 'discourse_post_id', true );
		$update_discourse_topic = get_post_meta( $post_id, 'update_discourse_topic', true );
		$title                  = $this->sanitize_title( $post->post_title );
		$title                  = apply_filters( 'wpdc_publish_format_title', $title, $post_id );

		if ( $force_publish_post || ( ! $already_published && $publish_to_discourse ) || $update_discourse_topic ) {
			$this->sync_to_discourse( $post_id, $title, $post->post_content );
		}

		return null;
	}

	/**
	 * For publishing by xmlrpc.
	 *
	 * Hooks into 'xmlrpc_publish_post'. Publishing through this hook is disabled. This is to prevent
	 * posts being inadvertently published to Discourse when they are edited using blogging software.
	 * This can be overridden by hooking into the `wp_discourse_before_xmlrpc_publish` filter and setting
	 * `$publish_to_discourse` to true based on some condition - testing for the presence of a tag can
	 * work for this.
	 *
	 * @param int $post_id The post id.
	 */
	public function xmlrpc_publish_post_to_discourse( $post_id ) {
		$post                 = get_post( $post_id );
		$post_is_published    = 'publish' === get_post_status( $post_id );
		$publish_to_discourse = false;
		$publish_to_discourse = apply_filters( 'wp_discourse_before_xmlrpc_publish', $publish_to_discourse, $post );
		$title                = $this->sanitize_title( $post->post_title );
		$title                = apply_filters( 'wpdc_publish_format_title', $title, $post_id );

		if ( $publish_to_discourse && $post_is_published && $this->is_valid_sync_post_type( $post_id ) && ! empty( $title ) ) {
			update_post_meta( $post_id, 'publish_to_discourse', 1 );
			$this->sync_to_discourse( $post_id, $title, $post->post_content );
		} elseif ( $post_is_published && ! empty( $this->options['auto-publish'] ) ) {
			$this->email_notifier->publish_failure_notification(
				$post,
				array(
					'location' => 'after_xmlrpc_publish',
				)
			);
		}
	}

	/**
	 * Calls `sync_to_discourse_work` after getting the lock.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title The title.
	 * @param string $raw The raw content of the post.
	 */
	public function sync_to_discourse( $post_id, $title, $raw ) {
		global $wpdb;

		// this avoids a double sync, just 1 is allowed to go through at a time.
		$got_lock = $wpdb->get_row( "SELECT GET_LOCK('discourse_sync_lock', 0) got_it" );
		if ( 1 === intval( $got_lock->got_it ) ) {
			$this->sync_to_discourse_work( $post_id, $title, $raw );
			$wpdb->get_results( "SELECT RELEASE_LOCK('discourse_sync_lock')" );
		}
	}

	/**
	 * Syncs a post to Discourse.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title The post title.
	 * @param string $raw The content of the post.
	 *
	 * @return null
	 */
	protected function sync_to_discourse_work( $post_id, $title, $raw ) {
		$options                     = $this->options;
		$discourse_id                = get_post_meta( $post_id, 'discourse_post_id', true );
		$current_post                = get_post( $post_id );
		$author_id                   = $current_post->post_author;
		$use_full_post               = ! empty( $options['full-post-content'] );
		$use_multisite_configuration = is_multisite() && ! empty( $options['multisite-configuration-enabled'] );
		$add_featured_link           = ! empty( $options['add-featured-link'] );
		$permalink                   = get_permalink( $post_id );

		if ( $use_full_post ) {
			$blocks = parse_blocks( $raw );
			$parsed = '';
			foreach ( $blocks as $block ) {
				if ( 'core/image' === $block['blockName'] || 'core/gallery' === $block['blockName'] ) {
					$parsed .= $this->extract_images_from_html( $block['innerHTML'] );
				} elseif ( 'core-embed/youtube' === $block['blockName'] || 'core-embed/vimeo' === $block['blockName'] ) {
					if ( ! empty( $block['attrs'] ) && ! empty( $block['attrs']['url'] ) ) {
						$video_url = esc_url( $block['attrs']['url'] );
						$parsed   .= "\r\n\r\n{$video_url}\r\n\r\n";
					}
				} else {
					$parsed .= apply_filters( 'the_content', render_block( $block ) );
				}
			}
			$parsed  = $this->remove_html_comments( $parsed );
			$excerpt = apply_filters( 'wp_discourse_excerpt', $parsed, $options['custom-excerpt-length'], $use_full_post );
		} else {
			if ( has_excerpt( $post_id ) ) {
				$wp_excerpt = apply_filters( 'get_the_excerpt', $current_post->post_excerpt );
				$excerpt    = apply_filters( 'wp_discourse_excerpt', $wp_excerpt, $options['custom-excerpt-length'], $use_full_post );
			}

			// Check empty() here in case the excerpt has been set to an empty string.
			if ( empty( $excerpt ) ) {
				$excerpt = apply_filters( 'the_content', $raw );
				$excerpt = apply_filters( 'wp_discourse_excerpt', wp_trim_words( $excerpt, $options['custom-excerpt-length'] ), $options['custom-excerpt-length'], $use_full_post );
			}
		}

		// Trim to keep the Discourse markdown parser from treating this as code.
		$baked  = trim( Templates::publish_format_html( $post_id ) );
		$baked  = str_replace( '{excerpt}', $excerpt, $baked );
		$baked  = str_replace( '{blogurl}', $permalink, $baked );
		$author = get_the_author_meta( 'display_name', $author_id );
		$baked  = str_replace( '{author}', $author, $baked );
		$thumb  = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
		if ( ! empty( $thumb ) ) {
			$baked = str_replace( '{thumbnail}', '![image](' . $thumb['0'] . ')', $baked );
		}
		$featured = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
		if ( ! empty( $featured ) ) {
			$baked = str_replace( '{featuredimage}', '![image](' . $featured['0'] . ')', $baked );
		}
		$username = apply_filters( 'wpdc_discourse_username', get_the_author_meta( 'discourse_username', $current_post->post_author ), $author_id );
		if ( ! $username || strlen( $username ) < 2 ) {
			$username = $options['publish-username'];
		}

		// Get publish category of a post.
		$publish_post_category = get_post_meta( $post_id, 'publish_post_category', true );
		// This would be used if a post is published through XML-RPC.
		$default_category = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$category         = ! empty( $publish_post_category ) ? $publish_post_category : $default_category;
		$category         = apply_filters( 'wpdc_publish_post_category', $category, $post_id );
		if ( ! empty( $this->options['allow-tags'] ) ) {
			$tags = get_post_meta( $post_id, 'wpdc_topic_tags', true );
			// For the Block Editor, tags are being set through the API. For this case, it's easier to handle the data as a string.
			if ( ! is_array( $tags ) ) {
				$tags = explode( ',', $tags );
			}
			$tags_param = $this->tags_param( $tags );
		} else {
			$tags_param = '';
		}

		// The post hasn't been published to Discourse yet.
		if ( ! $discourse_id > 0 ) {
			// Unlisted has been moved from post metadata to a site option. This is awkward for now.
			$unlisted_post   = get_post_meta( $post_id, 'wpdc_unlisted_topic', true );
			$unlisted_option = $this->options['publish-as-unlisted'];
			$unlisted        = apply_filters( 'wpdc_publish_unlisted', ! empty( $unlisted_post ) || ! empty( $unlisted_option ), $current_post, $post_id );
			if ( $unlisted ) {
				update_post_meta( $post_id, 'wpdc_unlisted_topic', 1 );
			}

			$data         = array(
				'embed_url'        => $permalink,
				'featured_link'    => $add_featured_link ? $permalink : null,
				'title'            => $title,
				'raw'              => $baked,
				'category'         => $category,
				'skip_validations' => 'true',
				'auto_track'       => ( ! empty( $options['auto-track'] ) ? 'true' : 'false' ),
				'visible'          => $unlisted ? 'false' : 'true',
			);
			$url          = $options['url'] . '/posts';
			$post_options = array(
				'timeout' => 30,
				'method'  => 'POST',
				'headers' => array(
					'Api-Key'      => sanitize_key( $options['api-key'] ),
					'Api-Username' => sanitize_text_field( $username ),
				),
				'body'    => http_build_query( $data ) . $tags_param,
			);

		} else {
			// The post has already been published.
			$data         = array(
				'title'            => $title,
				'post[raw]'        => $baked,
				'skip_validations' => 'true',
			);
			$url          = $options['url'] . '/posts/' . $discourse_id;
			$post_options = array(
				'timeout' => 30,
				'method'  => 'PUT',
				'headers' => array(
					'Api-Key'      => sanitize_key( $options['api-key'] ),
					'Api-Username' => sanitize_text_field( $username ),
				),
				'body'    => http_build_query( $data ),
			);
		}// End if().

		$result = wp_remote_post( esc_url_raw( $url ), $post_options );

		if ( ! $this->validate( $result ) ) {
			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
				$error_code    = null;
				update_post_meta( $post_id, 'wpdc_publishing_error', $error_message );
			} else {
				$result_body = json_decode( wp_remote_retrieve_body( $result ) );
				if ( ! empty( $result_body ) && ! empty( $result_body->errors ) && ! empty( $result_body->errors[0] ) ) {
					$error_message = $result_body->errors[0];
					$error_code    = null;
				} else {
					$error_message = wp_remote_retrieve_response_message( $result );
					$error_code    = intval( wp_remote_retrieve_response_code( $result ) );
				}
				// This is a fix for a bug that was introduced by not setting the wpdc_auto_publish_overridden post_metadata
				// when posts are unlined from Discourse. That metadata is now being set. This fix is for dealing with
				// previously unlinked posts.
				if ( 'Embed url has already been taken' === $error_message ) {
					update_post_meta( $post_id, 'wpdc_auto_publish_overridden', 1 );
				}
				update_post_meta( $post_id, 'wpdc_publishing_error', sanitize_text_field( $error_message ) );
			}

			// Delete to avoid attempts to republish posts that are returning errors.
			delete_post_meta( $post_id, 'publish_to_discourse' );

			$this->create_bad_response_notifications( $current_post, $post_id, $error_message, $error_code );

			return new \WP_Error( 'discourse_publishing_response_error', 'An invalid response was returned from Discourse after attempting to publish a post.' );
		}

		$body = json_decode( wp_remote_retrieve_body( $result ) );
		// Check for queued posts. We have already determined that a status code of `200` was returned. A post queued by Discourse will have an empty body.
		if ( empty( $body ) ) {
			update_post_meta( $post_id, 'wpdc_publishing_error', 'queued_topic' );

			return new \WP_Error( 'discourse_publishing_response_error', 'The published post has been added to the Discourse approval queue.' );
		}

		// The response when a topic is first created.
		if ( ! empty( $body->id ) && ! empty( $body->topic_slug ) && ! empty( $body->topic_id ) ) {
			$discourse_id = intval( $body->id );
			$topic_slug   = sanitize_text_field( $body->topic_slug );
			$topic_id     = intval( $body->topic_id );

			add_post_meta( $post_id, 'discourse_post_id', $discourse_id, true );
			add_post_meta( $post_id, 'discourse_topic_id', $topic_id, true );
			add_post_meta( $post_id, 'discourse_permalink', esc_url_raw( $options['url'] . '/t/' . $topic_slug . '/' . $topic_id ), true );
			update_post_meta( $post_id, 'publish_post_category', $category );

			// Used for resetting the error notification, if one was being displayed.
			update_post_meta( $post_id, 'wpdc_publishing_response', 'success' );
			if ( $use_multisite_configuration ) {
				$blog_id = intval( get_current_blog_id() );
				$this->save_topic_blog_id( $body->topic_id, $blog_id );
			}

			$pin_until = get_post_meta( $post_id, 'wpdc_pin_until', true );
			if ( ! empty( $pin_until ) ) {
				$pin_response = $this->pin_discourse_topic( $post_id, $topic_id, $pin_until );

				return $pin_response;
			}

			// The topic has been created and its associated post's metadata has been updated.
			return null;

		} elseif ( ! empty( $body->post ) ) {

			$discourse_post = $body->post;
			$topic_slug     = ! empty( $discourse_post->topic_slug ) ? sanitize_text_field( $discourse_post->topic_slug ) : null;
			$topic_id       = ! empty( $discourse_post->topic_id ) ? intval( $discourse_post->topic_id ) : null;

			// Handles deleted topics for recent versions of Discourse.
			if ( ! empty( $discourse_post->deleted_at ) ) {
				update_post_meta( $post_id, 'wpdc_publishing_error', 'deleted_topic' );

				return new \WP_Error( 'discourse_publishing_response_error', 'The Discourse topic associated with this post has been deleted.' );
			}

			if ( $topic_slug && $topic_id ) {
				$discourse_topic_url = esc_url_raw( $options['url'] . '/t/' . $topic_slug . '/' . $topic_id );
				update_post_meta( $post_id, 'discourse_permalink', $discourse_topic_url );
				update_post_meta( $post_id, 'discourse_topic_id', $topic_id );
				update_post_meta( $post_id, 'wpdc_publishing_response', 'success' );
				// Allows the publish_post_category to be set by clicking the "Update Discourse Topic" button.
				update_post_meta( $post_id, 'publish_post_category', $category );

				if ( $use_multisite_configuration ) {
					// Used when use_multisite_configuration is enabled, if an existing post is not yet associated with a topic_id/blog_id.
					if ( ! $this->topic_blog_id_exists( $topic_id ) ) {
						$blog_id = get_current_blog_id();
						$this->save_topic_blog_id( $topic_id, $blog_id );
					}
				}

				// Update the topic's featured_link property.
				if ( ! empty( $options['add-featured-link'] ) ) {
					$data         = array(
						'featured_link' => $permalink,
					);
					$post_options = array(
						'timeout' => 30,
						'method'  => 'PUT',
						'headers' => array(
							'Api-Key'      => sanitize_key( $options['api-key'] ),
							'Api-Username' => sanitize_text_field( $username ),
						),
						'body'    => http_build_query( $data ),
					);

					$result = wp_remote_post( esc_url_raw( $discourse_topic_url ), $post_options );
					if ( ! $this->validate( $result ) ) {

						return new \WP_Error( 'discourse_publishing_response_error', 'An error was returned when attempting to update the Discourse featured link.' );
					}
				}

				// The topic has been updated, and its associated post's metadata has been updated.
				return null;
			} else {
				$this->create_bad_response_notifications( $current_post, $post_id );

				return new \WP_Error( 'discourse_publishing_response_error', 'An invalid response was returned from Discourse after attempting to publish a post.' );
			}// End if().
		}// End if().

		// Neither the 'id' or the 'post' property existed on the response body.
		$this->create_bad_response_notifications( $current_post, $post_id );

		return new \WP_Error( 'discourse_publishing_response_error', 'An invalid response was returned from Discourse after attempting to publish a post.' );
	}

	/**
	 * Generates the tags parameter in the form that is required by Discourse.
	 *
	 * @param array $tags The array of tags for the topic.
	 *
	 * @return string
	 */
	protected function tags_param( $tags ) {
		$tags_string = '';
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$tag = sanitize_text_field( $tag );
				if ( empty( $tag ) ) {

					break;
				}
				$tags_string .= '&tags' . rawurlencode( '[]' ) . "={$tag}";
			}
		}

		return $tags_string;
	}

	/**
	 * Pins a Discourse topic.
	 *
	 * @param int    $post_id The WordPress id of the pinned post.
	 * @param int    $topic_id The Discourse topic_id of the pinned post.
	 * @param string $pin_until A string that sets the pin_until date.
	 *
	 * @return null|\WP_Error
	 */
	protected function pin_discourse_topic( $post_id, $topic_id, $pin_until ) {
		$status_url   = esc_url_raw( $this->options['url'] . "/t/$topic_id/status" );
		$data         = array(
			'status'  => 'pinned',
			'enabled' => 'true',
			'until'   => $pin_until,
		);
		$post_options = array(
			'timeout' => 30,
			'method'  => 'PUT',
			'headers' => array(
				'Api-Key'      => $this->options['api-key'],
				'Api-Username' => $this->options['publish-username'],
			),
			'body'    => http_build_query( $data ),
		);

		$response = wp_remote_post( $status_url, $post_options );

		if ( ! $this->validate( $response ) ) {

			return new \WP_Error( 'discourse_publishing_response_error', 'The topic could not be pinned on Discourse.' );
		}

		delete_post_meta( $post_id, 'wpdc_pin_until' );

		return null;
	}

	/**
	 * Creates an admin_notice and calls the publish_failure_notification method after a bad response is returned from Discourse.
	 *
	 * @param \WP_Post $current_post The post for which the notifications are being created.
	 * @param int      $post_id The current post id.
	 * @param string   $error_message The error message returned from the request.
	 * @param int      $error_code The error code returned from the request.
	 */
	protected function create_bad_response_notifications( $current_post, $post_id, $error_message = '', $error_code = null ) {
		update_post_meta( $post_id, 'wpdc_publishing_response', 'error' );
		$this->email_notifier->publish_failure_notification(
			$current_post,
			array(
				'location'      => 'after_bad_response',
				'error_message' => $error_message,
				'error_code'    => $error_code,
			)
		);
	}

	/**
	 * Checks if a post_type can be synced.
	 *
	 * @param null| $post_id The ID of the post in question.
	 *
	 * @return bool
	 */
	protected function is_valid_sync_post_type( $post_id = null ) {
		$allowed_post_types = $this->get_allowed_post_types();
		$current_post_type  = get_post_type( $post_id );

		return in_array( $current_post_type, $allowed_post_types, true );
	}

	/**
	 * Returns the array of allowed post types.
	 *
	 * @return mixed
	 */
	protected function get_allowed_post_types() {
		if ( isset( $this->options['allowed_post_types'] ) && is_array( $this->options['allowed_post_types'] ) ) {
			$selected_post_types = $this->options['allowed_post_types'];

			return $selected_post_types;
		} else {
			// Return an empty array, otherwise if all post types have been deselectd on the options page
			// functions using this function will be trying to access the key of `null`.
			return array();
		}
	}

	/**
	 * Strip html tags from titles before passing them to Discourse.
	 *
	 * @param string $title The title of the post.
	 *
	 * @return string
	 */
	protected function sanitize_title( $title ) {
		return wp_strip_all_tags( $title );
	}

	/**
	 * Saves the topic_id/blog_id to the wpdc_topic_blog table.
	 *
	 * Used for multisite installations so that a Discourse topic_id can be associated with a blog_id.
	 *
	 * @param int $topic_id The topic_id to save to the database.
	 * @param int $blog_id The blog_id to save to the database.
	 */
	protected function save_topic_blog_id( $topic_id, $blog_id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
		$wpdb->insert(
			$table_name,
			array(
				'topic_id' => $topic_id,
				'blog_id'  => $blog_id,
			),
			array(
				'%d',
				'%d',
			)
		);
	}

	/**
	 * Checks if a given topic_id already exists in the wpdc_topic_blog table.
	 *
	 * Only used for multisite installations.
	 *
	 * @param int $topic_id The topic_id to search for in the database.
	 *
	 * @return bool
	 */
	protected function topic_blog_id_exists( $topic_id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . 'wpdc_topic_blog';
		$query      = "SELECT * FROM $table_name WHERE topic_id = %d";
		$row        = $wpdb->get_row( $wpdb->prepare( $query, $topic_id ) );

		return $row ? true : false;
	}
}
