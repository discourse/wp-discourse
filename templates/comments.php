<?php
$custom = get_post_custom();
$options = get_option('discourse');
$is_enable_sso = (isset( $options['enable-sso'] ) && intval( $options['enable-sso'] ) == 1);
$permalink = (string)$custom['discourse_permalink'][0];
if($is_enable_sso) {
  $permalink = esc_url($options['url']) . '/session/sso?return_path=' . $permalink;
}
$discourse_url_name = preg_replace( "(https?://)", "", $options['url'] );
if(isset($custom['discourse_comments_raw'])) {
  $discourse_info = json_decode($custom['discourse_comments_raw'][0]);
} else{
  $discourse_info = array();
}
$defaults = array(
  'posts_count' => 0,
  'posts' => array(),
  'participants' => array()
);

// add <time> tag to WP allowed html tags
global $allowedposttags;
$allowedposttags['time'] = array('datetime'=>array());

// Add some protection in the event our metadata doesn't look how we expect it to
$discourse_info = (object)wp_parse_args((array)$discourse_info, $defaults);

$more_replies = ($discourse_info->posts_count - count($discourse_info->posts) - 1);
$more = count($discourse_info->posts) == 0 ? "" : "more ";

if($more_replies == 0) {
  $more_replies = "";
} elseif($more_replies == 1) {
  $more_replies = "1 " . $more . "reply";
} else {
  $more_replies = $more_replies . " " . $more . "replies";
}

$discourse_url = esc_url($options['url']);
$discourse_html = '';
$comments_html = '';
$participants_html = '';
if(count($discourse_info->posts) > 0) {
  foreach($discourse_info->posts as &$post) {
    $comment_html = wp_kses_post($options['comment-html']);

    $replace_array = array(
      '{discourse_url}' => $discourse_url,
      '{discourse_url_name}' => $discourse_url_name,
      '{topic_url}' => $permalink,
      '{avatar_url}' => Discourse::avatar($post->avatar_template,64),
      '{user_url}' => Discourse::homepage($options['url'],$post),
      '{username}' => $post->username,
      '{comment_body}' => Discourse::convert_relative_img_src_to_absolute($discourse_url, $post->cooked),
      '{comment_url}' => "{$permalink}/{$post->post_number}",
      '{comment_created_at}' => mysql2date(get_option('date_format'), $post->created_at)
    );

    $comments_html .= str_replace(array_keys($replace_array), array_values($replace_array), $comment_html);
  }
  foreach($discourse_info->participants as &$participant) {
    $participant_html = wp_kses_post($options['participant-html']);

    $replace_array = array(
      '{discourse_url}' => $discourse_url,
      '{discourse_url_name}' => $discourse_url_name,
      '{topic_url}' => $permalink,
      '{avatar_url}' => Discourse::avatar($participant->avatar_template,64),
      '{user_url}' => Discourse::homepage($options['url'],$participant),
      '{username}' => $participant->username
    );
    $participants_html .= str_replace(array_keys($replace_array), array_values($replace_array), $participant_html);
  }
  $discourse_html = wp_kses_post($options['replies-html']);
  $discourse_html = str_replace('{more_replies}', $more_replies, $discourse_html);
} else {
  $discourse_html = wp_kses_post($options['no-replies-html']);
}
$replace_array = array(
  '{discourse_url}' => $discourse_url,
  '{discourse_url_name}' => $discourse_url_name,
  '{topic_url}' => $permalink,
  '{comments}' => $comments_html,
  '{participants}' => $participants_html
);
$discourse_html = str_replace(array_keys($replace_array), array_values($replace_array), $discourse_html);
echo $discourse_html;
