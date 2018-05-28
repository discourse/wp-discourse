<?php
/**
 * Formats Discourse comments.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseCommentFormatter;

use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Templates\TemplateFunctions as TemplateFunctions;
use WPDiscourse\Shared\PluginUtilities;

/**
 * Class DiscourseCommentFormatter
 */
class DiscourseCommentFormatter {
	use PluginUtilities;

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
		$custom = get_post_custom( $post_id );
		if ( empty( $custom['discourse_permalink'] ) ) {

			return wp_kses_post( Templates::bad_response_html() );

		} else {
			$permalink = (string) $custom['discourse_permalink'][0];

			if ( ! empty( $this->options['enable-sso'] ) && empty( $this->options['redirect-without-login'] ) ) {
				$permalink = esc_url( $this->options['url'] ) . '/session/sso?return_path=' . $permalink;
			}

			if ( ! empty( $this->options['discourse-link-text'] ) ) {
				$discourse_url_name = esc_html( $this->options['discourse-link-text'] );
			} else {
				$discourse_url_name = preg_replace( '(https?://)', '', esc_url( $this->options['url'] ) );
			}

			if ( isset( $custom['discourse_comments_raw'] ) ) {
				$topic_data = json_decode( $custom['discourse_comments_raw'][0] );
			} else {
				$topic_data = array();
			}

			$defaults = array(
				'posts_count'  => 0,
				'posts'        => array(),
				'participants' => array(),
			);

			// Use custom datetime format string if provided, else global date format.
			$datetime_format = empty( $this->options['custom-datetime-format'] ) ? get_option( 'date_format' ) : $this->options['custom-datetime-format'];

			// Add some protection in the event our metadata doesn't look how we expect it to.
			$topic_data = (object) wp_parse_args( (array) $topic_data, $defaults );

			$more_replies = intval( ( $topic_data->posts_count - count( $topic_data->posts ) - 1 ) );
			$more         = ( 0 === count( $topic_data->posts ) ) ? '' : esc_html( strtolower( $this->options['more-replies-more-text'] ) ) . ' ';

			if ( 0 === $more_replies ) {
				$more_replies = '';
			} elseif ( 1 === $more_replies ) {
				$more_replies = '1 ' . $more . esc_html( strtolower( $this->options['single-reply-text'] ) );
			} else {
				$more_replies = $more_replies . ' ' . $more . esc_html( strtolower( $this->options['many-replies-text'] ) );
			}

			$discourse_url         = esc_url( $this->options['url'] );
			$comments_html         = '';
			$participants_html     = '';
			$topic_id              = ! empty( $topic_data->id ) ? $topic_data->id : null;
			$discourse_posts_count = ! empty( $topic_data->posts_count ) ? $topic_data->posts_count : 0;

			$posts = $topic_data->posts;
			$participants = $topic_data->participants;

			if ( count( $posts ) > 0 ) {
				foreach ( $posts as $post ) {
					$comment_html   = wp_kses_post( Templates::comment_html() );
					$comment_html   = str_replace( '{discourse_url}', $discourse_url, $comment_html );
					$comment_html   = str_replace( '{discourse_url_name}', $discourse_url_name, $comment_html );
					$comment_html   = str_replace( '{topic_url}', $permalink, $comment_html );
					$comment_html   = str_replace( '{comment_url}', $permalink . '/' . $post->post_number, $comment_html );
					$avatar_url     = TemplateFunctions::avatar( $post->avatar_template, 64 );
					$comment_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $comment_html );
					$user_url       = TemplateFunctions::homepage( $this->options['url'], $post );
					$comment_html   = str_replace( '{user_url}', esc_url( $user_url ), $comment_html );
					$comment_html   = str_replace( '{username}', esc_html( $post->username ), $comment_html );
					$comment_html   = str_replace( '{fullname}', esc_html( $post->name ), $comment_html );
					$comment_body   = TemplateFunctions::convert_relative_urls_to_absolute( $discourse_url, $post->cooked );
					$comment_body   = TemplateFunctions::add_poll_links( $post->cooked, $permalink . '/'. $post->post_number );
					$comment_body   = wp_kses_post( apply_filters( 'wpdc_comment_body', $comment_body ) );
					$comment_body   = str_replace( '{comment_url}', $permalink . '/' . $post->post_number, $comment_body );
					$comment_html   = str_replace( '{comment_body}', $comment_body, $comment_html );
					$comment_html   = str_replace( '{comment_created_at}', TemplateFunctions::format_date( $post->created_at, $datetime_format ), $comment_html );
					$comments_html .= $comment_html;
				}
				foreach ( $participants as $participant ) {
					$participant_html   = wp_kses_post( Templates::participant_html() );
					$participant_html   = str_replace( '{discourse_url}', $discourse_url, $participant_html );
					$participant_html   = str_replace( '{discourse_url_name}', $discourse_url_name, $participant_html );
					$participant_html   = str_replace( '{topic_url}', $permalink, $participant_html );
					$avatar_url         = TemplateFunctions::avatar( $participant->avatar_template, 64 );
					$participant_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $participant_html );
					$user_url           = TemplateFunctions::homepage( $this->options['url'], $participant );
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

			return $discourse_html;
		}// End if().
	}
}
