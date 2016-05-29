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
   * HTML template for replies.
   * 
   * Checks the connection before displaying the 'Continue the discussion' link.
   *
   * Can be customized from within a theme using the filter provided.
   *
   * Available tags:
   * {comments}, {discourse_url}, {discourse_url_name},
   * {topic_url}, {more_replies}, {participants}
   *
   * @static
   *
   * @param $connection_status
   *
   * @return mixed|void
   */
  public static function replies_html( $connection_status ) {
    ob_start();
    ?>
    <div id="comments" class="comments-area">
      <h2
        class="comments-title"><?php _e( 'Notable Replies', 'wp-discourse' ); ?></h2>
      <ol class="comment-list">{comments}</ol>
      <div class="respond comment-respond">

        <?php if ( $connection_status ) : ?>
          <h3 id="reply-title" class="comment-reply-title">
            <a href="{topic_url}">
              <?php _e( 'Continue the discussion', 'wp-discourse' ); ?>
            </a> <?php _e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}
          </h3>
          <p class="more-replies">{more_replies}</p>
        <?php else : ?>
          <h3 id="reply-title" class="comment-reply-title no-connection">
            <?php _e( 'We are currently not able to connect to our forum.', 'wp-discourse' ); ?>
          </h3>
          <p><?php _e( 'The site administrator has been notified. Please try again soon.' ) ?></p>

        <?php endif; ?>

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
   * Checks the connection status before displaying the 'Start the discussion' link.
   *
   * Can be customized from within a theme using the filter provided.
   *
   * Available tags:
   * {comments}, {discourse_url}, {discourse_url_name}, {topic_url}
   *
   * @static
   * @return mixed|void
   */
  public static function no_replies_html( $connection_status ) {
    ob_start();
    ?>
    <div id="comments" class="comments-area">
      <div class="respond comment-respond">

        <?php if ( $connection_status ) : ?>
          <h3 id="reply-title" class="comment-reply-title"><a
              href="{topic_url}">
              <?php _e( 'Start the discussion', 'wp-discourse' ); ?>
            </a><?php _e( ' at ', 'wp-discourse' ); ?>{discourse_url_name}
          </h3>
        <?php else : ?>
          <h3 id="reply-title" class="comment-reply-title no-connection">
            <?php _e( 'We would love to hear from you, but we are currently unable to ' .
                      'establish a connection with our forum.', 'wp-discourse' ); ?>
          </h3>
          <p class="no-connection">
            <?php _e( 'The site administrators have been notified. Please try again soon.', 'wp-discourse' ); ?>
          </p>
        <?php endif; ?>

      </div><!-- #respond -->
    </div>
    <?php
    $output = ob_get_clean();

    return apply_filters( 'discourse_no_replies_html', $output );
  }

  /**
   * The template that is used when a post is created with bad credentials.
   * 
   * @return mixed|void
   */
  public static function no_connection_html() {
    ob_start();
    ?>
    <div class="no-connection">
      <h3><?php _e( 'We are currently unable to connect with the Discourse forum. ' .
                    'The site administrator has been notified. Please try again later.', 'wp-discourse' ); ?></h3>
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