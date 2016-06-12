<?php
/**
 * Syncs Discourse comments with WordPress posts.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseComment;

/**
 * Class DiscourseComment
 */
class DiscourseComment {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Validates the response from the Discourse forum.
	 *
	 * @access protected
	 * @var \WPDiscourse\ResponseValidator\ResponseValidator
	 */
	protected $response_validator;

	/**
	 * DiscourseComment constructor.
	 *
	 * @param \WPDiscourse\ResponseValidator\ResponseValidator $response_validator Validate the response from Discourse.
	 */
	public function __construct( $response_validator ) {
		$this->options = get_option( 'discourse' );
		$this->response_validator = $response_validator;
		add_filter( 'comments_number', array( $this, 'comments_number' ) );
		add_filter( 'comments_template', array( $this, 'comments_template' ), 20, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'discourse_comments_js' ) );
	}

	/**
	 * Enqueues the `comments.js` script.
	 *
	 * Hooks into 'wp_enqueue_scripts'.
	 */
	function discourse_comments_js() {
		if ( is_singular( $this->options['allowed_post_types'] ) ) {
			if ( $this->use_discourse_comments( get_the_ID() ) ) {
				wp_enqueue_script(
					'discourse-comments-js',
					WPDISCOURSE_URL . '/js/comments.js',
					array( 'jquery' ),
					get_option( 'discourse_version' ),
					true
				);
				// Localize script
				$data              = array(
					'url' => $this->options['url'],
				);
				wp_localize_script( 'discourse-comments-js', 'discourse', $data );
			}
		}
	}

	/**
	 * Checks if a post is using Discourse comments.
	 *
	 * @param int $postid The ID of the post.
	 *
	 * @return bool|int
	 */
	protected function use_discourse_comments( $postid ) {
		if ( ! $this->options['use-discourse-comments'] ) {
			return 0;
		}

		$setting = get_post_meta( $postid, 'publish_to_discourse', true );

		return $setting == '1';
	}

	/**
	 * Syncs Discourse comments to WordPress.
	 *
	 * @param int $postid The WordPress post id.
	 */
	function sync_comments( $postid ) {
		global $wpdb;
		$discourse_options = $this->options;

		// every 10 minutes do a json call to sync comment count and top comments
		$last_sync = (int) get_post_meta( $postid, 'discourse_last_sync', true );
		$time      = date_create()->format( 'U' );
		$debug     = isset( $discourse_options['debug-mode'] ) && intval( $discourse_options['debug-mode'] ) == 1;

		if ( $debug || $last_sync + 60 * 10 < $time ) {
			$got_lock = $wpdb->get_row( "SELECT GET_LOCK( 'discourse_lock', 0 ) got_it" );
			if ( $got_lock->got_it == '1' ) {
				if ( get_post_status( $postid ) == 'publish' ) {

					$comment_count            = intval( $discourse_options['max-comments'] );
					$min_trust_level          = intval( $discourse_options['min-trust-level'] );
					$min_score                = intval( $discourse_options['min-score'] );
					$min_replies              = intval( $discourse_options['min-replies'] );
					$bypass_trust_level_score = intval( $discourse_options['bypass-trust-level-score'] );

					$options = 'best=' . $comment_count . '&min_trust_level=' . $min_trust_level . '&min_score=' . $min_score;
					$options = $options . '&min_replies=' . $min_replies . '&bypass_trust_level_score=' . $bypass_trust_level_score;

					if ( isset( $discourse_options['only-show-moderator-liked'] ) && intval( $discourse_options['only-show-moderator-liked'] ) == 1 ) {
						$options = $options . '&only_moderator_liked=true';
					}
					$options = $options . '&api_key=' . $discourse_options['api-key'] . '&api_username=' . $discourse_options['publish-username'];

					$permalink = esc_url_raw( get_post_meta( $postid, 'discourse_permalink', true ) ) . '/wordpress.json?' . $options;
					$result    = wp_remote_get( $permalink );

					if ( $this->response_validator->validate( $result ) ) {

						$json = json_decode( $result['body'] );

						if ( isset( $json->posts_count ) ) {
							$posts_count = $json->posts_count - 1;
							if ( $posts_count < 0 ) {
								$posts_count = 0;
							}

							delete_post_meta( $postid, 'discourse_comments_count' );
							add_post_meta( $postid, 'discourse_comments_count', $posts_count, true );

							delete_post_meta( $postid, 'discourse_comments_raw' );
							add_post_meta( $postid, 'discourse_comments_raw', esc_sql( $result['body'] ), true );

							delete_post_meta( $postid, 'discourse_last_sync' );
							add_post_meta( $postid, 'discourse_last_sync', $time, true );
						}
					}
				}
				$wpdb->get_results( "SELECT RELEASE_LOCK( 'discourse_lock' )" );
			}
		}
	}

	/**
	 * Loads the comments template.
	 *
	 * Hooks into 'comments_template'.
	 *
	 * @param string $old The comments template returned by WordPress.
	 *
	 * @return string
	 */
	function comments_template( $old ) {
		global $post;

		if ( self::use_discourse_comments( $post->ID ) ) {
			self::sync_comments( $post->ID );
			$options         = $this->options;
			$num_WP_comments = get_comments_number();
			if ( ( isset($options['show_existing_comments'] ) && ! $options['show-existing-comments'] ) ||
			     $num_WP_comments == 0 ) {
				// only show the Discourse comments
				return WPDISCOURSE_PATH . '/templates/comments.php';
			} else {
				// show the Discourse comments then show the existing WP comments (in $old)
				include WPDISCOURSE_PATH . '/templates/comments.php';
				echo '<div class="discourse-existing-comments-heading">' . wp_kses_post( $options['existing-comments-heading'] ) . '</div>';

				return $old;
			}
		}

		// show the existing WP comments
		return $old;
	}

	/**
	 * Returns the comments number.
	 *
	 * If Discourse comments are enabled, returns the 'discourse_comments_count', otherwise
	 * returns the $count value. Hooks into 'comments_number'.
	 *
	 * @param int $count The comment count supplied by WordPress.
	 *
	 * @return mixed|string
	 */
	function comments_number( $count ) {
		global $post;
		if ( $this->use_discourse_comments( $post->ID ) ) {
			$this->sync_comments( $post->ID );
			$count = get_post_meta( $post->ID, 'discourse_comments_count', true );
			if ( ! $count ) {
				$count = 'Leave a reply';
			} else {
				$count = $count == 1 ? '1 Reply' : $count . ' Replies';
			}
		}

		return $count;
	}


}