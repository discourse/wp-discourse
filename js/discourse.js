jQuery(document).ready(function() {
  youtube_id = jQuery(".lazyYT").data('youtube-id');
  link = "https://www.youtube.com/watch?v=" + youtube_id;
  jQuery(".lazyYT").replaceWith("<a href='" + link + "'>" + link + "</a>");
});
