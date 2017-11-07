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
});
