<?php
  $custom = get_post_custom();
  $options = get_option('discourse');
  $permalink = (string)$custom['discourse_permalink'][0];
  $discourse_url_name = preg_replace("(https?://)", "", $options['url'] );
  $discourse_info = json_decode($custom['discourse_comments_raw'][0]);
  $more_replies = $discourse_info->posts_count - count($discourse_info->posts) - 1;
  $show_fullname = $options['use-fullname-in-comments'] == 1;
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

  $link_text = count($discourse_info->posts_count - 1) == 0 ? "Start the discussion" : "Continue the discussion";

?>

<div id="comments">
<?php if(count($discourse_info->posts) > 0) { ?>
    <h2 id="comments-title"><?php echo $comments_title ?></h2>
<?php } ?>
		<ol class="commentlist">
      <?php foreach($discourse_info->posts as &$post) { ?>
      <li class="comment">
				<div class="comment-author vcard">
          <img alt="" src="<?php Discourse::avatar($post->avatar_template,64) ?>" class="avatar avatar-64 photo avatar-default" height="64" width="64">
            <a href="<?php Discourse::homepage($options['url'],$post) ?>" rel="external" class="url"><?php echo ($show_fullname ? $post->name : $post->username) ?></a>
            <br/>
            <time pubdate="" datetime="<?php echo $post->created_at ?>"><?php echo mysql2date(get_option('date_format'), $post->created_at)?></time>
        </div>
        <div class="comment-content"><?php echo $post->cooked ?></div>
      </li>
      <?php } ?>

		</ol>



    <div class="respond">
        <h3 class="reply-title"><a href="<?php echo $permalink ?>"><?php echo $link_text ?></a> at <?php echo $discourse_url_name ?></h3>
        <?php if(count($discourse_info->posts) > 0 || $more_replies > 0) { ?>
        <p class='more-replies'><?php echo $more_replies ?></p>
        <p>
          <?php foreach($discourse_info->participants as &$participant) { ?>
            <img alt="" src="<?php Discourse::avatar($participant->avatar_template,25) ?>" class="avatar avatar-25 photo avatar-default" height="25" width="25">
          <?php } ?>
        </p>
        <?php } ?>
    </div><!-- #respond -->

</div>
