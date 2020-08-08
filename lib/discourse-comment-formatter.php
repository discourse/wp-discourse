<?php
/**
 * Formats Discourse comments.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseCommentFormatter;

use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Shared\TemplateFunctions;
use WPDiscourse\Shared\PluginUtilities;

/**
 * Class DiscourseCommentFormatter
 */
class DiscourseCommentFormatter {
	use PluginUtilities;
	use TemplateFunctions;

	/**
	 * Formats the Discourse comments for a given post.
	 *
	 * @param int $post_id The post_id to retrieve comments for.
	 *
	 * @return string
	 */
	public function format( $post_id ) {
		// Sync the comments.
		do_action( 'wpdc_sync_discourse_comments', $post_id );
		$options = $this->get_options();

		$custom = get_post_custom( $post_id );
		if ( empty( $custom['discourse_permalink'] ) || empty( $custom['discourse_comments_raw'] ) ) {

			return wp_kses_post( Templates::bad_response_html() );

		}

		$topic_data = json_decode( $custom['discourse_comments_raw'][0] );
		// The topic_id may not be available for posts that were published before version 1.4.0.
		$topic_id = get_post_meta( $post_id, 'discourse_topic_id', true );

		if ( ! empty( $topic_id ) && ! empty( $options['cache-html'] ) ) {
			$transient_key = "wpdc_comment_html_{$topic_id}";
			$html          = get_transient( $transient_key );

			if ( ! empty( $html ) ) {

				return $html;
			}
		}

		$permalink = (string) $custom['discourse_permalink'][0];

		if ( ! empty( $options['enable-sso'] ) && empty( $options['redirect-without-login'] ) ) {
			$permalink = esc_url( $options['url'] ) . '/session/sso?return_path=' . $permalink;
		}

		if ( ! empty( $options['discourse-link-text'] ) ) {
			$discourse_url_name = esc_html( $options['discourse-link-text'] );
		} else {
			$discourse_url_name = preg_replace( '(https?://)', '', esc_url( $options['url'] ) );
		}

		// Use custom datetime format string if provided, else global date format.
		$datetime_format = empty( $options['custom-datetime-format'] ) ? get_option( 'date_format' ) : $options['custom-datetime-format'];

		$more_replies_number = intval( ( $topic_data->filtered_posts_count - count( $topic_data->posts ) - 1 ) );
		$more_text           = esc_html( strtolower( $options['more-replies-more-text'] ) ) . ' ';
		if ( 0 >= $more_replies_number ) {
			$more_replies = '';
		} elseif ( 1 === $more_replies_number ) {
			$more_replies = '1 ' . $more_text . esc_html( strtolower( $options['single-reply-text'] ) );
		} else {
			$more_replies = $more_replies_number . ' ' . $more_text . esc_html( strtolower( $options['many-replies-text'] ) );
		}

		$discourse_url     = esc_url( $options['url'] );
		$comments_html     = '';
		$participants_html = '';

		$discourse_posts_count = ! empty( $topic_data->filtered_posts_count ) ? $topic_data->filtered_posts_count : 0;
		$posts                 = $topic_data->posts;
		$participants          = $topic_data->participants;

		if ( count( $posts ) > 0 ) {
			$displayed_comment_number = 0;
			foreach ( $posts as $post ) {
				$even           = 0 === $displayed_comment_number % 2;
				$post_url       = esc_url( $permalink . '/' . $post->post_number );
				$name           = ! empty( $post->name ) ? $post->name : '';
				$comment_html   = wp_kses_post( Templates::comment_html( $even ) );
				$comment_html   = str_replace( '{discourse_url}', $discourse_url, $comment_html );
				$comment_html   = str_replace( '{discourse_url_name}', $discourse_url_name, $comment_html );
				$comment_html   = str_replace( '{topic_url}', $permalink, $comment_html );
				$comment_html   = str_replace( '{comment_url}', $post_url, $comment_html );
				$avatar_url     = $this->avatar( $post->avatar_template, apply_filters( 'discourse_post_avatar_template_size', 64 ) );
				$comment_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $comment_html );
				$user_url       = $this->homepage( $options['url'], $post );
				$comment_html   = str_replace( '{user_url}', esc_url( $user_url ), $comment_html );
				$comment_html   = str_replace( '{username}', esc_html( $post->username ), $comment_html );
				$comment_html   = str_replace( '{fullname}', esc_html( $name ), $comment_html );
				$comment_body   = $this->convert_relative_urls_to_absolute( $discourse_url, $post->cooked );
				$comment_body   = $this->add_poll_links( $comment_body, $post_url );
				$comment_body   = $this->fix_youtube_onebox_links( $comment_body );
				$comment_body   = wp_kses_post( apply_filters( 'wpdc_comment_body', $comment_body ) );
				$comment_body   = str_replace( '{comment_url}', $post_url, $comment_body );
				$comment_html   = str_replace( '{comment_body}', $comment_body, $comment_html );
				$comment_html   = str_replace( '{comment_created_at}', $this->format_date( $post->created_at, $datetime_format ), $comment_html );
				$comments_html .= $comment_html;
				$displayed_comment_number++;
			}
			foreach ( $participants as $participant ) {
				$participant_html   = wp_kses_post( Templates::participant_html() );
				$participant_html   = str_replace( '{discourse_url}', $discourse_url, $participant_html );
				$participant_html   = str_replace( '{discourse_url_name}', $discourse_url_name, $participant_html );
				$participant_html   = str_replace( '{topic_url}', $permalink, $participant_html );
				$avatar_url         = $this->avatar( $participant->avatar_template, apply_filters( 'discourse_participant_avatar_template_size', 64 ) );
				$participant_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $participant_html );
				$user_url           = $this->homepage( $options['url'], $participant );
				$participant_html   = str_replace( '{user_url}', esc_url( $user_url ), $participant_html );
				$participant_html   = str_replace( '{username}', esc_html( $participant->username ), $participant_html );
				$participants_html .= $participant_html;
			}
			$discourse_html = wp_kses_post( Templates::replies_html() );
			$discourse_html = str_replace( '{more_replies}', $more_replies, $discourse_html );
		} else {
			$discourse_html = wp_kses_post( Templates::no_replies_html( $discourse_posts_count ) );
		}// End if().

		$discourse_html = str_replace( '{discourse_url}', $discourse_url, $discourse_html );
		$discourse_html = str_replace( '{discourse_url_name}', $discourse_url_name, $discourse_html );
		$discourse_html = str_replace( '{topic_url}', $permalink, $discourse_html );
		$discourse_html = str_replace( '{comments}', $comments_html, $discourse_html );
		$discourse_html = str_replace( '{participants}', $participants_html, $discourse_html );

		do_action( 'wp_discourse_after_comments', $topic_id );

		if ( isset( $transient_key ) ) {
			set_transient( $transient_key, $discourse_html, 10 * MINUTE_IN_SECONDS );
			$transient_keys = get_option( 'wpdc_cached_html_keys' ) ? get_option( 'wpdc_cached_html_keys' ) : array();
			if ( ! in_array( $transient_key, $transient_keys, true ) ) {
				$transient_keys[] = $transient_key;
				update_option( 'wpdc_cached_html_keys', $transient_keys );
			}
		}

		return $discourse_html;
	}

	/**
	 * Displays a link to the associated Discourse topic.
	 *
	 * @param int $post_id The post_id that the link is being displayed for.
	 *
	 * @return string
	 */
	public function comment_link( $post_id ) {
		$options             = $this->get_options();
		$discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true );
		$new_tab             = ! empty( $options['discourse-new-tab'] ) ? ' target="_blank" rel="noreferrer noopener"' : '';

		if ( empty( $discourse_permalink ) ) {

			return new \WP_Error( 'wpdc_configuration_error', 'The join link can not be added for the post. It is missing the discourse_permalink metadata.' );
		}

		if ( ! empty( $options['enable-sso'] ) && empty( $options['redirect-without-login'] ) ) {
			$discourse_permalink = $options['url'] . '/session/sso?return_path=' . $discourse_permalink;
		}
		$comments_count = get_comments_number( $post_id );

		switch ( $comments_count ) {
			case 0:
				$link_text = $options['no-comments-text'];
				break;
			case 1:
				$link_text = '1 ' . $options['comments-singular-text'];
				break;
			default:
				$link_text = $comments_count . ' ' . $options['comments-plural-text'];
		}

		$link_text = apply_filters( 'wpdc_join_discussion_link_text', $link_text, $comments_count, $post_id );

		return '<div class="wpdc-join-discussion"><a class="wpdc-join-discussion-link" href="' . esc_url_raw( $discourse_permalink ) . '"' . $new_tab . '>' . esc_html( $link_text ) . '</a></div>';
	}
}
