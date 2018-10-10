<?php
/**
 * Returns HTML templates used for publishing to Discourse and for displaying comments on the WordPress site.
 *
 * Templates and implementation copied from @aliso's commit:
 * https://github.com/10up/wp-discourse/commit/5c9d43c4333e136204d5a3b07192f4b368c3f518.
 *
 * @link https://github.com/discourse/wp-discourse/blob/master/lib/html-templates.php
 * @package WPDiscourse
 */

namespace WPDiscourse\Templates;

/**
 * Class HTMLTemplates
 */
class HTMLTemplates {

	/**
	 * Gets the 'discourse_configurable_text' options.
	 *
	 * @param string $option The option key.
	 *
	 * @return string
	 */
	protected static function get_text_options( $option ) {
		$text_options = get_option( 'discourse_configurable_text' );

		$text = ! empty( $text_options[ $option ] ) ? $text_options[ $option ] : '';

		return $text;
	}

	/**
	 * Sets the value of the target attribute.
	 *
	 * @return string
	 */
	protected static function new_tab() {
		$comment_options = get_option( 'discourse_comment' );

		return ! empty( $comment_options['discourse-new-tab'] );
	}

	/**
	 * HTML template for replies.
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 * Available tags:
	 * {comments}, {discourse_url}, {discourse_url_name},
	 * {topic_url}, {more_replies}, {participants}
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function replies_html() {
		ob_start();
		?>
		<div id="comments" class="comments-area discourse-comments-area">
			<h2 class="comments-title discourse-comments-title"><?php echo esc_html( self::get_text_options( 'notable-replies-text' ) ); ?></h2>
			<ol class="comment-list">{comments}</ol>
			<div class="respond comment-respond">
				<h3 id="reply-title" class="comment-reply-title">
					<?php echo esc_html( self::get_text_options( 'continue-discussion-text' ) . ' ' ); ?>
					<?php self::discourse_topic_link( self::new_tab() ); ?>
				</h3>
				<p class="more-replies">{more_replies}</p>
				<div class="comment-reply-title">
					<h4 class="discourse-participants"><?php echo esc_html( self::get_text_options( 'participants-text' ) ); ?></h4>
					<p>{participants}</p>
				</div>
			</div>
		</div>
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_replies_html', $output );
	}

	/**
	 * HTML template for no replies.
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 * Available tags:
	 * {comments}, {discourse_url}, {discourse_url_name}, {topic_url}
	 *
	 * @param null/string $discourse_comments_number The number of comments that are displayed on Discourse.
	 * @static
	 * @return string
	 */
	public static function no_replies_html( $discourse_comments_number = null ) {
		ob_start();
		?>
		<div id="comments" class="comments-area">
			<div class="respond comment-respond">
				<h3 id="reply-title" class="comment-reply-title">
					<?php
					$text = $discourse_comments_number > 1 ? self::get_text_options( 'join-discussion-text' ) : self::get_text_options( 'start-discussion-text' );
					?>
					<?php echo esc_html( $text ) . ' '; ?>
					<?php self::discourse_topic_link( self::new_tab() ); ?>
					</h3>
			</div>
		</div>
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_no_replies_html', $output );
	}

	/**
	 * The template that is displayed in the comments section after a post is created
	 * with bad credentials.
	 * This template is displayed in the comments section when there is no `discourse_permalink`
	 * index in the response returned from `Discourse::sync_to_discourse_work`
	 *
	 * Can be customized in the theme using the filter provided.
	 *
	 * @return string
	 */
	public static function bad_response_html() {
		ob_start();
		?>
		<div class="respond comment-respond">
			<div class="comment-reply-title discourse-no-connection-notice">
				<p><?php echo esc_html( self::get_text_options( 'comments-not-available-text' ) ); ?></p>
			</div>
		</div>
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_no_connection_html', $output );
	}

	/**
	 * HTML template for each comment
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 * Available tags:
	 * {discourse_url}, {discourse_url_name}, {topic_url},
	 * {avatar_url}, {user_url}, {username}, {fullname},
	 * {comment_body}, {comment_created_at}, {comment_url}
	 *
	 * @param bool $even Whether it's an even comment number.
	 * @static
	 * @return string
	 */
	public static function comment_html( $even = true ) {
		ob_start();
		?>
		<li class="comment <?php echo $even ? 'even' : 'odd'; ?> depth-1">
			<article class="comment-body">
				<footer class="comment-meta">
					<div class="comment-author vcard">
						<img alt="" src="{avatar_url}" class="avatar avatar-64 photo avatar-default"
							 height="64"
							 width="64">
						<b class="fn"><a href="{topic_url}" rel="external"
										 class="url">{username}</a></b>
						<span class="says screen-reader-text"><?php echo esc_html( 'says:', 'wp-discourse' ); ?></span><!-- screen reader text -->
					</div>
					<div class="comment-metadata">
						<time datetime="{comment_created_at}">{comment_created_at}</time>
					</div>
				</footer>
				<div class="comment-content">{comment_body}</div>
			</article>
		</li>
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_comment_html', $output );
	}

