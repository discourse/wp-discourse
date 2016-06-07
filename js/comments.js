/* globals discourse */
jQuery( document ).ready(function() {
	jQuery( '.lazyYT' ).each(function() {
		var id = jQuery( this ).data( 'youtube-id' ),
			url = 'https://www.youtube.com/watch?v=' + id;
		jQuery( this ).replaceWith( '<a href="' + url + '">' + url + '</a>' );
	});
	jQuery( 'a.mention' ).each(function() {
		jQuery( this ).attr( 'href', discourse.url + jQuery( this ).attr( 'href' ) );
	});
});
