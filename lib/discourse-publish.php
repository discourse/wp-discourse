<?php
/**
 * Publishes a post to Discourse.
 *
 * @package WPDicourse
 */

namespace WPDiscourse\DiscoursePublish;

use WPDiscourse\Templates as Templates;
use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscoursePublish
 */
class DiscoursePublish {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * DiscoursePublish constructor.
	 *
	 * @param \WPDiscourse\ResponseValidator\ResponseValidator $response_validator Validate the response from Discourse.
	 */
	public function __construct() {
		$this->options            = get_option( 'discourse' );

		add_action( 'save_post', array( $this, 'publish_post_after_save' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'publish_post_after_transition' ), 10, 3 );
		add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
	}

	/**
	 * Publishes a post to Discourse after its status has transitioned.
	 *
	 * This function is called when post status changes. Hooks into 'transition_post_status'.
	 *
	 * @param string $new_status New post status after an update.
	 * @param string $old_status The old post status.
	 * @param object $post The post object.
	 */
	function publish_post_after_transition( $new_status, $old_status, $post ) {
		$publish_to_discourse  = get_post_meta( $post->ID, 'publish_to_discourse', true );

		if ( $publish_to_discourse && $new_status == 'publish' && self::is_valid_sync_post_type( $post->ID ) ) {
			self::sync_to_discourse( $post->ID, $post->post_title, $post->post_content );
		}
	}

	/**
	 * Published a post to Discourse after it has been saved.
	 * 
	 * @param int $post_id The id of the post that has been saved.
	 * @param object $post The Post object.
	 */
	public function publish_post_after_save( $post_id, $post ) {
		if ( wp_is_post_revision($post_id ) ) {
			return;
		}
		$post_is_published = 'publish' === get_post_status( $post_id );
		$publish_to_discourse = get_post_meta( $post_id, 'publish_to_discourse', true );
		if ( $publish_to_discourse && $post_is_published && self::is_valid_sync_post_type( $post_id ) ) {
			self::sync_to_discourse( $post_id, $post->post_title, $post->post_content );
		}
	}

	/**
	 * For publishing by xmlrpc.
	 *
	 * Hooks into 'xmlrpc_publish_post'.
	 *
	 * @param int $postid The post id.
	 */
	public function xmlrpc_publish_post_to_discourse( $postid ) {
		$post = get_post( $postid );
		if ( 'publish' === get_post_status( $postid ) && self::is_valid_sync_post_type( $postid ) ) {
			update_post_meta( $postid, 'publish_to_discourse', 1, true );
			self::sync_to_discourse( $postid, $post->post_title, $post->post_content );
		}
	}
	
	/**
	 * Calls `sync_do_discourse_work` after getting the lock.
	 *
	 * @param int    $postid The post id.
	 * @param string $title The title.
	 * @param string $raw The raw content of the post.
	 */
	public function sync_to_discourse( $postid, $title, $raw ) {
		global $wpdb;

		// This avoids a double sync, just 1 is allowed to go through at a time.
		$got_lock = $wpdb->get_row( "SELECT GET_LOCK('discourse_sync_lock', 0) got_it" );
		if ( $got_lock ) {
			self::sync_to_discourse_work( $postid, $title, $raw );
			$wpdb->get_results( "SELECT RELEASE_LOCK('discourse_sync_lock')" );
		}
	}

