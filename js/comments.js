/* globals discourse */

/**
 * Fixes Discourse oneboxes and mention links for display on WordPress.
 *
 * @package WPDiscourse
 */

jQuery( document ).ready(function() {
	jQuery( '.lazyYT' ).each(function() {
		var id = jQuery( this ).data( 'youtube-id' ),
			url = 'https://www.youtube.com/watch?v=' + id;
		jQuery( this ).replaceWith( '<a href="' + url + '">' + url + '</a>' );
	});
	// Todo: this may need to stay as a fallback for sites that don't have lib-sml.
	/*jQuery( 'a.mention' ).each(function() {
		jQuery( this ).attr( 'href', discourse.url + jQuery( this ).attr( 'href' ) );
	});*/
});
