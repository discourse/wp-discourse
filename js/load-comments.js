/* globals wpdc */
/**
 * Loads Discourse comments into the .wpdc-comments div.
 *
 * @package WPDiscourse
 */

(function( $ ) {
	$( document ).ready(
		function() {
			var commentsURL  = wpdc.commentsURL,
				$commentArea = $( '#wpdc-comments' ),
				postId       = $commentArea.data( 'post-id' );

			$.ajax(
				{
					url: commentsURL + '?post_id=' + postId,
					success: function( response ) {
						$commentArea.removeClass( 'wpdc-comments-loading' );
						$commentArea.addClass( 'wpdc-comments-loaded' );
						$commentArea.html( response );
					}
				}
			);
		}
	);
})( jQuery );
