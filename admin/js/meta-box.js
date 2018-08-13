/* globals wpdc */
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
			} else if ( 'link' === val ) {
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
				response = window.confirm( 'Updating the Discourse topic will overwrite the existing topic content on Discourse. Do you wish to proceed?' );
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
				response = window.confirm( 'Unlinking the post will remove all Discourse data from the post. You will need to update the post to complete the unlinking process. Do you wish to proceed?' );
				if ( ! response ) {
					$( this ).prop( 'checked', false );
				}
			}
		}
	);

	$( '#pin_discourse_topic' ).click(
		function() {
			var $pinUntil = $( '.wpdc-pin-topic-time' );
			$( this ).is( ':checked' ) ? $pinUntil.show() : $pinUntil.hide();
		}
	);

	$( '#wpdc-tagadd' ).click(
		function() {
			var $tagInput      = $( '#discourse-topic-tags' ),
				$tagList       = $( '#wpdc-tagchecklist' ),
				$tagListErrors = $( '.wpdc-taglist-errors' ),
				tags           = $tagInput.val(),
				maxTags        = wpdc.maxTags,
				tooManyTags    = false,
				tagArr;

			$tagInput.val( '' );
			$tagListErrors.empty();

			if ( tags ) {
				tagArr = tags.split( ',' ).map(
					function( e ) {
							return e.trim().replace( / /g, '-' );
					}
				);

				if ( tagArr ) {
					tagArr.forEach(
						function( tag ) {
							if ( $tagList.children( 'li' ).length < maxTags ) {
								$tagList.append(
									'<li class="wpdc-tag-item"><button type="button" class="wpdc-remove-tag">' +
									'<span class="wpdc-remove-tag-icon" aria-hidden="true"></span><span class="screen-reader-text">Remove term: ' + tag + '</span></button>' +
									'&nbsp;' + tag + '<input name="wpdc_topic_tags[]" type="hidden" value="' + tag + '"></li>'
								);
							} else {
								tooManyTags = true;
							}
						}
					);

					if ( tooManyTags ) {
						$tagListErrors.append( 'You are only allowed ' + maxTags + ' tags per topic.' );
					}
				}
			}
		}
	);

	$( '.wpdc-advanced-options' ).on(
		'click', '.wpdc-remove-tag', function() {
			$( this ).parent().remove();
			$( '.wpdc-taglist-errors' ).empty();
		}
	);
})( jQuery );
