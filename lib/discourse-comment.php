<?php
/**
 * Syncs Discourse comments with WordPress posts.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseComment;
use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

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
	 * DiscourseComment constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'discourse' );
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
				// Localize script.
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
		if ( ( ! isset( $this->options['use-discourse-comments'] ) ) || ! $this->options['use-discourse-comments'] ) {
			return 0;
		}

		$setting = get_post_meta( $postid, 'publish_to_discourse', true );

		return 1 === intval( $setting );
	}

	/**
	 * Syncs Discourse comments to WordPress.
	 *
	 * @param int $postid The WordPress post id.
	 */
	function sync_comments( $postid ) {
		$discourse_options = $this->options;
		// Every 10 minutes do a json call to sync comment count and top comments.
		$last_sync = (int) get_post_meta( $postid, 'discourse_last_sync', true );
		$time      = date_create()->format( 'U' );
		$debug     = isset( $discourse_options['debug-mode'] ) && 1 === intval( $discourse_options['debug-mode'] );

		if ( $debug || $last_sync + 60 * 10 < $time ) {
			$lock = 'comments_locked_for_' . $postid;
			if ( ! 'locked' === get_transient( $lock ) ) {
				set_transient( $lock, 'locked' );

				if ( 'publish' === get_post_status( $postid ) ) {

					$comment_count            = intval( $discourse_options['max-comments'] );
					$min_trust_level          = intval( $discourse_options['min-trust-level'] );
					$min_score                = intval( $discourse_options['min-score'] );
					$min_replies              = intval( $discourse_options['min-replies'] );
					$bypass_trust_level_score = intval( $discourse_options['bypass-trust-level-score'] );

					$options = 'best=' . $comment_count . '&min_trust_level=' . $min_trust_level . '&min_score=' . $min_score;
					$options = $options . '&min_replies=' . $min_replies . '&bypass_trust_level_score=' . $bypass_trust_level_score;

					if ( isset( $discourse_options['only-show-moderator-liked'] ) && 1 === intval( $discourse_options['only-show-moderator-liked'] ) ) {
						$options = $options . '&only_moderator_liked=true';
					}
					$options = $options . '&api_key=' . $discourse_options['api-key'] . '&api_username=' . $discourse_options['publish-username'];

					$permalink = esc_url_raw( get_post_meta( $postid, 'discourse_permalink', true ) ) . '/wordpress.json?' . $options;
					$result    = wp_remote_get( $permalink );

					if ( DiscourseUtilities::validate( $result ) ) {

						$json = json_decode( $result['body'] );

						if ( isset( $json->posts_count ) ) {
							$posts_count = $json->posts_count - 1;
							if ( $posts_count < 0 ) {
								$posts_count = 0;
							}

							update_post_meta( $postid, 'discourse_comments_count', $posts_count );
							update_post_meta( $postid, 'discourse_comments_raw', esc_sql( $result['body'] ) );
							update_post_meta( $postid, 'discourse_last_sync', $time );
						}
					}
				}
				delete_transient( $lock );
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

		if ( $this->use_discourse_comments( $post->ID ) ) {
			$this->sync_comments( $post->ID );
			$options         = $this->options;
			$num_wp_comments = get_comments_number();
			if ( ( isset( $options['show-existing-comments'] ) && ( 0 === intval( $options['show-existing-comments'] ) ) ) ||
			     0 === intval( $num_wp_comments ) ) {
				// Only show the Discourse comments.
				return WPDISCOURSE_PATH . 'templates/comments.php';
			} else {
				// Show the Discourse comments then show the existing WP comments (in $old).
				include WPDISCOURSE_PATH . 'templates/comments.php';
				echo '<div class="discourse-existing-comments-heading">' . wp_kses_post( $options['existing-comments-heading'] ) . '</div>';

				return $old;
			}
		}

		// Show the existing WP comments.
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
				$count = ( 1 === intval( $count ) ) ? '1 Reply' : $count . ' Replies';
			}
		}

		return $count;
	}
}
