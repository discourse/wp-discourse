<?php
/*
Plugin Name: WP-Discourse
Description: Allows you to publish your posts to a Discourse instance and view top Discourse comments on your blog
Version: 0.0.1
Author: Sam Saffron
Author URI: http://www.discourse.org
*/
/*  Copyright 2011 Sam Saffron (sam.saffron@discourse.org)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
class Discourse {

	var $domain = 'discourse';

	//Version
	static $version ='0.0.1';

	//Options and defaults
	static $options = array(
		'url'=>'',
    'api-key'=>'',
    'publish-username'=>'',
    'publish-category'=>'',
    'auto-publish'=>0,
    'auto-update'=>0,
    'max-comment'=>5,
    'use-discourse-comments'=>0,
    'use-fullname-in-comments'=>1,
    'publish-format'=>'<small>Originally published at: {blogurl}</small><br>{excerpt}'
	);

	public function __construct() {
		register_activation_hook(__FILE__,array(__CLASS__, 'install' ));
		register_uninstall_hook(__FILE__,array( __CLASS__, 'uninstall' ));
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
    add_action( 'admin_menu', array( $this, 'discourse_admin_menu' ));
	}

	static function install(){
		update_option("discourse_version",self::$version);
		add_option('discourse',self::$options);
	}

	static function uninstall(){
		delete_option('discourse_version');
		delete_option('discourse');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain( 'discourse', false, basename(dirname(__FILE__)).'/languages');

    //replace comments with discourse comments

    add_filter('comments_number', array($this,'comments_number'));
    add_filter('comments_template', array($this,'comments_template'));

	}

  function comments_number($count) {


    global $post;
    if(self::use_discourse_comments($post->ID)){
      self::sync_comments($post->ID);
      $count = get_post_meta($post->ID, 'discourse_comments_count', true);
      if(!$count){
        $count = "Leave a reply";
      } else {
        $count = $count == 1 ? "1 Reply" : $count . " Replies";
      }
    }

    return $count;
  }

  function use_discourse_comments($postid){
    return get_post_meta($postid, 'publish_to_discourse', true) == 1 &&
      get_post_meta($postid, 'discourse_post_id', true) > 0;
  }

  function sync_comments($postid) {
    global $wpdb;

    # every 10 minutes do a json call to sync comment count and top comments
    $last_sync = (int)get_post_meta($postid, 'discourse_last_sync', true);
    $time = date_timestamp_get(date_create());
    if($last_sync + 60 * 10 < $time) {

      $got_lock = $wpdb->get_row( "SELECT GET_LOCK('discourse_lock', 0) got_it");
      if($got_lock->got_it == "1") {

        $discourse_options =  get_option('discourse');
        $comment_count = intval($discourse_options['max-comments']);
        $permalink = (string)get_post_meta($postid, 'discourse_permalink', true) . '.json?best=' . $comment_count;
        $soptions = array('http' => array('ignore_errors' => true, 'method'  => 'GET'));
        $context  = stream_context_create($soptions);
        $result = file_get_contents($permalink, false, $context);
        $json = json_decode($result);

        delete_post_meta($postid, 'discourse_comments_count');
        add_post_meta($postid, 'discourse_comments_count', $json->posts_count - 1 , true);

        delete_post_meta($postid, 'discourse_comments_raw');

        add_post_meta($postid, 'discourse_comments_raw', $wpdb->escape($result) , true);

        delete_post_meta($postid, 'discourse_last_sync');
        add_post_meta($postid, 'discourse_last_sync', $time, true);
        $wpdb->get_results("SELECT RELEASE_LOCK('discourse_lock')");
      }
    }
  }

  function comments_template($old) {
    global $post;

    if(self::use_discourse_comments($post->ID)) {
      self::sync_comments($post->ID);
      return dirname(__FILE__) . '/comments.php';
    }

    return $old;
  }

	/*
	* Settings
	*/
	public function admin_init(){
    register_setting( 'discourse', 'discourse', array($this, 'discourse_validate_options'));
    add_settings_section( 'default_discourse', 'Default Settings', array($this, 'init_default_settings'), 'discourse' );
    add_settings_field('discourse_url', 'Url', array($this, 'url_input'), 'discourse', 'default_discourse');
    add_settings_field('discourse_api_key', 'API Key', array($this, 'api_key_input'), 'discourse', 'default_discourse');
    add_settings_field('discourse_publish_username', 'Publishing username', array($this, 'publish_username_input'), 'discourse', 'default_discourse');
    add_settings_field('discourse_publish_category', 'Published category', array($this, 'publish_category_input'), 'discourse', 'default_discourse');
    add_settings_field('discourse_publish_format', 'Publish format', array($this, 'publish_format_textarea'), 'discourse', 'default_discourse');
    add_settings_field('discourse_auto_publish', 'Auto Publish', array($this, 'auto_publish_checkbox'), 'discourse', 'default_discourse');
    add_settings_field('discourse_auto_update', 'Auto Update Posts', array($this, 'auto_update_checkbox'), 'discourse', 'default_discourse');
    add_settings_field('discourse_use_discourse_comments', 'Use Discourse Comments', array($this, 'use_discourse_comments_checkbox'), 'discourse', 'default_discourse');
    add_settings_field('discourse_max_comments', 'Max visible comments', array($this, 'max_comments_input'), 'discourse', 'default_discourse');
    add_settings_field('discourse_use_fullname_in_comments', 'Full name in comments', array($this, 'use_fullname_in_comments_checkbox'), 'discourse', 'default_discourse');


    add_action( 'post_submitbox_misc_actions', array($this,'publish_to_discourse'));
    add_action( 'save_post', array($this, 'save_postdata'));
    add_action ( 'transition_post_status', array($this, 'post_status_changed'), 10, 3 );
  }

  function post_status_changed($old, $new, $post){
    if($post->post_type == "revision") { return; }
    if($new == 'publish' && get_post_meta($post->ID, 'publish_to_discourse', true) == 1) {
      self::sync_to_discourse($post->ID, $post->post_title, $post->post_content);
    }
  }

  function save_postdata($postid)
  {
    if ( !current_user_can( 'edit_page', $postid ) ) return $postid;
    if(empty($postid) || !isset($_POST['publish_to_discourse'])) return $postid;

    # trust me ... word press is crazy like this, try changing a title.
    if(!isset($_POST['ID'])) return $postid;

    if($_POST['action'] == 'editpost'){
        delete_post_meta($_POST['ID'], 'publish_to_discourse');
    }

    $publish = $_POST['publish_to_discourse'];
    add_post_meta($_POST['ID'], 'publish_to_discourse', $publish, true);

    return $postid;
  }

  function sync_to_discourse($postid, $title, $raw) {
    $discourse_id = get_post_meta($postid, 'discourse_post_id', true);
    $options = get_option('discourse');
    $post = get_post($post_id);


    $excerpt = apply_filters('the_content', $raw);
    $excerpt = wp_trim_words($excerpt);

    $baked = $options['publish-format'];
    $baked = str_replace("{excerpt}", $excerpt, $baked);
    $baked = str_replace("{blogurl}", get_permalink($postid), $baked);

    $data = array(
      'wp-id' => $postid,
      'api_key' => $options['api-key'],
      'api_username' => $options['publish-username'],
      'title' => $title,
      'post[raw]' => $baked,
      'post[category]' => $options['publish-category']
    );


    if(!$discourse_id > 0) {
      $url =  $options['url'] .'/posts';

      // use key 'http' even if you send the request to https://...
      $soptions = array('http' => array('ignore_errors' => true, 'method'  => 'POST','content' => http_build_query($data)));
      $context  = stream_context_create($soptions);
      $result = file_get_contents($url, false, $context);
      $json = json_decode($result);

      #todo may have $json->errors with list of errors

      if(property_exists($json, 'id')) {
        $discourse_id = (int)$json->id;
      }


      if(isset($discourse_id) && $discourse_id > 0) {
        add_post_meta($postid, 'discourse_post_id', $discourse_id, true);
      }

    }
    else {
      # for now the updates are just causing grief, leave'em out
      return;
      $url =  $options['url'] .'/posts/' . $discourse_id ;
      $soptions = array('http' => array('ignore_errors' => true, 'method'  => 'PUT','content' => http_build_query($data)));
      $context  = stream_context_create($soptions);
      $result = file_get_contents($url, false, $context);
      $json = json_decode($result);

      if(isset($json->post)) {
        $json = $json->post;
      }

      # todo may have $json->errors with list of errors
    }

    if(isset($json->topic_slug)){
      delete_post_meta($postid,'discourse_permalink');
      add_post_meta($postid,'discourse_permalink', $options['url'] . '/t/' . $json->topic_slug . '/' . $json->topic_id, true);
    }
  }


  function publish_to_discourse()
  {
    global $post;
    $value = get_post_meta($post->ID, 'publish_to_discourse', true);
    echo '<div class="misc-pub-section misc-pub-section-last">
         <span>'
         . '<label><input type="checkbox"' . (!empty($value) ? ' checked="checked" ' : null) . 'value="1" name="publish_to_discourse" /> Publish to Discourse</label>'
    .'</span></div>';
  }


  function init_default_settings() {

  }

  function url_input(){
    self::text_input('url', 'Enter your discourse url Eg: http://discuss.mysite.com');
  }

  function api_key_input(){
    self::text_input('api-key', '');
  }

  function publish_username_input(){
    self::text_input('publish-username', 'Discourse username of publisher');
  }

  function publish_category_input(){
    self::text_input('publish-category', 'Category post will be published in Discourse (optional)');
  }

  function publish_format_textarea(){
    self::text_area('publish-format', 'Markdown format for published articles, use {excerpt} for excerpt and {blogurl} for the url of the blog post');
  }

  function max_comments_input(){
    self::text_input('max-comments', 'Maximum number of comments to display');
  }

  function use_fullname_in_comments_checkbox(){
    self::checkbox_input('use-fullname-in-comments', 'Use the users full name in blog comment section');
  }

  function auto_publish_checkbox(){
    self::checkbox_input('auto-publish', 'Publish all new posts to Discourse');
  }

  function auto_update_checkbox(){
    self::checkbox_input('auto-update', 'Update published blog posts on Discourse');
  }

  function use_discourse_comments_checkbox(){
    self::checkbox_input('use-discourse-comments', 'Use Discourse to comment on Discourse published posts (hiding existing comment section)');
  }

  function checkbox_input($option, $description) {

    $options = get_option( 'discourse' );
    if (array_key_exists($option, $options) and $options[$option] == 1) {
      $value = 'checked="checked"';
    } else {
      $value = '';
    }

    ?>

<input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='checkbox' value='1' <?php echo $value?> /> <?php echo $description ?>
    <?php

  }

  function text_input($option, $description) {

    $options = get_option( 'discourse' );
    if (array_key_exists($option, $options)) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
<input id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]' type='text' value='<?php echo esc_attr( $value ); ?>' /> <?php echo $description ?>
    <?php

  }

  function text_area($option, $description) {

    $options = get_option( 'discourse' );
    if (array_key_exists($option, $options)) {
      $value = $options[$option];
    } else {
      $value = '';
    }

    ?>
<textarea cols=100 rows=6 id='discourse_<?php echo $option?>' name='discourse[<?php echo $option?>]'><?php echo esc_attr( $value ); ?></textarea><br><?php echo $description ?>
    <?php

  }
  function discourse_validate_options($input) {
    return $input;
  }

  function discourse_admin_menu(){
    add_options_page( 'Discourse', 'Discourse', 'manage_options', 'discourse', array ( $this, 'discourse_options_page' ));
  }

  function discourse_options_page() {
    ?>
    <div class="wrap">
        <h2>Discourse Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'discourse' ); ?>
            <?php do_settings_sections('discourse'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
  }
}



$discourse = new Discourse();
