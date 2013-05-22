<?php
  $custom = get_post_custom();
  $options = get_option('discourse');
  $permalink = (string)$custom['discourse_permalink'][0];
  $discourse_url_name = preg_replace("(https?://)", "", $options['url'] );
  $discourse_info = json_decode($custom['discourse_comments_raw'][0]);
  $more_replies = $discourse_info->filtered_posts_count - count($discourse_info->posts) - 1;
  $show_fullname = $options['use-fullname-in-comments'] == 1;

  if($more_replies == 0) {
    $more_replies = "";
  } elseif($more_replies == 1) {
    $more_replies = "1 more reply";
  } else {
    $more_replies = $more_replies . " more replies";
  }

  function homepage($url, $post) {
    echo $url . "/users/" . strtolower($post->username);
  }

  function avatar($template, $size) {
    echo str_replace("{size}", $size, $template);
  }
?>

<?php # var_dump($discourse_info->posts) ?>

<div id="comments">
<?php if(count($discourse_info->posts) > 0) { ?>
<h2 id="comments-title">Notable Replies</h2>
<?php } ?>
		<ol class="commentlist">
      <?php foreach($discourse_info->posts as &$post) { ?>
        <li class="comment">
				<div class="comment-author vcard">
        <img alt="" src="<?php avatar($post->avatar_template,68) ?>" class="avatar avatar-68 photo avatar-default" height="68" width="68">
          <a href="<?php homepage($options['url'],$post) ?>" rel="external" class="url"><?php echo ($show_fullname ? $post->name : $post->username) ?></a>
          <br/>
          <time pubdate="" datetime="<?php echo $post->created_at ?>"><?php echo mysql2date(get_option('date_format'), $post->created_at)?></time>
</div>
            <div class="comment-content"><?php echo $post->cooked ?></div>
        </li>
      <?php } ?>

		</ol>



    <div id="respond">
        <h3 id="reply-title"><a href="<?php echo $permalink ?>">Continue the discussion</a> at <?php echo $discourse_url_name ?></h3>
        <?php if(count($discourse_info->posts) > 0) { ?>
        <p class='more-replies'><?php echo $more_replies ?></p>
        <p>
          <?php foreach($discourse_info->participants as &$participant) { ?>
            <img alt="" src="<?php avatar($participant->avatar_template,25) ?>" class="avatar avatar-25 photo avatar-default" height="25" width="25">
          <?php } ?>
        </p>
        <?php } ?>
    </div><!-- #respond -->

</div>
