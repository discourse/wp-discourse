<?php
/**
 * The template for comments.
 *
 * @package WPDiscourse
 */

use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

$custom = get_post_custom();

// If, when a new post is published to Discourse, there is not a valid response from
// the forum, the `discourse_permalink` key will not be set. Display the `bad_response_html` template.
if ( ! array_key_exists( 'discourse_permalink', $custom ) ) {
	echo wp_kses_post( Templates::bad_response_html() );

} else {
	$options       = get_option( 'discourse' );
	$is_enable_sso = ( isset( $options['enable-sso'] ) && 1 === intval( $options['enable-sso'] ) );
	$permalink     = (string) $custom['discourse_permalink'][0];
	if ( $is_enable_sso ) {
		$permalink = esc_url( $options['url'] ) . '/session/sso?return_path=' . $permalink;
	}
	$discourse_url_name = preg_replace( '(https?://)', '', esc_url( $options['url'] ) );
	if ( isset( $custom['discourse_comments_raw'] ) ) {
		$discourse_info = json_decode( $custom['discourse_comments_raw'][0] );
	} else {
		$discourse_info = array();
	}
	$defaults = array(
		'posts_count'  => 0,
		'posts'        => array(),
		'participants' => array(),
	);

	// Add <time> tag to WP allowed html tags.
	global $allowedposttags;
	$allowedposttags['time'] = array( 'datetime' => array() );

	// Use custom datetime format string if provided, else global date format.
	$datetime_format = '' === $options['custom-datetime-format'] ? get_option( 'date_format' ) : $options['custom-datetime-format'];

	// Add some protection in the event our metadata doesn't look how we expect it to.
	$discourse_info = (object) wp_parse_args( (array) $discourse_info, $defaults );

	$more_replies = intval( ( $discourse_info->posts_count - count( $discourse_info->posts ) - 1 ) );
	$more         = ( 0 === count( $discourse_info->posts ) ) ? '' : 'more ';

	if ( 0 === $more_replies ) {
		$more_replies = '';
	} elseif ( 1 === $more_replies ) {
		$more_replies = '1 ' . $more . 'reply';
	} else {
		$more_replies = $more_replies . ' ' . $more . 'replies';
	}

	$discourse_url     = esc_url( $options['url'] );
	$discourse_html    = '';
	$comments_html     = '';
	$participants_html = '';

	if ( count( $discourse_info->posts ) > 0 ) {
		foreach ( $discourse_info->posts as &$post ) {
			$comment_html = wp_kses_post( Templates::comment_html() );
			$comment_html = str_replace( '{discourse_url}', $discourse_url, $comment_html );
			$comment_html = str_replace( '{discourse_url_name}', $discourse_url_name, $comment_html );
			$comment_html = str_replace( '{topic_url}', $permalink, $comment_html );
			$avatar_url   = DiscourseUtilities::avatar( $post->avatar_template, 64 );
			$comment_html = str_replace( '{avatar_url}', esc_url( $avatar_url ), $comment_html );
			$user_url     = DiscourseUtilities::homepage( $options['url'], $post );
			$comment_html = str_replace( '{user_url}', esc_url( $user_url ), $comment_html );
			$comment_html = str_replace( '{username}', esc_html( $post->username ), $comment_html );
			$comment_html = str_replace( '{fullname}', esc_html( $post->name ), $comment_html );
			$comment_body = DiscourseUtilities::sanitize_for_environment( $post->cooked );
			$comment_html = str_replace( '{comment_body}', $comment_body, $comment_html );
			$comment_html = str_replace( '{comment_created_at}', mysql2date( $datetime_format, get_date_from_gmt( $post->created_at ) ), $comment_html );
			$comments_html .= $comment_html;
		}
		foreach ( $discourse_info->participants as &$participant ) {
			$participant_html = wp_kses_post( Templates::participant_html() );
			$participant_html = str_replace( '{discourse_url}', $discourse_url, $participant_html );
			$participant_html = str_replace( '{discourse_url_name}', $discourse_url_name, $participant_html );
			$participant_html = str_replace( '{topic_url}', $permalink, $participant_html );
			$avatar_url       = DiscourseUtilities::avatar( $participant->avatar_template, 64 );
			$participant_html = str_replace( '{avatar_url}', esc_url( $avatar_url ), $participant_html );
			$user_url         = DiscourseUtilities::homepage( $options['url'], $participant );
			$participant_html = str_replace( '{user_url}', esc_url( $user_url ), $participant_html );
			$participant_html = str_replace( '{username}', esc_html( $participant->username ), $participant_html );
			$participants_html .= $participant_html;
		}
		$discourse_html = wp_kses_post( Templates::replies_html() );
		$discourse_html = str_replace( '{more_replies}', $more_replies, $discourse_html );
	} else {
		$discourse_html = wp_kses_post( Templates::no_replies_html() );
	}
	$discourse_html = str_replace( '{discourse_url}', $discourse_url, $discourse_html );
	$discourse_html = str_replace( '{discourse_url_name}', $discourse_url_name, $discourse_html );
	$discourse_html = str_replace( '{topic_url}', $permalink, $discourse_html );
	$discourse_html = str_replace( '{comments}', $comments_html, $discourse_html );
	$discourse_html = str_replace( '{participants}', $participants_html, $discourse_html );
	$discourse_html = DiscourseUtilities::sanitize_for_environment( $discourse_html );
	echo $discourse_html;
}
