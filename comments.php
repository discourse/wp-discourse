<?php
  $custom = get_post_custom();
  $options = get_option('discourse');
  $permalink = (string)$custom['discourse_permalink'][0];
  $discourse_url_name = preg_replace("(https?://)", "", $options['url'] );
  $discourse_info = json_decode($custom['discourse_comments_raw'][0]);
  $more_replies = $discourse_info->posts_count - count($discourse_info->posts) - 1;
  /*
  $show_fullname = $options['use-fullname-in-comments'] == 1;
  $comments_title = $options['custom-comments-title'];
  if(!$comments_title || strlen(trim($comments_title)) == 0) {
    $comments_title = 'Notable Replies';
  }*/
  $more = count($discourse_info->posts) == 0 ? "" : "more ";

  if($more_replies == 0) {
    $more_replies = "";
  } elseif($more_replies == 1) {
    $more_replies = "1 " . $more . "reply";
  } else {
    $more_replies = $more_replies . " " . $more . "replies";
  }

  //$link_text = count($discourse_info->posts_count - 1) == 0 ? "Start the discussion" : "Continue the discussion";

  $discourse_html = '';
  $comments_html = '';
  if(count($discourse_info->posts) > 0) {
	foreach($discourse_info->posts as &$post) {
		$comment_html = wp_kses_post($options['comment-html']);
		$comment_html = str_replace('{discourse_url}', esc_url($options['url']), $comment_html);
		$comment_html = str_replace('{topic_url}', $permalink, $comment_html);
		//$comment_html = str_replace('{comment_url}', $permalink."/".$post->post_number, $comment_html); //post_number appears to be missing
		$comment_html = str_replace('{avatar_url}', Discourse::avatar($post->avatar_template,64), $comment_html);
		$comment_html = str_replace('{user_url}', Discourse::homepage($options['url'],$post), $comment_html);
		$comment_html = str_replace('{username}', $post->username, $comment_html);
		$comment_html = str_replace('{fullname}', $post->name, $comment_html);
		$comment_html = str_replace('{comment_body}', $post->cooked, $comment_html); // emoticons don't have absolute urls
		$comment_html = str_replace('{comment_created_at}', mysql2date(get_option('date_format'), $post->created_at), $comment_html);
		$comments_html .= $comment_html;
	}
	$discourse_html = wp_kses_post($options['replies-html']);
  } else {
	$discourse_html = wp_kses_post($options['no-replies-html']);
  }
  $discourse_html = str_replace('{discourse_url}', esc_url($options['url']), $discourse_html);
  $discourse_html = str_replace('{topic_url}', $permalink, $discourse_html);
  $discourse_html = str_replace('{comments}', $comments_html, $discourse_html);
  echo $discourse_html;
?>
