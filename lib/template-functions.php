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
		$use_internal_errors = libxml_use_internal_errors( true );

		$doc = new \DOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );

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
		// Restore the previous value of libxml_use_internal_errors.
		libxml_use_internal_errors( $use_internal_errors );

		$parsed = $doc->saveHTML( $doc->documentElement );

		// Remove DOCTYPE, html, and body tags that have been added to the DOMDocument.
		$parsed = preg_replace( '~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $parsed );

		return $parsed;
	}

	/**
	 * Replaces polls in posts with a link to the post.
	 *
	 * @param string $cooked The post's cooked content.
	 * @param string $url The post's Discourse URL.
	 *
	 * @return string
	 */
	protected function add_poll_links( $cooked, $url ) {
		if ( ! extension_loaded( 'libxml' ) ) {

			return $cooked;
		}

		$use_internal_errors = libxml_use_internal_errors( true );
		$doc                 = new \DOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( mb_convert_encoding( $cooked, 'HTML-ENTITIES', 'UTF-8' ) );

		$finder = new \DOMXPath( $doc );
		// See: http://www.a-basketful-of-papayas.net/2010/04/css-selectors-and-xpath-expressions.html.
		$polls = $finder->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' poll ')]" );
		if ( $polls->length ) {
			foreach ( $polls as $poll ) {
				$link = $doc->createElement( 'a' );
				$link->setAttribute( 'class', 'wpdc-poll-link' );
				$link->setAttribute( 'href', $url );
				$link_text = sprintf(
					// translators: Poll replacement text. Placeholder: discourse_url.
					__( 'This post includes a poll. Visit it at %1$s', 'wp-discourse' ), esc_url( $this->options['url'] )
				);
				$link_text = $doc->createTextNode( $link_text );
				$link->appendChild( $link_text );

				$poll->parentNode->replaceChild( $link, $poll );
			}

			$parsed = $doc->saveHTML( $doc->documentElement );
			$parsed = preg_replace( '~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $parsed );

			libxml_clear_errors();
			libxml_use_internal_errors( $use_internal_errors );

			return $parsed;
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $use_internal_errors );

		return $cooked;
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
