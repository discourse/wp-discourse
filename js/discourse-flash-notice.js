/* globals notices */

jQuery(document).ready(function () {
    var flashNotices = jQuery( '.discourse-flash-notice-container').html();
    jQuery('body').prepend(flashNotices);

    jQuery('.discourse-close-flash-notice').click(function(e) {
        jQuery(this).parent().hide();
        e.preventDefault();
    });
});