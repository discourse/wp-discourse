/* globals wpdc */
/**
 * Toggles the hidden topic-map content.
 *
 * @package WPDiscourse
 */

(function($) {
    var ajaxLoad = dctm.ajaxLoad;
    console.log('ajax load', ajaxLoad);
    // Todo: The target needs to be changed for when comments are loaded with javascript
    $( '.topic-map' ).on( 'click', "#toggle-expand", function() {
        var expand_section = $("#map-expanded");
        $(".topic-map #arrow").toggleClass('dashicons dashicons-arrow-down-alt2 dashicons dashicons-arrow-up-alt2');

        if (expand_section.css("display") == "block") {
            expand_section.css('display', 'none');
        } else {
            expand_section.css('display', 'block');
        }
    });

    if ($('.popular-links > .popular-links-p').is(':empty')) {
        $('.popular-links').hide();
    }
})(jQuery);