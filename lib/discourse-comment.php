<?php
/**
 * Syncs Discourse comments with WordPress posts.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseComment;

use WPDiscourse\DiscourseBase;

/**
 * Class DiscourseComment
 */
class DiscourseComment extends DiscourseBase {

	/**
	 * An instance of the DiscourseCommentFormatter class.
	 *
	 * @access protected
	 * @var \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter DiscourseCommentFormatter
	 */
	protected $comment_formatter;

	/**
	 * Logger context
	 *
	 * @access protected
	 * @var string
	 */
	protected $logger_context = 'comment';

	/**
	 * DiscourseComment constructor.
	 *
	 * @param \WPDiscourse\DiscourseCommentFormatter\DiscourseCommentFormatter $comment_formatter An instance of DiscourseCommentFormatter.
	 */
	public function __construct( $comment_formatter ) {
		$this->comment_formatter = $comment_formatter;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'init', array( $this, 'setup_logger' ) );
		add_filter( 'get_comments_number', array( $this, 'get_comments_number' ), 10, 2 );
		add_action( 'wpdc_sync_discourse_comments', array( $this, 'sync_comments' ), 10, 3 );
		add_filter( 'comments_template', array( $this, 'comments_template' ), 20, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'discourse_comments_js' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_comment_route' ) );
	}

	/**
	 * Enqueues the comment scripts and styles.
	 *
	 * Hooks into 'wp_enqueue_scripts'.
	 */
	public function discourse_comments_js() {
		if ( ! empty( $this->options['ajax-load'] ) ) {
			$data = array(
				'commentsURL' => home_url( '/wp-json/wp-discourse/v1/discourse-comments' ),
			);
			wp_enqueue_script( 'load_comments_js' );
			wp_localize_script( 'load_comments_js', 'wpdc', $data );
		}

		if ( ! empty( $this->options['load-comment-css'] ) ) {
			wp_enqueue_style( 'comment_styles' );
		}
	}

	/**
	 * Registers a Rest API route for returning comments at /wp-json/wp-discourse/v1/discourse-comments.
	 */
	public function initialize_comment_route() {
		if ( ! empty( $this->options['ajax-load'] ) ) {
			register_rest_route(
				'wp-discourse/v1',
				'discourse-comments',
				array(
					array(
						'methods'             => \WP_REST_Server::READABLE,
						'permission_callback' => function() {
							return true;
						},
						'callback'            => array( $this, 'get_discourse_comments' ),
					),
				)
			);
		}
	}

	/**
	 * Handles the REST request for Discourse comments.
	 *
	 * @param \WP_REST_Request Object $request The WP_REST_Request for Discourse comments.
	 *
	 * @return string
	 */
	public function get_discourse_comments( $request ) {
		$post_id = isset( $request['post_id'] ) ? intval( ( $request['post_id'] ) ) : 0;

		if ( empty( $post_id ) ) {

			return '';
		}

		$status = get_post_status( $post_id );
		$post   = get_post( $post_id );

		if ( 'publish' !== $status || ! empty( $post->post_password ) ) {

			return '';
		}

		return wp_kses_post( $this->comment_formatter->format( $post_id ) );
	}

	/**
	 * Returns the comment type for posts that have been published to Discourse, 0 if the post has not been published
	 * to Discourse, or if the 'enable-discourse-comments' option is not enabled.
	 *
	 * @param int    $post_id The post ID to check.
	 * @param string $context The caller context.
	 *
	 * @return int|mixed|string
	 */
	public function get_comment_type_for_post( $post_id, $context ) {
		$discourse_post_id = get_post_meta( $post_id, 'discourse_post_id', true );
		if ( empty( $this->options['enable-discourse-comments'] ) || empty( $discourse_post_id ) ) {

			return 0;
		}

		$comment_type = $this->options['comment-type'];
		// For posts published to Discourse prior to WP Discourse version 2.0.7 the "Update Discourse Topic" button will need to be
		// clicked for the 'publish_post_category' metadata to get set as post_metadata.
		$publish_category_id = get_post_meta( $post_id, 'publish_post_category', true );

		if ( 'display-comments' === $comment_type || 'display-comments-link' === $comment_type ) {

			return $comment_type;
		} else {
			$discourse_category = $this->get_discourse_category_by_id( $publish_category_id );

			if ( is_wp_error( $discourse_category ) ) {
				$log_args = array(
					'message' => $discourse_category->get_error_message(),
				);
				$this->logger->error( "{$context}.get_discourse_category", $log_args );
			}

			// If the Display Subcategories option is not enabled and a linked Discourse topic is moved to a subcategory, comments will not be displayed on WordPress.
			// If the Display Subcategories option is enabled, the subcategory security settings will be respected.
			if ( is_wp_error( $discourse_category ) || empty( $discourse_category ) || 1 === intval( $discourse_category['read_restricted'] ) ) {

				return 'display-comments-link';
			} else {

				return 'display-comments';
			}
		}
	}

