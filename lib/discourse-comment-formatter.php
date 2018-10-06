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
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * DiscourseCommentFormatter constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

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

		$topic_data = get_post_meta( $post_id, 'discourse_comments_raw', true );
		$permalink = get_post_meta( $post_id, 'discourse_permalink', true );
		if ( empty( $topic_data ) || empty( $permalink ) ) {

			return wp_kses_post( Templates::bad_response_html() );
		}

		// The topic_id may not be available for posts that were published before version 1.4.0.
		$topic_id = get_post_meta( $post_id, 'discourse_topic_id', true );

		if ( ! empty( $topic_id ) && ! empty( $this->options['cache-html'] ) ) {
			$transient_key = "wpdc_comment_html_{$topic_id}";
			$html          = get_transient( $transient_key );

			if ( ! empty( $html ) ) {

				return $html;
			}
		}

		if ( ! empty( $this->options['enable-sso'] ) && empty( $this->options['redirect-without-login'] ) ) {
			$permalink = esc_url( $this->options['url'] ) . '/session/sso?return_path=' . $permalink;
		}

		if ( ! empty( $this->options['discourse-link-text'] ) ) {
			$discourse_url_name = esc_html( $this->options['discourse-link-text'] );
		} else {
			$discourse_url_name = preg_replace( '(https?://)', '', esc_url( $this->options['url'] ) );
		}

		// Use custom datetime format string if provided, else global date format.
		$datetime_format = empty( $this->options['custom-datetime-format'] ) ? get_option( 'date_format' ) : $this->options['custom-datetime-format'];

		// Todo: clean up use of $topic_data->posts_count.
		$more_replies_number = intval( ( $topic_data->posts_count - count( $topic_data->posts ) - 1 ) );
		$more_text           = esc_html( strtolower( $this->options['more-replies-more-text'] ) ) . ' ';
		if ( 0 >= $more_replies_number ) {
			$more_replies = '';
		} elseif ( 1 === $more_replies_number ) {
			$more_replies = '1 ' . $more_text . esc_html( strtolower( $this->options['single-reply-text'] ) );
		} else {
			$more_replies = $more_replies_number . ' ' . $more_text . esc_html( strtolower( $this->options['many-replies-text'] ) );
		}

		$discourse_url     = esc_url( $this->options['url'] );
		$comments_html     = '';
		$participants_html = '';

		$discourse_posts_count = ! empty( $topic_data->posts_count ) ? $topic_data->posts_count : 0;
		$posts                 = $topic_data->posts;
		$participants          = $topic_data->participants;
		$popular_links = ! empty( $topic_data->popular_links ) ? $topic_data->popular_links : NULL;
		$popular_links_count = count( $popular_links );

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
				$user_url       = $this->homepage( $this->options['url'], $post );
				$comment_html   = str_replace( '{user_url}', esc_url( $user_url ), $comment_html );
				$comment_html   = str_replace( '{username}', esc_html( $post->username ), $comment_html );
				$comment_html   = str_replace( '{fullname}', esc_html( $name ), $comment_html );
				$comment_body   = $this->convert_relative_urls_to_absolute( $discourse_url, $post->cooked );
				$comment_body   = $this->add_poll_links( $comment_body, $post_url );
				$comment_body   = wp_kses_post( apply_filters( 'wpdc_comment_body', $comment_body ) );
				$comment_body   = str_replace( '{comment_url}', $post_url, $comment_body );
				$comment_html   = str_replace( '{comment_body}', $comment_body, $comment_html );
				$comment_html   = str_replace( '{comment_created_at}', $this->format_date( $post->created_at, $datetime_format ), $comment_html );
				$comments_html .= $comment_html;
				$displayed_comment_number++;
			}
			// Should the participants section be included if the topic-map is being used?
			foreach ( $participants as $participant ) {
				$participant_html   = wp_kses_post( Templates::participant_html() );
				$participant_html   = str_replace( '{discourse_url}', $discourse_url, $participant_html );
				$participant_html   = str_replace( '{discourse_url_name}', $discourse_url_name, $participant_html );
				$participant_html   = str_replace( '{topic_url}', $permalink, $participant_html );
				$avatar_url         = $this->avatar( $participant->avatar_template, apply_filters( 'discourse_participant_avatar_template_size', 64 ) );
				$participant_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $participant_html );
				$user_url           = $this->homepage( $this->options['url'], $participant );
				$participant_html   = str_replace( '{user_url}', esc_url( $user_url ), $participant_html );
				$participant_html   = str_replace( '{username}', esc_html( $participant->username ), $participant_html );
				$participants_html .= $participant_html;
			}
			$discourse_html = ! empty( $this->options['include-topic-map'] ) ? wp_kses_post( Templates::topic_map_html() ) : "";
			$discourse_html .= wp_kses_post( Templates::replies_html() );
			$discourse_html = str_replace( '{more_replies}', $more_replies, $discourse_html );
		} else {
			//Todo: Should the topic map be included if there are no replies? On Discourse it isn't, but maybe should be on WordPress.
			$discourse_html = wp_kses_post( Templates::no_replies_html( $discourse_posts_count ) );
		}// End if().

		if ( ! empty( $this->options['include-topic-map'] ) ) {
			$popular_links_html = '';
			if ( $popular_links_count > 0 ) {
				foreach ( $popular_links as $link ) {
					$popular_link_html   = wp_kses_post( Templates::popular_link_html() );
					$popular_link_html   = str_replace( '{popular_link}', $link->url, $popular_link_html );
					$popular_links_html .= $popular_link_html;
				}
			}

			$discourse_html = str_replace( '{replies_count}', $discourse_posts_count - 1, $discourse_html );
			$discourse_html = str_replace( '{participants_count}', count( $participants ), $discourse_html );
			$discourse_html = str_replace( '{links_count}', $popular_links_count, $discourse_html );
			$last_poster = $topic_data->last_poster;
			$original_poster = $topic_data->created_by;
			$discourse_html = str_replace( '{last_reply_relative_time}', $this->relative_time($topic_data->last_posted_at), $discourse_html );
			// Todo: find a cleaner way of creating the avatar template. Make sure it works for hosted sites!!!
			$discourse_html = str_replace( '{last_reply_user_avatar}', $this->options['url'] . str_replace('{size}', 20, $last_poster->avatar_template), $discourse_html );
			$discourse_html = str_replace( '{last_reply_user_username}', $last_poster->username, $discourse_html );
			$discourse_html = str_replace( '{post_created_relative_time}', $this->relative_time($topic_data->created_at), $discourse_html );
			// Todo: find a cleaner way of creating the avatar template. Make sure it works for hosted sites!!!
			$discourse_html = str_replace( '{post_created_user_avatar}', $this->options['url'] . str_replace('{size}', 20, $original_poster->avatar_template), $discourse_html );
			$discourse_html = str_replace( '{post_created_user_username}', $original_poster->username, $discourse_html );
			$discourse_html = str_replace( '{popular_links}', $popular_links_html, $discourse_html );
		}// End if().

		$discourse_html = str_replace( '{discourse_url}', $discourse_url, $discourse_html );
		$discourse_html = str_replace( '{discourse_url_name}', $discourse_url_name, $discourse_html );
		$discourse_html = str_replace( '{topic_url}', $permalink, $discourse_html );
		$discourse_html = str_replace( '{comments}', $comments_html, $discourse_html );
		$discourse_html = str_replace( '{participants}', $participants_html, $discourse_html );

		do_action( 'wp_discourse_after_comments', $topic_id );

		// Todo: caching the comments is going to break the topic-map times. Don't cache the topic map?
		if ( isset( $transient_key ) ) {
			set_transient( $transient_key, $discourse_html, 12 * HOUR_IN_SECONDS );
			$transient_keys = get_option( 'wpdc_cached_html_keys' ) ? get_option( 'wpdc_cached_html_keys' ) : array();
			if ( ! in_array( $transient_key, $transient_keys, true ) ) {
				$transient_keys[] = $transient_key;
				update_option( 'wpdc_cached_html_keys', $transient_keys );
			}
		}

		return $discourse_html;
	}
}

