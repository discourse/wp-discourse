<?php
/**
 * Publishes a post to Discourse.
 *
 * @package WPDiscourse
 * @todo Periodically review phpcs exclusions.
 */

namespace WPDiscourse\DiscoursePublish;

use WPDiscourse\DiscourseBase;
use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Shared\TemplateFunctions;

/**
 * Class DiscoursePublish
 */
class DiscoursePublish extends DiscourseBase {
	use TemplateFunctions;

	/**
	 * An email_notification object that has a publish_failure_notification method.
	 *
	 * @access protected
	 * @var \WPDiscourse\EmailNotification\EmailNotification
	 */
	protected $email_notifier;

	/**
     * Logger context
     *
     * @access protected
     * @var string
     */
  protected $logger_context = 'publish';

	/**
	 * Instance store for log args
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $log_args;

	/**
	 * DiscoursePublish constructor.
	 *
	 * @param object $email_notifier An object for sending an email verification notice.
	 * @param bool   $register_actions Flag determines whether to register publish actions.
	 */
	public function __construct( $email_notifier, $register_actions = true ) {
		$this->email_notifier = $email_notifier;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'init', array( $this, 'setup_logger' ) );

		// Registration is conditional to make testing easier.
		if ( $register_actions ) {
			if ( version_compare( get_bloginfo( 'version' ), '5.6', '>=' ) ) {
				// On the difference between wp_after_insert_post and save_post see https://make.wordpress.org/core/2020/11/20/new-action-wp_after_insert_post-in-wordpress-5-6/.
				add_action( 'wp_after_insert_post', array( $this, 'publish_post_after_save' ), 10, 2 );
			} else {
				// Priority is set to 13 so that 'publish_post_after_save' is called after the meta-box is saved.
				add_action( 'save_post', array( $this, 'publish_post_after_save' ), 13, 2 );
			}

			add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
		}
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
			 || $this->has_excluded_tag( $post_id, $post )
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

		$already_published      = $this->dc_get_post_meta( $post_id, 'discourse_post_id', true );
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