	/**
	 * Syncs Discourse comments to WordPress.
	 *
	 * @param int    $post_id The WordPress post id.
	 * @param bool   $force Force comment sync.
	 * @param string $comment_type Set comment type for the post.
	 *
	 * @return null
	 */
	public function sync_comments( $post_id, $force = false, $comment_type = null ) {
		global $wpdb;

		$discourse_options     = $this->options;
		$use_discourse_webhook = ! empty( $discourse_options['use-discourse-webhook'] );
		$time                  = date_create()->format( 'U' );
		// Every 10 minutes do a json call to sync comments.
		$last_sync   = (int) get_post_meta( $post_id, 'discourse_last_sync', true );
		$sync_period = apply_filters( 'wpdc_comment_sync_period', 600, $post_id );
		$sync_post   = $last_sync + $sync_period < $time;

		// If the comments webhook is enabled, comments may be synced more often than once every 10 minutes.
		// For now, the 10 minute sync period is used as a fallback even when the webhook is enabled.
		// Once we give authors some feedback about the webhooks success after a post is published, the fallback can be removed.
		if ( $use_discourse_webhook ) {
			$sync_post = $sync_post || 1 === intval( get_post_meta( $post_id, 'wpdc_sync_post_comments', true ) );
		}

		if ( $sync_post || $force ) {
			// Avoids a double sync.
			$got_lock = $wpdb->get_row( "SELECT GET_LOCK( 'discourse_lock', 0 ) got_it" );
			if ( 1 === intval( $got_lock->got_it ) ) {

				$publish_private = apply_filters( 'wpdc_publish_private_post', false, $post_id );
				if ( 'publish' === get_post_status( $post_id ) || $publish_private ) {
					// Possible values are 0 (no Discourse comments), 'display-comments', or 'display-comments-link'.
					$comment_type             = $comment_type ? $comment_type : $this->get_comment_type_for_post( $post_id, 'sync_comments' );
					$comment_count            = 'display-comments' === $comment_type ? intval( $discourse_options['max-comments'] ) : 0;
					$min_trust_level          = intval( $discourse_options['min-trust-level'] );
					$min_score                = intval( $discourse_options['min-score'] );
					$min_replies              = intval( $discourse_options['min-replies'] );
					$bypass_trust_level_score = intval( $discourse_options['bypass-trust-level-score'] );

					$options = 'best=' . $comment_count . '&min_trust_level=' . $min_trust_level . '&min_score=' . $min_score;
					$options = $options . '&min_replies=' . $min_replies . '&bypass_trust_level_score=' . $bypass_trust_level_score;

					if ( isset( $discourse_options['only-show-moderator-liked'] ) && 1 === intval( $discourse_options['only-show-moderator-liked'] ) ) {
						$options = $options . '&only_moderator_liked=true';
					}

					$discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true );
					$topic_id            = get_post_meta( $post_id, 'discourse_topic_id', true );
					if ( ! $discourse_permalink ) {

						return 0;
					}
					$permalink = esc_url_raw( $discourse_permalink ) . '/wordpress.json?' . $options;
					$result    = $this->discourse_request( $permalink, array( 'raw' => true ) );

					if ( ! $this->validate( $result ) ) {
						$message = wp_remote_retrieve_response_message( $result );
						$code    = wp_remote_retrieve_response_code( $result );

						$log_args = array(
							'message'            => $message,
							'discourse_topic_id' => $topic_id,
							'wp_post_id'         => $post_id,
							'http_code'          => $code,
						);
						$this->logger->error( 'sync_comments.response_error', $log_args );
					} else {
						$raw_body = $result['body'];
						$json     = json_decode( $raw_body );

						if ( isset( $json->filtered_posts_count ) ) {
							$posts_count = $json->filtered_posts_count - 1;
							if ( $posts_count < 0 ) {
								$posts_count = 0;
							}

							update_post_meta( $post_id, 'discourse_comments_count', $posts_count );

							// Check if the site is on a recent version of Discourse that's returning the category_id (Aug 5, 2020.)
							// Allows comment type to be set for topics that are recategorized on Discourse when the display-public-comments-only comment option is selected.
							if ( property_exists( $json, 'category_id' ) ) {
								$category_id = $json->category_id;
								update_post_meta( $post_id, 'publish_post_category', intval( $category_id ) );
							}

							update_post_meta( $post_id, 'discourse_comments_raw', esc_sql( $raw_body ) );

							if ( isset( $topic_id ) ) {
								// Delete the cached html.
								delete_transient( "wpdc_comment_html_{$topic_id}" );
							}

							if ( ! empty( $this->options['verbose-comment-logs'] ) ) {
								$this->logger->info( 'sync_comments.success', array( 'post_id' => $post_id ) );
							}
						}
					}

					update_post_meta( $post_id, 'discourse_last_sync', $time );
					update_post_meta( $post_id, 'wpdc_sync_post_comments', 0 );
				}// End if().
			}// End if().

			$wpdb->get_results( "SELECT RELEASE_LOCK( 'discourse_lock' )" );
		}// End if().

