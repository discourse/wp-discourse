/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import comments from "./comments.js";
import aAvatarUrl from "./avatars/a_240.png";
import bAvatarUrl from "./avatars/b_240.png";
import cAvatarUrl from "./avatars/c_240.png";
import parse from 'html-react-parser';
import { format } from "date-fns";

/**
 * WP Discourse Comments Placeholder
 */

export default function WPDiscourseCommentsPlaceholder( props ) {
  const avatarUrls = [
    aAvatarUrl,
    bAvatarUrl,
    cAvatarUrl
  ];

  const mapAvatarUrls = (objects) => {
    return objects.map((obj, i) => {
      obj.avatar_url = avatarUrls[i];
      return obj;
    });
  }

  const commentList = () => {
    return mapAvatarUrls(comments.posts)
      .map((comment) => (commentTemplate(comment)));
  }

  const participantList = () => {
    return mapAvatarUrls(comments.participants)
      .map((participant) => (avatarTemplate(participant, '25')));
  }

  const commentTemplate = (comment) => {
    let date = new Date(comment.created_at);
    let createdAt = format(date, "MMMM do, yyyy");

    return (
      <li class='comment'>
        <article class="comment-body">
          <footer class="comment-meta">
            <div class="comment-author vcard">
              { avatarTemplate(comment, '64' ) }
              <b class="fn">
                <a href="https://discourse.mysite.com/t/1"
                   rel="external"
                   class="url">
                {comment.username}
                </a>
              </b>
              <span class="says screen-reader-text">
                { __( 'says:', 'wp-discourse' ) }
              </span>
            </div>
            <div class="comment-metadata">
              <time datetime={createdAt}>{createdAt}</time>
            </div>
          </footer>
          <div class="comment-content">{parse(comment.cooked)}</div>
        </article>
      </li>
    );
  }

  const avatarTemplate = (obj, size) => {
    const avatarClass = `avatar avatar-${size} photo avatar-default`;
    return (
      <img
        alt={obj.username}
        src={obj.avatar_url}
        class={avatarClass}
        height={size}
  			width={size} />
    );
  }

	return (
    <div id="comments" class="comments-area discourse-comments-area">
			<div class="comments-title-wrap">
				<h2 class="comments-title discourse-comments-title">{ __( 'Notable Replies', 'wp-discourse' ) }</h2>
			</div>
			<ol class="comment-list">{ commentList() }</ol>
			<div class="respond comment-respond">
				<h3 id="reply-title" class="comment-reply-title">
					{ __( 'Continue the discussion at ', 'wp-discourse' ) }
					<a
            class="wpdc-discourse-topic-link"
            target="_blank"
            rel="noreferrer noopener"
            href={ props.discourse_url }>
            { props.discourse_url }
          </a>
				</h3>
				<div class="comment-reply-title">
					<h4 class="discourse-participants">{ __( 'Participants', 'wp-discourse' ) }</h4>
					<p>{ participantList() }</p>
				</div>
			</div>
		</div>
	);
}
