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
		$disable_entity_loader = libxml_disable_entity_loader( true );

		$doc  = new \DOMDocument( '1.0', 'utf-8' );
		$html = $this->wrap_html_fragment( $content );
		$doc->loadHTML( $html );

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

		// Clear the libxml error buffer.
		libxml_clear_errors();
		// Restore the previous value of libxml_use_internal_errors and libxml_disable_entity_loader.
		libxml_use_internal_errors( $use_internal_errors );
		libxml_disable_entity_loader( $disable_entity_loader );

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
		$disable_entity_loader = libxml_disable_entity_loader( true );
		$doc                   = new \DOMDocument( '1.0', 'utf-8' );
		$html                  = $this->wrap_html_fragment( $cooked );
		$doc->loadHTML( $html );

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

			libxml_clear_errors();
			libxml_use_internal_errors( $use_internal_errors );
			libxml_disable_entity_loader( $disable_entity_loader );

			return $this->remove_outer_html_elements( $parsed );
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $use_internal_errors );
		libxml_disable_entity_loader( $disable_entity_loader );

		return $cooked;
	}

	/**
	 * Sets the outer elements for an HTML fragment so that it can be correctly parsed with the DOMDocument functions.
	 *
	 * @param string $fragment The HTML to wrap with outer elements.
	 *
	 * @return string
	 */
	protected function wrap_html_fragment( $fragment ) {

		return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $fragment . '</body></html>';
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