		return null;
	}

	/**
	 * Displays either the Discourse comments or a link to the comments, depending on the value of $comment_type.
	 *
	 * Hooks into 'comments_template'.
	 *
	 * @param string $old The comments template returned by WordPress.
	 *
	 * @return string
	 */
	public function comments_template( $old ) {
		global $post;
		$post_id      = $post->ID;
		$current_user = wp_get_current_user();

		// Possible values are 0 (no Discourse comments), 'display-comments', or 'display-comments-link'.
		$comment_type = $this->get_comment_type_for_post( $post_id, 'comments_template' );
		// Discourse comments are not being used for the post and the hide-wordpress-comments option has been selected.
		$load_blank = empty( $comment_type ) && ! empty( $this->options['hide-wordpress-comments'] );
		// A switch that can be used to prevent loading the comments template for a user.
		$load_comments_template = apply_filters( 'wpdc_load_comments_template_for_user', true, $current_user, $post_id );

		if ( ! $load_comments_template || $load_blank ) {

			return WPDISCOURSE_PATH . 'templates/blank.php';
		}

		if ( empty( $comment_type ) ) {

			return $old;
		}

		$discourse_comments = null;
		switch ( $comment_type ) {
			case 'display-comments':
				if ( ! empty( $this->options['ajax-load'] ) ) {

					return WPDISCOURSE_PATH . 'templates/ajax-comments.php';
				}
				$discourse_comments = $this->comment_formatter->format( $post_id );

				break;
			case 'display-comments-link':
				$discourse_comments = $this->comment_formatter->comment_link( $post_id );

				break;
		}

		// Use $post->comment_count because get_comments_number will return the Discourse comments
		// number for posts that are published to Discourse.
		$num_wp_comments = $post->comment_count;
		if ( empty( $this->options['show-existing-comments'] ) || 0 === intval( $num_wp_comments ) ) {
			echo wp_kses_post( $discourse_comments );

			return WPDISCOURSE_PATH . 'templates/blank.php';
		} else {
			echo wp_kses_post( $discourse_comments ) . '<div class="discourse-existing-comments-heading">' . wp_kses_post( $this->options['existing-comments-heading'] ) . '</div>';

			return $old;
		}
	}

	/**
	 * Returns the 'discourse_comments_count' for posts that use Discourse comments.
	 *
	 * Returns the sum of the Discourse comments and the WordPress comments for posts that have both.
	 *
	 * @param int $count The comment count supplied by WordPress.
	 * @param int $post_id The ID of the post.
	 *
	 * @return mixed
	 */
	public function get_comments_number( $count, $post_id ) {
		$discourse_post_id = get_post_meta( $post_id, 'discourse_post_id', true );
		if ( ! empty( $discourse_post_id ) ) {

			$single_page = is_single( $post_id ) || is_page( $post_id );
			$single_page = apply_filters( 'wpdc_single_page_comment_number_sync', $single_page, $post_id );

			if ( empty( $this->options['use-discourse-webhook'] ) ) {
				// Only automatically sync comments for individual posts, it's too inefficient to do this with an archive page.
				if ( $single_page ) {
					$this->sync_comments( $post_id );
				} else {
					// For archive pages, check $last_sync against $archive_page_sync_period.
					$archive_page_sync_period = intval( apply_filters( 'discourse_archive_page_sync_period', DAY_IN_SECONDS, $post_id ) );
					$last_sync                = intval( get_post_meta( $post_id, 'discourse_last_sync', true ) );

					if ( $last_sync + $archive_page_sync_period < time() ) {
						$this->sync_comments( $post_id );
					}
				}
			}

			$count = intval( get_post_meta( $post_id, 'discourse_comments_count', true ) );

			// If WordPress comments are also being used, add them to the comment count.
			if ( ! empty( $this->options['show-existing-comments'] ) ) {
				$current_post     = get_post( $post_id );
				$wp_comment_count = $current_post->comment_count;
				$count            = $count + $wp_comment_count;
			}
		}

		return apply_filters( 'wpdc_comments_count', $count, $post_id, $discourse_post_id );
	}
}
