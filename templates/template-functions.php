<?php
/**
 * Tempate utility functions.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Templates;

/**
 * Class TemplateFunctions
 * Todo: there's no good reason for these to be static functions.
 */
class TemplateFunctions {

	/**
	 * Returns the user's Discourse homepage.
	 *
	 * @param string $url The base URL of the Discourse forum.
	 * @param object $post The Post object.
	 *
	 * @return string
	 */
	public static function homepage( $url, $post ) {
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
	public static function avatar( $template, $size ) {
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
	public static function convert_relative_img_src_to_absolute( $url, $content ) {
		if ( preg_match( "/<img\s*src\s*=\s*[\'\"]?(https?:)?\/\//i", $content ) ) {
			return $content;
		}

		$search  = '#<img src="((?!\s*[\'"]?(?:https?:)?\/\/)\s*([\'"]))?#';
		$replace = "<img src=\"{$url}$1";

		return preg_replace( $search, $replace, $content );
	}

	public static function convert_relative_urls_to_absolute( $url, $content ) {
		libxml_use_internal_errors( true );

		$doc = new \DOMDocument( '1.0', 'utf-8' );
		$html = $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="inner-content">' . $content . '</div></body></html>';
		$doc->loadHTML( $html );

		// Mentions and hashtags.
		$links = $doc->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			$href= $link->getAttribute( 'href' );
			$url_parts = parse_url( $href );

			if ( ! isset( $url_parts['host']) || $url_parts['host'] === '' ) {
				$link->setAttribute( 'href', $url . $href );
			}
		}

		// Images, emojis etc.
		$images = $doc->getElementsByTagName( 'img' );
		foreach( $images as $image ) {
			$src = $image->getAttribute( 'src' );
			$url_parts = parse_url( $src );

			if ( ! isset( $url_parts['host']) || $url_parts['host'] === '' ) {
				$image->setAttribute( 'src', $url . $src );
			}
		}

		$inner_html = self::inner_html($doc->getElementById('inner-content'));

		libxml_clear_errors();

		return $inner_html;
	}

	public static function inner_html( \DOMElement $element ) {
		$doc = $element->ownerDocument;
		$html = '';

		foreach ( $element->childNodes as $node ) {
			$html .= $doc->saveHTML( $node );
		}

		return $html;
	}
}
