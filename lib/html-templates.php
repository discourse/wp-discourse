<?php
namespace WPDiscourse\Templates;
/**
 * Class HTMLTemplates
 *
 * Static methods to return HTML templates. Used in `templates/comments.php`
 *
 * Templates and implementation copied from @aliso's commit:
 * https://github.com/10up/wp-discourse/commit/5c9d43c4333e136204d5a3b07192f4b368c3f518
 */
class HTMLTemplates {

  /**
   * HTML template for comment numbers
   *
   * Can be customized from within the a theme using the filter provided.
   *
   * @param $total_comments
   * @param $displayed_comments
   *
   * @return mixed|void
   */
  public static function comment_count_html( $total_comments, $displayed_comments ) {
    ob_start();

    if ( $total_comments == 0 ) {
      echo '';

    } elseif ( $displayed_comments == 1 && $total_comments == 1 ) {
      echo '1 ' . __( 'comment', 'wp-discourse' );

    } elseif ( $displayed_comments == $total_comments ) {
      echo $displayed_comments . ' ' . __( 'comments', 'wp-discourse' );
    } else {
      echo __( 'displaying ', 'wp-discourse' ) . $displayed_comments . __( ' of ', 'wp-discourse' ) . $total_comments . __( ' comments', 'wp-discourse' );
    }

    $output = ob_get_clean();

    return apply_filters( 'discourse_comment_count_html', $output, $total_comments, $displayed_comments );
  }

  /**
   * HTML template for replies
   *
   * Can be customized from within a theme using the filter provided.
   *
   * Available tags:
   * {comments}, {discourse_url}, {discourse_url_name},
   * {topic_url}, {more_replies}, {participants}
   *
   * @static
   * @return mixed|void
   */
  public static function replies_html() {
    ob_start();
    ?>
    <div id="comments" class="comments-area discourse-comments">
      <h2 class="comments-title"><?php _e( 'Notable Replies', 'wp-discourse' ); ?></h2>
      <div class="comment-meta">{comments_count}</div>
      <ol class="comment-list">{comments}</ol>
      <div class="respond comment-respond">
        <h3 id="reply-title" class="comment-reply-title">
          <a href="{topic_url}"><?php _e( 'Continue the discussion', 'wp-discourse' ); ?>
          </a><?php _e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}
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
   * HTML template for no replies
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
            <?php _e( 'Start the discussion', 'wp-discourse' ); ?>
          </a><?php _e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}</h3>
      </div><!-- #respond -->
    </div>
    <?php
    $output = ob_get_clean();

    return apply_filters( 'discourse_no_replies_html', $output );
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
            <img alt="" src="{avatar_url}"
                 class="avatar avatar-64 photo avatar-default" height="64"
                 width="64">
            <b class="fn"><a href="{topic_url}" rel="external" class="url">{fullname}</a></b>
            <span class="says">says:</span>
          </div>
          <!-- .comment-author -->
          <div class="comment-metadata">
            <time pubdate="" datetime="{comment_created_at}">
              {comment_created_at}
            </time>
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
    <img alt="" src="{avatar_url}" class="avatar avatar-25 photo avatar-default"
         height="25" width="25">
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