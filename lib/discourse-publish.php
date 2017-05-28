<?php
/**
 * Publishes a post to Discourse.
 *
 * @package WPDicourse
 */

namespace WPDiscourse\DiscoursePublish;

use WPDiscourse\Templates\HTMLTemplates as Templates;
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
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		// Priority is set to 13 so that 'publish_post_after_save' is called after the meta-box is saved.
		add_action( 'save_post', array( $this, 'publish_post_after_save' ), 13, 2 );
		add_action( 'xmlrpc_publish_post', array( $this, 'xmlrpc_publish_post_to_discourse' ) );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Published a post to Discourse after it has been saved.
	 *
	 * @param int    $post_id The id of the post that has been saved.
	 * @param object $post The Post object.
	 */
	public function publish_post_after_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) ) {

			return;
		}

		$post_is_published    = 'publish' === get_post_status( $post_id );
		$publish_to_discourse = get_post_meta( $post_id, 'publish_to_discourse', true );
		$publish_to_discourse = apply_filters( 'wpdc_publish_after_save', $publish_to_discourse, $post_id, $post );

		if ( $publish_to_discourse && $post_is_published && $this->is_valid_sync_post_type( $post_id ) ) {
			$title = $this->sanitize_title( $post->post_title );
			$this->sync_to_discourse( $post_id, $title, $post->post_content );

		} elseif ( $post_is_published && $this->is_valid_sync_post_type( $post_id ) && isset( $this->options['auto-publish'] ) &&
		           1 === intval( $this->options['auto-publish'] )
		) {
			// The post should have been published.
			$this->notify_admin( $post, array(
				'location' => 'after_save',
			) );
		}
	}

	/**
	 * For publishing by xmlrpc.
	 *
	 * Hooks into 'xmlrpc_publish_post'. Publishing through this hook is disabled. This is to prevent
	 * posts being inadvertently published to Discourse when they are edited using blogging software.
	 * This can be overridden by hooking into the `wp_discourse_before_xmlrpc_publish` filter and setting
	 * `$publish_to_discourse` to true based on some condition - testing for the presence of a tag can
	 * work for this.
	 *
	 * @param int $post_id The post id.
	 */
	public function xmlrpc_publish_post_to_discourse( $post_id ) {
		$post                 = get_post( $post_id );
		$post_is_published    = 'publish' === get_post_status( $post_id );
		$publish_to_discourse = false;
		$publish_to_discourse = apply_filters( 'wp_discourse_before_xmlrpc_publish', $publish_to_discourse, $post );

		if ( $publish_to_discourse && $post_is_published && $this->is_valid_sync_post_type( $post_id ) ) {
			update_post_meta( $post_id, 'publish_to_discourse', 1 );
			$title = $this->sanitize_title( $post->post_title );
			$this->sync_to_discourse( $post_id, $title, $post->post_content );
		} elseif ( $post_is_published && isset( $this->options['auto-publish'] ) && 1 === intval( $this->options['auto-publish'] ) ) {
			$this->notify_admin( $post, array(
				'location' => 'after_xmlrpc_publish',
			) );
		}
	}

	/**
	 * Calls `sync_do_discourse_work` after getting the lock.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title The title.
	 * @param string $raw The raw content of the post.
	 */
	public function sync_to_discourse( $post_id, $title, $raw ) {
		global $wpdb;

		wp_cache_set( 'discourse_publishing_lock', $wpdb->get_row( "SELECT GET_LOCK( 'discourse_publish_lock', 0 ) got_it" ) );

		// This avoids a double sync, just 1 is allowed to go through at a time.
		if ( 1 === intval( wp_cache_get( 'discourse_publishing_lock' )->got_it ) ) {
			$this->sync_to_discourse_work( $post_id, $title, $raw );
			wp_cache_set( 'discourse_publishing_lock', $wpdb->get_results( "SELECT RELEASE_LOCK( 'discourse_publish_lock' )" ) );
		}
	}

	/**
	 * Syncs a post to Discourse.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title The post title.
	 * @param string $raw The content of the post.
	 *
	 * @return null
	 */
	protected function sync_to_discourse_work( $post_id, $title, $raw ) {
		$discourse_id  = get_post_meta( $post_id, 'discourse_post_id', true );
		$options       = $this->options;
		$current_post  = get_post( $post_id );
		$use_full_post = isset( $options['full-post-content'] ) && 1 === intval( $options['full-post-content'] );

		if ( $use_full_post ) {
			$excerpt = apply_filters( 'wp_discourse_excerpt', $raw );
		} else {
			if ( has_excerpt( $post_id ) ) {
				$wp_excerpt = apply_filters( 'get_the_excerpt', $current_post->post_excerpt );
				$excerpt    = apply_filters( 'wp_discourse_excerpt', $wp_excerpt );
			} else {
				$excerpt = apply_filters( 'the_content', $raw );
				$excerpt = apply_filters( 'wp_discourse_excerpt', wp_trim_words( $excerpt, $options['custom-excerpt-length'] ) );
			}
		}

		// Trim to keep the Discourse markdown parser from treating this as code.
		$baked     = trim( Templates::publish_format_html() );
		$baked     = str_replace( '{excerpt}', $excerpt, $baked );
		$baked     = str_replace( '{blogurl}', get_permalink( $post_id ), $baked );
		$author_id = $current_post->post_author;
		$author    = get_the_author_meta( 'display_name', $author_id );
		$baked     = str_replace( '{author}', $author, $baked );
		$thumb     = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
		$baked     = str_replace( '{thumbnail}', '![image](' . $thumb['0'] . ')', $baked );
		$featured  = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
		$baked     = str_replace( '{featuredimage}', '![image](' . $featured['0'] . ')', $baked );

		$username = get_the_author_meta( 'discourse_username', $current_post->post_author );
		if ( ! $username || strlen( $username ) < 2 ) {
			$username = $options['publish-username'];
		}

		// Get publish category of a post.
		$publish_post_category = get_post_meta( $post_id, 'publish_post_category', true );
		$default_category      = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$category              = isset( $publish_post_category ) ? $publish_post_category : $default_category;

		// The post hasn't been published to Discourse yet.
		if ( ! $discourse_id > 0 ) {
			$data = array(
				'wp-id'            => $post_id,
				'embed_url'        => get_permalink( $post_id ),
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

		} else {
			// The post has already been published.
			$data         = array(
				'api_key'          => $options['api-key'],
				'api_username'     => $username,
				'title'            => $title,
				'post[raw]'        => $baked,
				'skip_validations' => 'true',
			);
			$url          = $options['url'] . '/posts/' . $discourse_id;
			$post_options = array(
				'timeout' => 30,
				'method'  => 'PUT',
				'body'    => http_build_query( $data ),
			);
		}// End if().

		$result = wp_remote_post( $url, $post_options );

		if ( ! DiscourseUtilities::validate( $result ) ) {
			update_post_meta( $post_id, 'wpdc_publishing_response', 'error' );
			$this->notify_admin( $current_post, array(
				'location' => 'after_bad_response',
			) );

			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $result ) );

		if ( property_exists( $body, 'id' ) ) {
			$discourse_id = (int) $body->id;

			if ( ( isset( $discourse_id ) && $discourse_id > 0 ) && isset( $body->topic_slug ) && isset( $body->topic_id ) ) {

				add_post_meta( $post_id, 'discourse_post_id', $discourse_id, true );
				update_post_meta( $post_id, 'discourse_permalink', $options['url'] . '/t/' . $body->topic_slug . '/' . $body->topic_id );
				update_post_meta( $post_id, 'wpdc_publishing_response', 'success' );

			} else {
				update_post_meta( $post_id, 'wpdc_publishing_response', 'error' );
				$this->notify_admin( $current_post, array(
					'location' => 'after_bad_response',
				) );

				return null;
			}
		} elseif ( property_exists( $body, 'post' ) ) {
			$discourse_post = $body->post;

			if ( isset( $discourse_post->topic_slug ) && isset( $discourse_post->topic_id ) ) {
				update_post_meta( $post_id, 'discourse_permalink', $options['url'] . '/t/' . $discourse_post->topic_slug . '/' . $discourse_post->topic_id );
				update_post_meta( $post_id, 'wpdc_publishing_response', 'success' );

			} else {
				update_post_meta( $post_id, 'wpdc_publishing_response', 'error' );
				$this->notify_admin( $current_post, array(
					'location' => 'after_bad_response',
				) );

				return null;
			}
		}
	}

	/**
	 * Checks if a post_type can be synced.
	 *
	 * @param null $post_id The ID of the post in question.
	 *
	 * @return bool
	 */
	protected function is_valid_sync_post_type( $post_id = null ) {
		$allowed_post_types = $this->get_allowed_post_types();
		$current_post_type  = get_post_type( $post_id );

		return in_array( $current_post_type, $allowed_post_types, true );
	}

	/**
	 * Returns the array of allowed post types.
	 *
	 * @return mixed
	 */
	protected function get_allowed_post_types() {
		if ( isset( $this->options['allowed_post_types'] ) && is_array( $this->options['allowed_post_types'] ) ) {
			$selected_post_types = $this->options['allowed_post_types'];

			return $selected_post_types;
		} else {
			// Return an empty array, otherwise if all post types have been deselectd on the options page
			// functions using this function will be trying to access the key of `null`.
			return array();
		}
	}

	/**
	 * Strip html tags from titles before passing them to Discourse.
	 *
	 * @param string $title The title of the post.
	 *
	 * @return string
	 */
	protected function sanitize_title( $title ) {
		return wp_strip_all_tags( $title );
	}

	/**
	 * Sends a notification email to a site admin if a post fails to publish on Discourse.
	 *
	 * @param object $post $discourse_post The post where the failure occurred.
	 * @param array  $args Optional arguments for the function. The 'location' argument can be used to indicate where the failure occurred.
	 */
	protected function notify_admin( $post, $args ) {
		$post_id  = $post->ID;
		$location = ! empty( $args['location'] ) ? $args['location'] : '';

		// This is to avoid sending two emails when a post is published through XML-RPC.
		if ( 'after_save' === $location && 1 === intval( get_post_meta( $post_id, 'wpdc_xmlrpc_failure_sent', true ) ) ) {
			delete_post_meta( $post_id, 'wpdc_xmlrpc_failure_sent' );

			return;
		}

		if ( isset( $this->options['publish-failure-notice'] ) && 1 === intval( $this->options['publish-failure-notice'] ) ) {
			$publish_failure_email = ! empty( $this->options['publish-failure-email'] ) ? $this->options['publish-failure-email'] : get_option( 'admin_email' );
			$blogname              = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$post_title            = $post->post_title;
			$post_date             = $post->post_date;
			$post_author           = get_user_by( 'id', $post->post_author )->user_login;
			$permalink             = get_permalink( $post_id );
			$support_url           = 'https://meta.discourse.org/c/support/wordpress';

			// translators: Discourse publishing email. Placeholder: blogname.
			$message = sprintf( __( 'A post has failed to publish on Discourse from your site [%1$s].', 'wp-discourse' ), $blogname ) . "\r\n\r\n";
			// translators: Discourse publishing email. Placeholder: post title.
			$message .= sprintf( __( 'The post \'%1$s\' was published on WordPress', 'wp-discourse' ), $post_title ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: post author, post date.
			$message .= sprintf( __( 'by %1$s, on %2$s.', 'wp-discourse' ), $post_author, $post_date ) . "\r\n\r\n";
			// translators: Discourse publishing email. Placeholder: permalink.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $permalink ) ) . "\r\n\r\n";
			$message .= __( 'Reason for failure:', 'wp-discourse' ) . "\r\n";

			switch ( $location ) {
				case 'after_save':
					$message .= __( 'The \'Publish to Discourse\' checkbox wasn\'t checked.', 'wp-discourse' ) . "\r\n";
					$message .= __( 'You are being notified because you have the \'Auto Publish\' setting enabled.', 'wp-discourse' ) . "\r\n\r\n";
					break;
				case 'after_xmlrpc_publish':
					add_post_meta( $post->ID, 'wpdc_xmlrpc_failure_sent', 1 );
					$message .= __( 'The post was published through XML-RPC.', 'wp-discourse' ) . "\r\n\r\n";
					break;
				case 'after_bad_response':
					$discourse_category_id = get_post_meta( $post_id, 'publish_post_category', true );
					$category_name         = DiscourseUtilities::get_discourse_category_name( $discourse_category_id );

					$message .= __( 'A bad response was returned from Discourse.', 'wp-discourse' ) . "\r\n\r\n";
					$message .= __( 'Check that:', 'wp-discourse' ) . "\r\n";
					$message .= __( '- the author has correctly set their Discourse username', 'wp-discourse' ) . "\r\n";
					// translators: Discourse publishing email. Placeholder: Discourse category name.
					$message .= sprintf( __( '- the author is has permission to publish in the %1$s Discourse category', 'wp-discourse' ), esc_attr( $category_name ) ) . "\r\n\r\n";
					break;
			}

			$message .= __( 'If you\'re having trouble with the WP Discourse plugin, you can find help at:', 'wp-discourse' ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: Discourse support URL.
			$message .= sprintf( __( '<%1$s>', 'wp-discourse' ), esc_url( $support_url ) ) . "\r\n";
			// translators: Discourse publishing email. Placeholder: blogname, email message.
			wp_mail( $publish_failure_email, sprintf( __( '[%s] Discourse Publishing Failure' ), $blogname ), $message );
		}// End if().
	}
}
