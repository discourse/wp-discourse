<?php
/**
 * Tempate utility functions.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Shared;

/**
 * Trait TemplateFunctions
 */
trait TemplateFunctions {

	/**
	 * Returns the user's Discourse homepage.
	 *
	 * @param string $url The base URL of the Discourse forum.
	 * @param object $post The Post object.
	 *
	 * @return string
	 */
	protected function homepage( $url, $post ) {
		return $url . '/users/' . strtolower( $post->username );
	}

	/**
	 * Substitutes the value for `$size` into the template.
	 *
	 * @param string $template The avatar template.
	 * @param int    $size The size of the avarar.
	 *
	 * @return mixed
	 */
	protected function avatar( $template, $size ) {
		return str_replace( '{size}', $size, $template );
	}

	/**
	 * Replaces relative image src with absolute.
	 *
	 * This function may not be required anymore.
	 * See: https://meta.discourse.org/t/can-emoji-be-rendered-with-absolute-urls/47250
	 *
	 * @param string $url The base url of the forum.
	 * @param string $content The content to be checked.
	 *
	 * @return mixed
	 */
	protected function convert_relative_img_src_to_absolute( $url, $content ) {
		if ( preg_match( "/<img\s*src\s*=\s*[\'\"]?(https?:)?\/\//i", $content ) ) {
			return $content;
		}

		$search  = '#<img src="((?!\s*[\'"]?(?:https?:)?\/\/)\s*([\'"]))?#';
		$replace = "<img src=\"{$url}$1";

		return preg_replace( $search, $replace, $content );
	}

	/**
	 * Converts relative URLs retured from Discourse to absolute URLs with DOMDocument.
	 *
	 * Checks if libxml is loaded. If not, calls convert_relative_img_src_to_absolute.
	 *
	 * @param string $url The Discourse URL.
	 * @param string $content The content to be parsed.
	 *
	 * @return mixed|string
	 */
	protected function convert_relative_urls_to_absolute( $url, $content ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $this->convert_relative_img_src_to_absolute( $url, $content );
		}

		// Allows parsing misformed html. Save the previous value of libxml_use_internal_errors so that it can be restored.
		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = $this->create_dom_document( $content );

		// Mentions and hashtags.
		$links = $doc->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			$href      = $link->getAttribute( 'href' );
			$url_parts = wp_parse_url( $href );

