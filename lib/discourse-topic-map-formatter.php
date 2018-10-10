<?php
/**
 * Formats the Discourse topic-map.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseTopicMapFormatter;
use WPDiscourse\Templates\HTMLTemplates as Templates;
use WPDiscourse\Shared\TemplateFunctions;
use WPDiscourse\Shared\PluginUtilities;

/**
 * Class DiscourseTopicMapFormatter
 */
class DiscourseTopicMapFormatter {
	use PluginUtilities;
	use TemplateFunctions;

	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = $this->get_options();
	}

	public function format( $post_id ) {
		do_action( 'wpdc_sync_discourse_comments', $post_id );

		$topic_data = get_post_meta( $post_id, 'discourse_comments_raw', true );

		$topic_map_html = wp_kses_post( Templates::topic_map_html() );

		$discourse_url     = esc_url( $this->options['url'] );
		$permalink = get_post_meta( $post_id, 'discourse_permalink', true );
		if ( ! empty( $this->options['enable-sso'] ) && empty( $this->options['redirect-without-login'] ) ) {
			$permalink = esc_url( $this->options['url'] ) . '/session/sso?return_path=' . $permalink;
		}

		$discourse_posts_count = ! empty( $topic_data->posts_count ) ? $topic_data->posts_count : 0;
		$participants = $topic_data->participants;
		$popular_links = ! empty( $topic_data->popular_links ) ? $topic_data->popular_links : NULL;
		$popular_links_count = count( $popular_links );

		$popular_links_html = '';
		if ( $popular_links_count > 0 ) {
			foreach ( $popular_links as $link ) {
				$popular_link_html   = wp_kses_post( Templates::popular_link_html() );
				$popular_link_html   = str_replace( '{popular_link}', $link->url, $popular_link_html );
				$popular_links_html .= $popular_link_html;
			}
		}

		$participants_html = '';

		foreach ( $participants as $participant ) {
			$participant_html   = wp_kses_post( Templates::frequent_posters_html() );
			$participant_html   = str_replace( '{discourse_url}', $discourse_url, $participant_html );
			$participant_html   = str_replace( '{topic_url}', $permalink, $participant_html );
			$avatar_url         = $this->avatar( $participant->avatar_template, apply_filters( 'discourse_participant_avatar_template_size', 64 ), $this->options['url'] );
			$participant_html   = str_replace( '{avatar_url}', esc_url( $avatar_url ), $participant_html );
			$user_url           = $this->homepage( $this->options['url'], $participant );
			$participant_html   = str_replace( '{user_url}', esc_url( $user_url ), $participant_html );
			$participant_html   = str_replace( '{username}', esc_html( $participant->username ), $participant_html );
			$participants_html .= $participant_html;
		}

		$topic_map_html = str_replace( '{replies_count}', $discourse_posts_count - 1, $topic_map_html );
		$topic_map_html = str_replace( '{participants_count}', count( $participants ), $topic_map_html );
		$topic_map_html = str_replace( '{links_count}', $popular_links_count, $topic_map_html );
		$last_poster = $topic_data->last_poster;
		$original_poster = $topic_data->created_by;
		$topic_map_html = str_replace( '{last_reply_relative_time}', $this->relative_time($topic_data->last_posted_at), $topic_map_html );
		// Todo: add a filter to the avatar size.
		$topic_map_html = str_replace( '{last_reply_user_avatar}', $this->avatar( $last_poster->avatar_template, 20, $this->options['url']), $topic_map_html );
		$topic_map_html = str_replace( '{last_reply_user_username}', $last_poster->username, $topic_map_html );
		$topic_map_html = str_replace( '{post_created_relative_time}', $this->relative_time($topic_data->created_at), $topic_map_html );
		$topic_map_html = str_replace( '{post_created_user_avatar}', $this->avatar( $original_poster->avatar_template, 20, $this->options['url'] ), $topic_map_html );
		$topic_map_html = str_replace( '{post_created_user_username}', $original_poster->username, $topic_map_html );
		$topic_map_html = str_replace( '{popular_links}', $popular_links_html, $topic_map_html );
		$topic_map_html = str_replace( '{participants}', $participants_html, $topic_map_html );

		return $topic_map_html;
	}
}