		if ( $publish_to_discourse && $post_is_published && $this->is_valid_sync_post_type( $post_id ) && ! empty( $title ) && ! $this->has_excluded_tag( $post_id ) ) {
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
		$got_lock = $wpdb->get_row( "SELECT GET_LOCK('discourse_sync_lock', 0) got_it" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( 1 === intval( $got_lock->got_it ) ) {
			$this->sync_to_discourse_work( $post_id, $title, $raw );
			$wpdb->get_results( "SELECT RELEASE_LOCK('discourse_sync_lock')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	/**
	 * Calls `sync_to_discourse_work` without a lock. Only used for testing.
	 * Should not be used elsewhere in plugin.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title The title.
	 * @param string $raw The raw content of the post.
	 */
	public function sync_to_discourse_without_lock( $post_id, $title, $raw ) {
		return $this->sync_to_discourse_work( $post_id, $title, $raw );
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
		$discourse_id                = $this->dc_get_post_meta( $post_id, 'discourse_post_id', true );
		$post                        = get_post( $post_id );
		$author_id                   = $post->post_author;
		$use_full_post               = ! empty( $options['full-post-content'] );
		$use_multisite_configuration = is_multisite() && ! empty( $options['multisite-configuration-enabled'] );
		$add_featured_link           = ! empty( $options['add-featured-link'] );
		$permalink                   = get_permalink( $post_id );

		$this->log_args = array(
			'wp_title'     => $title,
			'wp_author_id' => $author_id,
			'wp_post_id'   => $post_id,
		);

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
				$wp_excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
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
		} else {
			$tags = array();
		}

		$remote_post_type = '';

		// The post hasn't been published to Discourse yet.
		if ( ! $discourse_id > 0 ) {
			// Unlisted has been moved from post metadata to a site option. This is awkward for now.
			$unlisted_post   = get_post_meta( $post_id, 'wpdc_unlisted_topic', true );
			$unlisted_option = $this->options['publish-as-unlisted'];
			$unlisted        = apply_filters( 'wpdc_publish_unlisted', ! empty( $unlisted_post ) || ! empty( $unlisted_option ), $post, $post_id );
			if ( $unlisted ) {
				update_post_meta( $post_id, 'wpdc_unlisted_topic', 1 );
			}

			$body = array(
				'embed_url'        => $permalink,
				'featured_link'    => $add_featured_link ? $permalink : null,
				'title'            => $title,
				'raw'              => $baked,
				'category'         => $category,
				'skip_validations' => 'true',
				'auto_track'       => ( ! empty( $options['auto-track'] ) ? 'true' : 'false' ),
				'visible'          => $unlisted ? 'false' : 'true',
			);

			$tags = array_filter( $tags );
			if ( ! empty( $tags ) ) {
				$body['tags'] = $tags;
			}

			$path                = '/posts';
			$remote_post_options = array(
				'method' => 'POST',
				'body'   => $body,
			);
			$remote_post_type    = 'create_post';
		} else {
			// The post has already been published.
			$body                = array(
				'title'            => $title,
				'post'             => array(
					'raw' => $baked,
				),
				'skip_validations' => 'true',
			);
			$path                = '/posts/' . $discourse_id;
			$remote_post_options = array(
				'method' => 'PUT',
				'body'   => $body,
			);
			$remote_post_type    = 'update_post';
		}

		$remote_post_options['body'] = apply_filters( 'wpdc_publish_body', $remote_post_options['body'], $remote_post_type, $post_id );

		$username            = apply_filters( 'wpdc_discourse_username', get_the_author_meta( 'discourse_username', $post->post_author ), $author_id );
		$username_exists     = $username && strlen( $username ) > 1;
		$single_user_api_key = ! empty( $this->options['single-user-api-key-publication'] );

		if ( 'create_post' === $remote_post_type && ! $single_user_api_key && $username_exists ) {
			$remote_post_options['api_username'] = $username;
		}

		$response = $this->remote_post( $path, $remote_post_options, $remote_post_type, $post_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = $this->validate_response_body( $response, $remote_post_type, $post_id );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( 'create_post' === $remote_post_type ) {
			$discourse_id = intval( $body->id );
			$topic_slug   = sanitize_text_field( $body->topic_slug );
			$topic_id     = intval( $body->topic_id );
			$topic_url    = esc_url_raw( $options['url'] . '/t/' . $topic_slug . '/' . $topic_id );

			$this->dc_add_post_meta( $post_id, 'discourse_post_id', $discourse_id, true );
			$this->dc_add_post_meta( $post_id, 'discourse_topic_id', $topic_id, true );
			$this->dc_add_post_meta( $post_id, 'discourse_permalink', $topic_url, true );
			update_post_meta( $post_id, 'publish_post_category', $category );

			$this->log_args['discourse_post_id'] = $discourse_id;

			// Used for resetting the error notification, if one was being displayed.
			update_post_meta( $post_id, 'wpdc_publishing_response', 'success' );
			if ( $use_multisite_configuration ) {
				$blog_id = intval( get_current_blog_id() );
				$this->save_topic_blog_id( $body->topic_id, $blog_id );
			}

			$pin_until = get_post_meta( $post_id, 'wpdc_pin_until', true );
			if ( ! empty( $pin_until ) ) {
				$pin_response = $this->pin_discourse_topic( $post_id, $topic_id, $pin_until );

				if ( is_wp_error( $pin_response ) ) {
					return $pin_response;
				}
			}

			if ( $single_user_api_key && $username_exists ) {
				$change_response = $this->change_post_owner( $post_id, $username );

				if ( is_wp_error( $change_response ) ) {
					return $change_response;
				}
			}

			$this->after_publish( $post_id, $remote_post_type );

			// The topic has been created and its associated post's metadata has been updated.
			return null;
		}

		if ( 'update_post' === $remote_post_type ) {
			$discourse_post = $body->post;
			$topic_slug     = sanitize_text_field( $discourse_post->topic_slug );
			$topic_id       = intval( $discourse_post->topic_id );
			$topic_url      = esc_url_raw( $options['url'] . '/t/' . $topic_slug . '/' . $topic_id );

			update_post_meta( $post_id, 'discourse_permalink', $topic_url );
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
				$body                = array(
					'featured_link' => $permalink,
				);
				$remote_post_options = array(
					'method' => 'PUT',
					'body'   => $body,
				);

				$featured_response = $this->remote_post( $topic_url, $remote_post_options, 'featured_link', $post_id );

				if ( is_wp_error( $featured_response ) ) {
					return $featured_response;
				}
			}

			$this->after_publish( $post_id, $remote_post_type );

			// The topic has been updated, and its associated post's metadata has been updated.
			return null;
		}

		return $this->handle_error( 'unknown', $response, $post_id );
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
		$status_path  = "/t/$topic_id/status";
		$body         = array(
			'status'  => 'pinned',
			'enabled' => 'true',
			'until'   => $pin_until,
		);
		$post_options = array(
			'method' => 'PUT',
			'body'   => $body,
		);

		$response = $this->remote_post( $status_path, $post_options, 'pin_topic', $post_id );

		delete_post_meta( $post_id, 'wpdc_pin_until' );

		return $response;
	}

	/**
	 * Changes the owner of a Discourse topic associated with a WordPress post.
	 *
	 * @param int    $post_id The WordPress post_id.
	 * @param string $username The username of the Discourse user to change ownership to.
	 *
	 * @return null|\WP_Error
	 */
	protected function change_post_owner( $post_id, $username ) {
		$discourse_post_id  = get_post_meta( $post_id, 'discourse_post_id', true );
		$discourse_topic_id = get_post_meta( $post_id, 'discourse_topic_id', true );

		$path    = "/t/$discourse_topic_id/change-owner";
		$body    = array(
			'username' => $username,
			'post_ids' => array( $discourse_post_id ),
		);
		$options = array(
			'method' => 'POST',
			'body'   => $body,
		);

		return $this->remote_post( $path, $options, 'change_owner', $post_id );
	}

	/**
	 * Creates an admin_notice and calls the publish_failure_notification method after a bad response is returned from Discourse.
	 *
	 * @param object $error The error returned from the request.
	 * @param int    $post_id The post for which the notifications are being created.
	 */
	protected function create_bad_response_notifications( $error, $post_id ) {
		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return;
		}

		$this->email_notifier->publish_failure_notification(
			$post,
			array(
				'location'      => 'after_bad_response',
				'error_message' => $error->message,
				'error_code'    => $error->code,
			)
		);
	}

	/**
	 * Wrapper of discourse_request to handle validation, logging and error handling.
	 *
	 * @param string $url Url or path of the remote post.
	 * @param object $remote_options Options to pass to remote post.
	 * @param string $remote_type Remote post type.
	 * @param int    $post_id ID of post being sent.
	 */
	public function remote_post( $url, $remote_options, $remote_type, $post_id ) {
		$remote_options['raw'] = true;
		$response              = $this->discourse_request( $url, $remote_options );

		if ( ! $this->validate( $response ) ) {
			$response = $this->handle_error( $remote_type, $response, $post_id );
		} elseif ( ! empty( $this->options['verbose-publication-logs'] ) ) {
			$this->logger->info( "$remote_type.post_success", $this->log_args );
		}

		return $response;
	}

	/**
	 * Validation for post response body.
	 *
	 * @param object $response Response to be validated.
	 * @param string $remote_type Remote post type.
	 * @param int    $post_id ID of post being sent.
	 */
	protected function validate_response_body( $response, $remote_type, $post_id ) {
		$body       = json_decode( wp_remote_retrieve_body( $response ) );
		$error_type = 'body_validation';

		if ( $this->post_is_enqueued( $body ) ) {
			return $this->handle_notice( $remote_type, $response, $post_id, 'queued_topic' );
		}

		if ( 'create_post' === $remote_type && ! $this->validate_create_post_body( $body ) ) {
			return $this->handle_error( $remote_type, $response, $post_id, $error_type );
		}

		if ( 'update_post' === $remote_type ) {
			if ( ! $this->validate_update_post_body( $body ) ) {
				return $this->handle_error( $remote_type, $response, $post_id, $error_type );
			}

			if ( $this->post_is_deleted( $body ) ) {
				return $this->handle_notice( $remote_type, $response, $post_id, 'deleted_topic' );
			}
		}

		if ( ! empty( $this->options['verbose-publication-logs'] ) ) {
			$this->logger->info( "$remote_type.body_valid", $this->log_args );
		}

		return $body;
	}

	/**
	 * Validate the body of a response when creating a post.
	 *
	 * @param object $body Body to be validated.
	 */
	protected function validate_create_post_body( $body ) {
		return ! empty( $body->id ) && ! empty( $body->topic_slug ) && ! empty( $body->topic_id );
	}

	/**
	 * Validate the body of a response when updating a post.
	 *
	 * @param object $body Body to be validated.
	 */
	protected function validate_update_post_body( $body ) {
		return ! empty( $body->post ) && ! empty( $body->post->topic_slug ) && ! empty( $body->post->topic_id );
	}

	/**
	 * Test for whether post was enqueued.
	 *
	 * @param object $body Body to be validated.
	 */
	protected function post_is_enqueued( $body ) {
		return empty( $body );
	}

	/**
	 * Test for whether topic has been deleted.
	 *
	 * @param object $body Body to be validated.
	 */
	protected function post_is_deleted( $body ) {
		return ! empty( $body->post->deleted_at );
	}

	/**
	 * Handle publication errors
	 *
	 * @param string $remote_type Remote post type.
	 * @param object $response Remote post response.
	 * @param string $post_id ID of post sent.
	 * @param bool   $error_type Error type.
	 */
	protected function handle_error( $remote_type, $response, $post_id, $error_type = 'post' ) {
		$atts = $this->get_response_attributes( $response );

		if ( 'create_post' === $remote_type ) {
			// This is a fix for a bug that was introduced by not setting the wpdc_auto_publish_overridden post_metadata
			// when posts are unlinked from Discourse. That metadata is now being set. This fix is for dealing with
			// previously unlinked posts.
			if ( 'Embed url has already been taken' === $atts->message ) {
				update_post_meta( $post_id, 'wpdc_auto_publish_overridden', 1 );
			}

			update_post_meta( $post_id, 'wpdc_publishing_error', sanitize_text_field( $atts->message ) );
			delete_post_meta( $post_id, 'publish_to_discourse' );

			if ( 'body_validation' === $error_type ) {
				update_post_meta( $post_id, 'wpdc_publishing_response', 'error' );
			}
		}

		$this->create_bad_response_notifications( $atts, $post_id );

		if ( 'body_validation' === $error_type ) {
			$message = __( 'An invalid response was returned from Discourse', 'wp-discourse' );
		} else {
			$message = __( 'An error occurred when communicating with Discourse', 'wp-discourse' );
		}

		$this->logger->error( "{$remote_type}.{$error_type}_error", $this->log_args );

		return new \WP_Error( 'discourse_publishing_response_error', $message );
	}

	/**
	 * Handle publication notices.
	 *
	 * @param string $remote_type Type of remote post.
	 * @param object $response Remote post response.
	 * @param string $post_id ID of post sent.
	 * @param string $notice_type Type of notice.
	 */
	protected function handle_notice( $remote_type, $response, $post_id, $notice_type ) {
		// The presence of notice types 'queued_topic' and 'deleted_topic' in
		// wpdc_publising_error are currently used for determining whether a
		// post can be published in discourse-sidebar/src/index.js.
		update_post_meta( $post_id, 'wpdc_publishing_error', $notice_type );

		$this->get_response_attributes( $response );
		$this->logger->warn( "{$remote_type}.{$notice_type}_notice", $this->log_args );

		$notice_messages = array(
			'queued_topic'  => __( 'The published post has been added to the Discourse approval queue', 'wp-discourse' ),
			'deleted_topic' => __( 'The Discourse topic associated with this post has been deleted', 'wp-discourse' ),
		);
		$message         = $notice_messages[ $notice_type ];

		return new \WP_Error( 'discourse_publishing_response_notice', $message );
	}

	/**
	 * Retrieve the message and code from a response.
	 *
	 * @param object $response Remote post response.
	 */
	protected function get_response_attributes( $response ) {
		$atts = (object) array(
			'message' => null,
			'code'    => null,
		);

		if ( is_wp_error( $response ) ) {
			$atts->message = $response->get_error_message();
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! empty( $body ) && ! empty( $body->errors ) && ! empty( $body->errors[0] ) ) {
				$atts->message = $body->errors[0];
			} else {
				$atts->message = wp_remote_retrieve_response_message( $response );
			}
		}

		if ( ! empty( $atts->message ) ) {
			$this->log_args['response_message'] = $atts->message;
		}

		$raw_code = wp_remote_retrieve_response_code( $response );

		if ( ! empty( $raw_code ) ) {
			$atts->code                  = intval( $raw_code );
			$this->log_args['http_code'] = $atts->code;
		}

		return $atts;
	}

	/**
	 * Checks if a post_type can be synced.
	 *
	 * @param null $post_id The ID of the post in question.
	 *
	 * @return bool
	 */
	protected function is_valid_sync_post_type( $post_id = null ) {
		$allowed_post_types = $this->get_allowed_post_types();
		$current_post_type  = get_post_type( $post_id );

		return in_array( $current_post_type, $allowed_post_types, true );
	}

	/**
	 * Checks if a post has an excluded tag.
	 *
	 * @param int      $post_id The ID of the post in question.
	 * @param \WP_Post $post The Post object.
	 *
	 * @return bool
	 */
	protected function has_excluded_tag( $post_id, $post ) {
		if ( version_compare( get_bloginfo( 'version' ), '5.6', '<' ) ) {
			return false;
		}

		$post_tags = get_the_terms( $post->ID, 'post_tag' );
		if ( empty( $post_tags ) || is_wp_error( $post_tags ) ) {
			return false;
		}

		$excluded_tag_slugs = $this->get_excluded_tag_slugs();
		if ( empty( $excluded_tag_slugs ) ) {
			return false;
		}

		$post_tag_slugs = wp_list_pluck( $post_tags, 'slug' );

		return count( array_intersect( $post_tag_slugs, $excluded_tag_slugs ) ) > 0;
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
	 * Returns the array of excluded tags.
	 *
	 * @return mixed
	 */
	protected function get_excluded_tag_slugs() {
		if ( isset( $this->options['exclude_tags'] ) && is_array( $this->options['exclude_tags'] ) ) {
			$excluded_tag_slugs = $this->options['exclude_tags'];

			return $excluded_tag_slugs;
		} else {
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
	 * Checks if a given topic_id already exists in the wpdc_topic_blog table.
	 *
	 * Only used for multisite installations.
	 *
	 * @param int $topic_id The topic_id to search for in the database.
	 *
	 * @return bool
	 */
	public function topic_blog_id_exists( $topic_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}wpdc_topic_blog WHERE topic_id = %d",
				$topic_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return $row ? true : false;
	}

	/**
	 * Gets post metadata via wp method, or directly from db, depending on the direct-db-publication-flags option.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key The meta key to retrieve.
	 * @param bool   $single (optional) Whether to return a single value.
	 *
	 * @return string
	 */
	protected function dc_get_post_meta( $post_id, $key, $single = false ) {
		if ( empty( $this->options['direct-db-publication-flags'] ) ) {
			return get_post_meta( $post_id, $key, $single );
		}

		global $wpdb;
		$limit = $single ? 'LIMIT 1' : '';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s %1s;", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
				$post_id,
				$key,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return $value;
	}

	/**
	 * Adds post metadata via wp method, or directly to db, depending on the direct-db-publication-flags option.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key The meta key.
	 * @param string $value The meta value.
	 * @param bool   $unique (optional) Whether the same key should not be added.
	 *
	 * @return bool
	 */
	protected function dc_add_post_meta( $post_id, $key, $value, $unique = false ) {
		if ( empty( $this->options['direct-db-publication-flags'] ) ) {
			return add_post_meta( $post_id, $key, $value, $unique );
		}

		global $wpdb;

		if ( $unique && ! is_null( $this->dc_get_post_meta( $post_id, $key ) ) ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $post_id,
				'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);
		// phpcs:disable WordPress.DB.DirectDatabaseQuery

		return $result ? true : false;
	}

	/**
	 * Runs after publication successfully completes
	 *
	 * @param int    $post_id Post ID.
	 * @param string $remote_post_type The remote post type.
	 *
	 * @return void
	 */
	protected function after_publish( $post_id, $remote_post_type ) {
		if ( ! empty( $this->options['verbose-publication-logs'] ) ) {
			$log_args = array(
				'post_id'             => $post_id,
				'remote_post_type'    => $remote_post_type,
				'discourse_post_id'   => get_post_meta( $post_id, 'discourse_post_id', true ),
				'discourse_topic_id'  => get_post_meta( $post_id, 'discourse_topic_id', true ),
				'discourse_permalink' => get_post_meta( $post_id, 'discourse_permalink', true ),
			);
			$this->logger->info( "$remote_post_type.after_publish", $log_args );
		}

		do_action( 'wp_discourse_after_publish', $post_id, $remote_post_type );
	}
}