	/**
	 * HTML template for each participant
	 *
	 * Can be customized from within a theme using the filter provided.
     *
	 *
	 * Available tags:
	 * {discourse_url}, {discourse_url_name}, {topic_url},
	 * {avatar_url}, {user_url}, {username}
	 *
	 * @static
	 * @return string
	 */
	public static function participant_html() {
		ob_start();
		?>
		<img alt="" src="{avatar_url}" class="avatar avatar-25 photo avatar-default" height="25"
			 width="25">
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_participant_html', $output );
	}

	/**
	 * HTML template for published byline
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 * Available tags:
	 * {excerpt}, {blogurl}, {author}, {thumbnail}, {featuredimage}
	 *
	 * @static
	 * @return string
	 */
	public static function publish_format_html() {
		ob_start();
		?>
		<small><?php echo esc_html( self::get_text_options( 'published-at-text' ) ); ?>
			{blogurl}
		</small><br>{excerpt}
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_publish_format_html', $output );
	}

	/**
	 * HTML template for the link to the Discourse topic.
	 *
	 * Available tags:
	 * {topic_url}, {discourse_url_name}
	 *
	 * @param bool $new_tab Whether or not to open the link in a new tab.
	 * @static
	 */
	public static function discourse_topic_link( $new_tab ) {
		if ( $new_tab ) {
			?>
			<a class="wpdc-discourse-topic-link" target="_blank" href="{topic_url}">{discourse_url_name}</a>
			<?php
		} else {
			?>
			<a class="wpdc-discourse-topic-link" href="{topic_url}">{discourse_url_name}</a>
			<?php
		}
	}

	/**
	 * HTML template for each popular link
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 * Available tags:
	 * {popular_link}
	 *
	 * @static
	 * @return string
	 */
	public static function popular_link_html() {
		ob_start();
		?>
		<a class="popular-link" href={popular_link}>{popular_link}</a>
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_popular_link_html', $output );
	}

	/**
	 * HTML template for each participant
	 *
	 * Can be customized from within a theme using the filter provided.
	 *
	 *
	 * Available tags:
	 * {discourse_url}, {topic_url},
	 * {avatar_url}, {user_url}, {username}
	 *
	 * @static
	 * @return string
	 */
	public static function frequent_posters_html() {
		ob_start();
		?>
        <img alt="" src="{avatar_url}" class="avatar avatar-25 photo avatar-default" height="25"
             width="25">
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_frequent_posters_html', $output );
	}

	public static function topic_map_html() {
		ob_start();
		?>
        <div class="topic-map">
            <section class="map map-collapsed">
                <nav class="buttons">
                    <button class="widget-button btn btn no-text btn-icon" id="toggle-expand">
                        <span id="arrow" class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </nav>
                <ul class="clearfix">
                    <li>
                        <h4><?php echo esc_html( self::get_text_options( 'topic-map-created-text' ) ); ?></h4>
                        <div class="topic-map-post created-at">
                            <a class="trigger-user-card">
                                <img alt="" width="20" height="20" src="{post_created_user_avatar}"
                                     title="{post_created_user_username}" class="avatar">
                            </a>
                            <span class="relative-date">{post_created_relative_time}</span>
                        </div>
                    </li>
                    <li>
                        <div>
                            <h4><?php echo esc_html( self::get_text_options( 'topic-map-last-reply-text' ) ); ?></h4>
                            <div class="topic-map-post last-reply">
                                <a class="trigger-user-card">
                                    <img alt="" width="20" height="20" src="{last_reply_user_avatar}"
                                         title="{last_reply_user_username}" class="avatar">
                                </a>
                                <span class="relative-date">{last_reply_relative_time}</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <span class="number">{replies_count}</span>
                        <h4><?php echo esc_html( self::get_text_options( 'topic-map-replies-text' ) ); ?></h4>
                    </li>
                    <li class="secondary">
                        <span class="number">{participants_count}</span>
                        <h4><?php echo esc_html( self::get_text_options( 'topic-map-users-text' ) ); ?></h4>
                    </li>
                    <li class="secondary">
                        <span class="number">{links_count}</span>
                        <h4><?php echo esc_html( self::get_text_options( 'topic-map-links-text' ) ); ?></h4>
                    </li>
                </ul>
            </section>
            <section class="map-expanded" id="map-expanded" style="display:none">
                <div class="frequent-posters">
                    <span class="topic-map-span"><?php echo esc_html( self::get_text_options( 'topic-map-frequent-posters-text' ) ); ?></span>
                    <p>{participants}</p>
                </div>
                <div class="popular-links">
                    <span class="topic-map-span"><?php echo esc_html( self::get_text_options( 'topic-map-popular-links-text' ) ); ?></span>
                    <p class="popular-links-p">{popular_links}</p>
                </div>
            </section>
        </div>
		<?php
		$topic_map =  ob_get_clean();

		return $topic_map;
    }
}
