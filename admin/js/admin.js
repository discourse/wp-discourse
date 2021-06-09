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
						// Only allow alphanumeric characters, dashes, underscores, and spaces.
						var allowedChars = new RegExp('^[a-zA-Z0-9\-\_ ]+$');
						if ( allowedChars.test( e ) ) {

							return e.trim().replace( / /g, '-' );
						} else {

							return '';
						}
					}
				).filter( function( tag ) {

					return tag.length > 0;
				});

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

	// Toggle the Commenting Settings comment-type input.
	$( '#discourse-enable-discourse-comments' ).click(
		function() {
				$( '.discourse-comment-type' ).toggleClass( 'hidden' );
		}
	);
	
	var $logControls = $('#wpdc-log-viewer-controls');
	var $logViewer = $('#wpdc-log-viewer');
	
	function handleLogResponse(response, logKey, meta) {
		if (response && response.data) {
			var title = (meta ? '' : 'Log for ') + response.data.name;
			$logControls.find('h3').html(title);
			$logViewer.find('pre').html(response.data.contents);
		}

		if (logKey) {
			$logViewer.data('log-key', logKey);
		}

		$logControls.toggleClass('meta', meta);
		$logViewer.removeClass('loading');
	}
	
	$logControls.find('select').on('change', function() {
		var logKey = $logControls.find('select').val();
		
		if (logKey) {
			$logViewer.addClass('loading');
			
			$.ajax({
				url: wpdc.ajax,
				type: 'post',
				data: {
					action: 'wpdc_view_log',
					nonce: wpdc.nonce,
					key: logKey
				},
				success: function(response) {
					if (response.success) {
						handleLogResponse(response, logKey, false);
					}
				}
			});
		}
	});
	
	$logControls.find('.load-log').on('click', function() {		
		var logKey = $logViewer.data('log-key');
		
		if (logKey) {
			$logViewer.addClass('loading');
			
			$.ajax({
				url: wpdc.ajax,
				type: 'post',
				data: {
					action: 'wpdc_view_log',
					nonce: wpdc.nonce,
					key: logKey
				},
				success: function(response) {
					if (response.success) {
						handleLogResponse(response, logKey, false);
					}
				}
			});
		}
	});
	
	$logControls.find('.button.view-meta').on('click', function() {		
		$logViewer.addClass('loading');	
		$.ajax({
			url: wpdc.ajax,
			type: 'post',
			data: {
				action: 'wpdc_view_logs_metafile'
			},
			success: function(response) {
				if (response.success) {
					handleLogResponse(response, null, true);
				}
			}
		});
	});
	
	$logControls.find('.button.download-logs').on('click', function() {		
		var xhr = new XMLHttpRequest();
		xhr.open('POST', wpdc.ajax + '?action=wpdc_download_logs', true);
		xhr.onload = function() {
			if (xhr.readyState === 4 && xhr.status === 200) {
				var blob = new Blob([ xhr.response ], { type: 'application/zip' });
				var url = window.URL.createObjectURL(blob);
				var a = document.createElement('a');
        
				document.body.appendChild(a);
        a.style = 'display:none';
        a.href = url;
        a.download = xhr.getResponseHeader('Content-Disposition').split('filename=')[1];
        a.click();
        a.remove();
				setTimeout(function() {
					window.URL.revokeObjectURL(url);
				});
			}
		};
		xhr.responseType = 'arraybuffer';
		xhr.send();
	});
	
	if ( $('.tagsdiv').length ) {
		window.tagBox && window.tagBox.init();
	}
})( jQuery );
