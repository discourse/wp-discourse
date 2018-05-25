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

	$( '.wpdc-advanced-options-toggle' ).click(
		function() {
				$( '.wpdc-advanced-options' ).toggleClass( 'hidden' );
		}
	);

	$( '#update_discourse_topic' ).click(
		function() {
			var response;
			if ( $( this ).is( ':checked' ) ) {
				response = confirm( 'Updating the Discourse topic will overwrite the existing topic content on Discourse. Do you wish to proceed?' );
				if ( ! response ) {
					$( this ).prop( 'checked', false );
				}
			}
		}
	);

	$( '#unlink_from_discourse' ).click(
		function() {
			var response;
			if ( $( this ).is( ':checked' ) ) {
				response = confirm( 'Unlinking the post will remove all Discourse data from the post. You will need to update the post to complete the unlinking process. Do you wish to proceed?' );
				if ( ! response ) {
					$( this ).prop( 'checked', false );
				}
			}
		}
	);
})( jQuery );
