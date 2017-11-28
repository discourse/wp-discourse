(function ($) {
    $(document).ready(
        function () {
            var commentsURL = wpdc.commentsURL,
                $commentArea = $('.wpdc-comments'),
                postId = $commentArea.data('post-id');

            console.log('post id', postId);
            $.ajax(
                {
                    url: commentsURL + '?post_id=' + postId,
                    success: function (response) {
                        if (0 !== response) {
                            console.log('response', response);
                            $commentArea.html( response );
                        }
                    }
                }
            );
        }
    )
})(jQuery);