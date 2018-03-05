/**
 * Toggles the 'hidden' class for the publishing_options metabox.
 *
 * @package WPDiscourse
 */

(function( $ ) {
	$( 'input[type=radio][name=wpdc_publish_options]' ).change(
		function() {
			var val          = this.value,
			$newTopicOptions = $( '.wpdc-new-discourse-topic' ),
			$linkPostOptions = $( '.wpdc-link-to-topic' );
			if ( 'new' === val ) {
				$newTopicOptions.removeClass( 'hidden' );
				$linkPostOptions.addClass( 'hidden' );
			} else {
				$newTopicOptions.addClass( 'hidden' );
				$linkPostOptions.removeClass( 'hidden' );
			}
		}
	);

	$( '#wpdc_pin_topic_checkbox').change( function() {
		$( '.wpdc-pin-topic').toggleClass( 'hidden' );
	});
})( jQuery );
