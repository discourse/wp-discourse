(function ($) {
    $( document ).ready(
        function() {
            console.log('load comments file loaded');
            var commentsURL = wpdc.commentsURL;
            console.log('comments URL', commentsURL);


          //  (function getComments() {
                $.ajax(
                    {
                        url: commentsURL,
                        success: function (response) {
                            if (0 !== response) {
                                console.log('response', response);
                                //$topicListWrapper.html( response );
                            }
                        }
                    }
                );
            //})();
        }
    )
})(jQuery);