			if ( empty( $url_parts['host'] ) ) {
				$link->setAttribute( 'href', $url . $href );
			}
		}

		// Images, emojis etc.
		$images = $doc->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			$src       = $image->getAttribute( 'src' );
			$url_parts = wp_parse_url( $src );

			if ( empty( $url_parts['host'] ) ) {
				$image->setAttribute( 'src', $url . $src );
			}
		}
		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		$parsed = $doc->saveHTML( $doc->documentElement );

		return $this->remove_outer_html_elements( $parsed );
	}

	/**
	 * Replaces polls in posts with a link to the post.
	 *
	 * @param string $cooked The post's cooked content.
	 * @param string $post_url The post's Discourse URL.
	 *
	 * @return string
	 */
	protected function add_poll_links( $cooked, $post_url ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $cooked;
		}

		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = $this->create_dom_document( $cooked );

		$finder = new \DOMXPath( $doc );
		// See: http://www.a-basketful-of-papayas.net/2010/04/css-selectors-and-xpath-expressions.html.
		$polls = $finder->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' poll ')]" );
		if ( $polls->length ) {
			$poll_number = 0;
			foreach ( $polls as $poll ) {
				if ( 0 === $poll_number ) {
					$link_text = __( 'Vote in the poll.', 'discourse-integration' );
					$link      = $doc->createElement( 'a', $link_text );
					$link->setAttribute( 'class', 'wpdc-poll-link' );
					$link->setAttribute( 'href', esc_url( $post_url ) );
					$poll->parentNode->replaceChild( $link, $poll );
				} else {
					$poll->parentNode->removeChild( $poll );
				}

				$poll_number ++;
			}

			$parsed = $doc->saveHTML( $doc->documentElement );
			$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

			return $this->remove_outer_html_elements( $parsed );
		}
		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		return $cooked;
	}

	/**
	 * Replaces divs with that have the data attribute `youtube-id` with a link to the video.
	 *
	 * @param string $cooked The cooked post content returned from Discourse.
	 *
	 * @return string
	 */
	protected function fix_youtube_onebox_links( $cooked ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $cooked;
		}

		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = $this->create_dom_document( $cooked );
		$finder                = new \DOMXPath( $doc );
		$youtube_links         = $finder->query( '//div[@data-youtube-id]' );

		if ( $youtube_links->length ) {
			foreach ( $youtube_links as $youtube_link ) {

				$youtube_id  = $youtube_link->getAttribute( 'data-youtube-id' );
				$youtube_url = esc_url( "https://www.youtube.com/watch?v={$youtube_id}" );
				$new_link    = $doc->createElement( 'a', $youtube_url );
				$new_link->setAttribute( 'href', esc_url( $youtube_url ) );
				$new_link->setAttribute( 'class', 'wpdc-onebox-link' );
				$youtube_link->parentNode->replaceChild( $new_link, $youtube_link );
			}

			$parsed = $doc->saveHTML( $doc->documentElement );
			$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

			return $this->remove_outer_html_elements( $parsed );
		}
		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		return $cooked;
	}

	/**
	 * Extracts image src from HTML and returns an image tag for each image.
	 *
	 * This function is used when full post content is published to Discourse. Its purpose is to remove the surrounding
	 * <figure> tags from images. Unless that is done, images will break when they are downloaded by Discourse.
	 *
	 * @param string $html The HTML to extract the image URL from.
	 *
	 * @return string
	 */
	protected function extract_images_from_html( $html ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $html;
		}

		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = $this->create_dom_document( $html );
		$finder                = new \DOMXPath( $doc );
		$images                = $finder->query( '//img' );
		$output                = '';

		if ( $images->length ) {
			foreach ( $images as $image ) {
				$src     = esc_url( $image->getAttribute( 'src' ) );
				$output .= "<img src='$src'>";
			}
		}

		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		return $output;
	}

	/**
	 * Removes HTML comments before publishing the post to Discourse.
	 *
	 * @param string $html The HTML to remove comment blocks from.
	 *
	 * @return string
	 */
	protected function remove_html_comments( $html ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $html;
		}

		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = $this->create_dom_document( $html );
		$finder                = new \DOMXPath( $doc );
		$comments              = $finder->query( '//comment()' );
		if ( $comments->length ) {
			foreach ( $comments as $comment ) {
				$comment->parentNode->removeChild( $comment );
			}

			$parsed = $doc->saveHTML( $doc->documentElement );
			$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

			return $this->remove_outer_html_elements( $parsed );

		}
		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		return $html;
	}

	/**
	 * Converts a fragment of HTML into a DomDocument object.
	 *
	 * @param string $fragment The HTML fragment to convert.
	 *
	 * @return \DOMDocument
	 */
	protected function create_dom_document( $fragment ) {
		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $fragment . '</body></html>';
		$doc  = new \DOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( $html );

		return $doc;
	}

	/**
	 * Removes DOCTYPE, html, head, meta, and body elements from the parsed HTML.
	 *
	 * @param string $html The HTML to remove elements from.
	 *
	 * @return string|null
	 */
	protected function remove_outer_html_elements( $html ) {

		return preg_replace( '~<(?:!DOCTYPE|/?(?:html|head|meta|body))[^>]*>\s*~i', '', $html );
	}

	/**
	 * Clears libxml errors and restores previous state of libxml_use_internal_errors and libxml_disable_entity_loader.
	 *
	 * @param bool $use_internal_errors The site's use_internal_errors setting.
	 * @param bool $disable_entity_loader The site's disable_entity_loader setting.
	 */
	protected function clear_libxml_errors( $use_internal_errors, $disable_entity_loader ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $use_internal_errors );
		$this->libxml_disable_entity_loader( $disable_entity_loader );
	}

	/**
	 * Using libxml_disable_entity_loader is unecessary and deprecated in PHP 8.0.0. See alos https://core.trac.wordpress.org/ticket/50898.
	 *
	 * @param bool $state State value for libxml_disable_entity_loader.
	 */
	protected function libxml_disable_entity_loader( $state ) {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			return libxml_disable_entity_loader( $state ); // phpcs:disable
		} else {
			return null;
		}
	}

	/**
	 * Fixes attributes of avatars in quotes.
	 *
	 * @param string $content       The comment's content.
	 * @param string $discourse_url The Discourse URL.
	 *
	 * @return string
	 */
	protected function fix_avatars_in_quotes( $content, $discourse_url ) {
		if ( ! extension_loaded( 'libxml' ) ) {
			return $content;
		}

		$use_internal_errors   = libxml_use_internal_errors( true );
		$disable_entity_loader = $this->libxml_disable_entity_loader( true );
		$doc                   = new \DOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );

		$finder = new \DOMXPath( $doc );
		$avatars_in_quotes = $finder->query( "//aside[contains(concat(' ', normalize-space(@class), ' '), ' quote ')]//img[contains(concat(' ', normalize-space(@class), ' '), ' avatar ')]" );
		if ( $avatars_in_quotes->length ) {
			foreach ( $avatars_in_quotes as $avatar ) {
				$alt = __( 'Avatar for', 'wp-discourse' ) . ' ';
				$src = $avatar->getAttribute( 'src' );
				if ( preg_match(
					'/\/\/[^\/]+\/user_avatar\/[^\/]+\/([^\/]+)\//',
					$src,
					$matches
				) ) {
					$alt .= esc_attr( $matches[1] );
				} else {
					$alt .= __('Discourse user', 'wp-discourse' );
				}
				$avatar->setAttribute( 'alt', $alt );

				// Discourse may send protocol-relative URLs for avatars in quotes.
				if ( substr( $src, 0, 2 ) === "//" ) {
					$protocol = strpos( $discourse_url, 'https://' ) !== false ? "https" : "http";
					$src = $protocol . ":" . $src;
					$avatar->setAttribute( 'src', $src );
				}
			}

			$content = $doc->saveHTML( $doc->documentElement );
			$content = $this->remove_outer_html_elements( $content );
		}

		$this->clear_libxml_errors( $use_internal_errors, $disable_entity_loader );

		return $content;
	}

	/**
	 * Format the Discourse created_at date based on the WordPress site's timezone.
	 *
	 * @param string $string The datetime string returned from Discourse.
	 * @param string $format The datetime format.
	 *
	 * @return string
	 */
	protected function format_date( $string, $format ) {
		$tz         = get_option( 'timezone_string' );
		$gmt_offset = get_option( 'gmt_offset' );
		$localtime  = '';
		if ( $tz ) {
			$datetime = date_create( $string, new \DateTimeZone( 'UTC' ) );
			$datetime->setTimezone( new \DateTimeZone( $tz ) );
			$localtime = $datetime->format( $format );
		} elseif ( $gmt_offset ) {
			$timestamp = strtotime( $string ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			$localtime = gmdate( $format, $timestamp );
		}

		return $localtime;
	}
}
