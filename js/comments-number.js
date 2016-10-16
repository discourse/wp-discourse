jQuery( document ).ready(function() {
    var ajaxURL = commentsNumberScript.ajaxurl,
        singleReplyText = commentsNumberScript.singleReplyText,
        manyRepliesText = commentsNumberScript.manyRepliesText,
        noRepliesText = commentsNumberScript.noRepliesText;

    function formatCommentsNumber( number, singleText, manyText, noneText ) {
        var formattedText,
            number = parseInt( number, 10 );
        if ( 1 > number ) {
            formattedText = noneText;
        } else if ( 1 === number ) {
            formattedText = 1 + ' ' + singleText;
        } else if ( 1 < number ) {
            formattedText = number + ' ' + manyText;
        } else {
            formattedText = '<span class="error">Unable to retrieve comments number</span>';
        }

        return formattedText;
    }

    function setCommentNumberText( text, target ) {
        var $target = jQuery( target );

        $target.removeClass( 'wp-discourse-comments-number-loading' );
        $target.html( text );
    }

    jQuery( '.wp-discourse-comments-number-ajax' ).each(function() {
        var postID = jQuery( this ).data( 'post-id' ),
            nonceName = jQuery( this ).data( 'nonce-name' ),
            nonce = jQuery( this ).data( 'nonce' ),
            currentSpan = jQuery( this ).attr( 'id' ),
            oldNumber = jQuery( this ).data( 'old-number' );

        data = {
            action: 'get_discourse_comments_number',
            nonce_name: nonceName,
            nonce: nonce,
            post_id: postID,
            current_span: currentSpan
        };

        jQuery.post( ajaxURL, data, function( response ) {
            var commentCount = response.comments_count,
                target = '#' + currentSpan,
                formattedText;

            if ( 'success' === response.status ) {
                formattedText = formatCommentsNumber( commentCount, singleReplyText, manyRepliesText, noRepliesText );
            } else {
                formattedText = formatCommentsNumber( oldNumber, singleReplyText, manyRepliesText, noRepliesText );
            }

            setCommentNumberText( formattedText, target );

        }).fail(function( response ) {
            var commentCount = oldNumber,
                target = '#' + currentSpan,
                formattedText;

            formattedText = formatCommentsNumber( commentCount, singleReplyText, manyRepliesText, noRepliesText );
            setCommentNumberText( formattedText, target );
        });
    });
});