	/**
	 * Syncs a post to Discourse.
	 *
	 * @param int    $postid The post id.
	 * @param string $title The post title.
	 * @param string $raw The content of the post.
	 */
	protected function sync_to_discourse_work( $postid, $title, $raw ) {
		$discourse_id   = get_post_meta( $postid, 'discourse_post_id', true );
		$options        = $this->options;
		$discourse_post = get_post( $postid );
		$use_full_post  = isset( $options['full-post-content'] ) && intval( $options['full-post-content'] ) == 1;

		if ( $use_full_post ) {
			$excerpt = $raw;
		} else {
			$excerpt = apply_filters( 'the_content', $raw );
			$excerpt = wp_trim_words( $excerpt, $options['custom-excerpt-length'] );
		}

		if ( function_exists( 'discourse_custom_excerpt' ) ) {
			$excerpt = discourse_custom_excerpt( $postid );
		}

		// trim to keep the Discourse markdown parser from treating this as code.
		$baked     = trim( Templates\HTMLTemplates::publish_format_html() );
		$baked     = str_replace( '{excerpt}', $excerpt, $baked );
		$baked     = str_replace( '{blogurl}', get_permalink( $postid ), $baked );
		$author_id = $discourse_post->post_author;
		$author    = get_the_author_meta( 'display_name', $author_id );
		$baked     = str_replace( '{author}', $author, $baked );
		$thumb     = wp_get_attachment_image_src( get_post_thumbnail_id( $postid ), 'thumbnail' );
		$baked     = str_replace( '{thumbnail}', '![image](' . $thumb['0'] . ')', $baked );
		$featured  = wp_get_attachment_image_src( get_post_thumbnail_id( $postid ), 'full' );
		$baked     = str_replace( '{featuredimage}', '![image](' . $featured['0'] . ')', $baked );

		$username = get_the_author_meta( 'discourse_username', $discourse_post->post_author );
		if ( ! $username || strlen( $username ) < 2 ) {
			$username = $options['publish-username'];
		}

		// Get publish category of a post
		$publish_post_category = get_post_meta( $discourse_post->ID, 'publish_post_category', true );
		// $publish_post_category = $discourse_post->publish_post_category;
		$default_category = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$category         = isset( $publish_post_category ) ? $publish_post_category : $default_category;

		if ( $category === '' ) {
			$categories = get_the_category();
			foreach ( $categories as $category ) {
				if ( in_category( $category->name, $postid ) ) {
					$category = $category->name;
					break;
				}
			}
		}

		if ( ! $discourse_id > 0 ) {
			$data = array(
				'wp-id'            => $postid,
				'embed_url'        => get_permalink( $postid ),
				'api_key'          => $options['api-key'],
				'api_username'     => $username,
				'title'            => $title,
				'raw'              => $baked,
				'category'         => $category,
				'skip_validations' => 'true',
				'auto_track'       => ( isset( $options['auto-track'] ) && 1 === intval( $options['auto-track'] ) ? 'true' : 'false' ),
			);
			$url  = $options['url'] . '/posts';
			// Use key 'http' even if you send the request to https://.
			$post_options = array(
				'timeout' => 30,
				'method'  => 'POST',
				'body'    => http_build_query( $data ),
			);
			$result       = wp_remote_post( $url, $post_options );

			if ( DiscourseUtilities::validate( $result ) ) {
				$json = json_decode( $result['body'] );

				if ( property_exists( $json, 'id' ) ) {
					$discourse_id = (int) $json->id;
				}

				if ( isset( $discourse_id ) && $discourse_id > 0 ) {
					add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
				}
			}
		} else {
			$data         = array(
				'api_key'          => $options['api-key'],
				'api_username'     => $username,
				'post[raw]'        => $baked,
				'skip_validations' => 'true',
			);
			$url          = $options['url'] . '/posts/' . $discourse_id;
			$post_options = array(
				'timeout' => 30,
				'method'  => 'PUT',
				'body'    => http_build_query( $data ),
			);
			$result       = wp_remote_post( $url, $post_options );

			if ( DiscourseUtilities::validate( $result ) ) {
				$json = json_decode( $result['body'] );

				if ( property_exists( $json, 'id' ) ) {
					$discourse_id = (int) $json->id;
				}

				if ( isset( $discourse_id ) && $discourse_id > 0 ) {
					add_post_meta( $postid, 'discourse_post_id', $discourse_id, true );
				}
			}
		}

		if ( isset( $json->topic_slug ) ) {
			delete_post_meta( $postid, 'discourse_permalink' );
			add_post_meta( $postid, 'discourse_permalink', $options['url'] . '/t/' . $json->topic_slug . '/' . $json->topic_id, true );
		}
	}

	/**
	 * Hmmm.
	 *
	 * @return bool
	 */
	protected function publish_active() {
		if ( isset( $_POST['showed_publish_option'] ) && isset( $_POST['publish_to_discourse'] ) ) {
			return $_POST['publish_to_discourse'] == '1';
		}

		return false;
	}

	/**
	 * @param null $postid The ID of the post in question.
	 *
	 * @return bool
	 */
	protected function is_valid_sync_post_type( $postid = null ) {
		// is_single() etc. is not reliable
		$allowed_post_types = $this->get_allowed_post_types();
		$current_post_type  = get_post_type( $postid );

		return in_array( $current_post_type, $allowed_post_types );
	}

	/**
	 * Returns the array of allowed post types.
	 *
	 * @return mixed
	 */
	protected function get_allowed_post_types() {
		$selected_post_types = $this->options['allowed_post_types'];

		return $selected_post_types;
	}
}
