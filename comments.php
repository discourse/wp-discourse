<?php
  $custom = get_post_custom();
  $options = get_option('discourse');
  $permalink = (string)$custom['discourse_permalink'][0];
  $discourse_url_name = preg_replace( "(https?://)", "", $options['url'] );
  if(isset($custom['discourse_comments_raw']))
    $discourse_info = json_decode($custom['discourse_comments_raw'][0]);
  else
    $discourse_info = array();
  $defaults = array(
    'posts_count' => 0,
    'posts' => array(),
    'participants' => array()
  );

  // Add some protection in the event our metadata doesn't look how we expect it to
  $discourse_info = (object)wp_parse_args((array)$discourse_info, $defaults);

  $more_replies = ($discourse_info->posts_count - count($discourse_info->posts) - 1);
  $show_fullname = ($options['use-fullname-in-comments'] == 1);
  $comments_title = $options['custom-comments-title'];
  if(!$comments_title || strlen(trim($comments_title)) == 0) {
    $comments_title = 'Notable Replies';
  }
  $more = count($discourse_info->posts) == 0 ? "" : "more ";

  if($more_replies == 0) {
    $more_replies = "";
  } elseif($more_replies == 1) {
    $more_replies = "1 " . $more . "reply";
  } else {
    $more_replies = $more_replies . " " . $more . "replies";
  }

  $link_text = ($discourse_info->posts_count - 1) == 0 ? "Start the discussion" : "Continue the discussion";

?>

<div id="comments" class="comments-area">
<?php if(count($discourse_info->posts) > 0) { ?>
  <h2 class="comments-title"><?php echo $comments_title ?></h2>
  <ol class="comment-list">
    <?php foreach($discourse_info->posts as &$post) { ?>

    <li class="comment even thread-even depth-1">
      <article class="comment-body">
        <footer class="comment-meta">
          <div class="comment-author vcard">
            <img alt="" src="<?php Discourse::avatar($post->avatar_template,64) ?>" class="avatar avatar-64 photo avatar-default" height="64" width="64">
            <b class="fn"><a href="<?php Discourse::homepage($options['url'],$post) ?>" rel="external" class="url"><?php echo ($show_fullname ? $post->name : $post->username) ?></a></b>
            <span class="says">says:</span>
          </div><!-- .comment-author -->

          <div class="comment-metadata">
            <time pubdate="" datetime="<?php echo $post->created_at ?>"><?php echo mysql2date(get_option('date_format'), $post->created_at)?></time>
          </div><!-- .comment-metadata -->
        </footer><!-- .comment-meta -->
        <div class="comment-content"><?php echo $post->cooked ?></div><!-- .comment-content -->
      </article><!-- .comment-body -->
    </li>

    <?php } ?>
  </ol>

<?php } ?>

  <div class="respond" class="comment-respond">
      <h3 id="reply-title" class="comment-reply-title"><a href="<?php echo $permalink ?>"><?php echo $link_text ?></a> at <?php echo $discourse_url_name ?></h3>
      <?php if(count($discourse_info->posts) > 0 || $more_replies > 0) { ?>
      <p class='more-replies'><?php echo $more_replies ?></p>
      <p class="comment-reply-title">
        <?php foreach($discourse_info->participants as &$participant) { ?>
          <img alt="" src="<?php Discourse::avatar($participant->avatar_template,25) ?>" class="avatar avatar-25 photo avatar-default" height="25" width="25">
        <?php } ?>
      </p>
      <?php } ?>
  </div><!-- #respond -->

</div>
