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
	 * @return mixed|void
	 */
	public static function replies_html() {
		ob_start();
		?>
		<div id="comments" class="comments-area">
			<h2 class="comments-title"><?php esc_html_e( 'Notable Replies', 'wp-discourse' ); ?></h2>
			<ol class="comment-list">{comments}</ol>
			<div class="respond comment-respond">
				<h3 id="reply-title" class="comment-reply-title">
					<a href="{topic_url}"><?php esc_html_e( 'Continue the discussion', 'wp-discourse' ); ?>
					</a><?php esc_html_e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}
				</h3>
				<p class="more-replies">{more_replies}</p>
				<p class="comment-reply-title">{participants}</p>
			</div><!-- #respond -->
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
	 * @static
	 * @return mixed|void
	 */
	public static function no_replies_html() {
		ob_start();
		?>
		<div id="comments" class="comments-area">
			<div class="respond comment-respond">
				<h3 id="reply-title" class="comment-reply-title"><a href="{topic_url}">
						<?php esc_html_e( 'Start the discussion', 'wp-discourse' ); ?>
					</a><?php esc_html_e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}</h3>
			</div><!-- #respond -->
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
	 * @return mixed|void
	 */
	public static function bad_response_html() {
		ob_start();
		?>
		<div class="respond comment-respond">
			<div class="comment-reply-title discourse-no-connection-notice">
				<p><?php esc_html_e( 'Comments are not enabled for this post.', 'wp-discourse' ); ?></p>
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
	 * @static
	 * @return mixed|void
	 */
	public static function comment_html() {
		ob_start();
		?>
		<li class="comment even thread-even depth-1">
			<article class="comment-body">
				<footer class="comment-meta">
					<div class="comment-author vcard">
						<img alt="" src="{avatar_url}" class="avatar avatar-64 photo avatar-default" height="64"
						     width="64">
						<b class="fn"><a href="{topic_url}" rel="external" class="url">{fullname}</a></b>
						<span class="says">says:</span>
					</div>
					<!-- .comment-author -->
					<div class="comment-metadata">
						<time pubdate="" datetime="{comment_created_at}">{comment_created_at}</time>
					</div>
					<!-- .comment-metadata -->
				</footer>
				<!-- .comment-meta -->
				<div class="comment-content">{comment_body}</div>
				<!-- .comment-content -->
			</article>
			<!-- .comment-body -->
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
	 * Available tags:
	 * {discourse_url}, {discourse_url_name}, {topic_url},
	 * {avatar_url}, {user_url}, {username}
	 *
	 * @static
	 * @return mixed|void
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
	 * @return mixed|void
	 */
	public static function publish_format_html() {
		ob_start();
		?>
		<small>Originally published at: {blogurl}</small><br>{excerpt}
		<?php
		$output = ob_get_clean();

		return apply_filters( 'discourse_publish_format_html', $output );
	}
}
