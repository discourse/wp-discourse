<?php
/**
 * Adds a Discourse Publish meta box to posts that may be published to Discourse.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class MetaBox
 */
class MetaBox {
	use PluginUtilities;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * The Discourse categories.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $categories;

	/**
	 * MetaBox constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_meta_box_js' ) );
		add_action( 'auto-draft_to_draft', array( $this, 'check_for_quickdrafts' ) );
	}

	/**
	 * Enqueue meta_box_js.
	 */
	public function enqueue_meta_box_js() {
		wp_register_script( 'meta_box_js', plugins_url( '../admin/js/meta-box.js', __FILE__ ), array( 'jquery' ), WPDISCOURSE_VERSION, true );
		wp_enqueue_script( 'meta_box_js' );
		$max_tags = ! isset( $this->options['max-tags'] ) ? 5 : $this->options['max-tags'];
		$data     = array(
			'maxTags' => $max_tags,
		);
		wp_localize_script( 'meta_box_js', 'wpdc', $data );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options    = $this->get_options();
		$this->categories = $this->get_discourse_categories();
	}

	/**
	 * Registers a meta box for the allowed post types.
	 *
	 * @param string $post_type The post_type of the current post.
	 */
	public function add_meta_box( $post_type ) {
		if ( isset( $this->options['allowed_post_types'] ) &&
			 in_array( $post_type, $this->options['allowed_post_types'], true )
		) {
			add_meta_box(
				'discourse-publish-meta-box', esc_html__( 'Discourse' ), array(
					$this,
					'render_meta_box',
				), null, 'side', 'high', null
			);
		}
	}

	/**
	 * If a Quick Draft has been converted to a draft, add the default Discourse metadata to the post.
	 *
	 * @param \WP_Post $post The draft post that has transitioned.
	 */
	public function check_for_quickdrafts( $post ) {
		if ( in_array( $post->post_type, $this->options['allowed_post_types'], true ) ) {
			$post_id          = $post->ID;
			$default_category = ! empty( $this->options['publish-category'] ) ? $this->options['publish-category'] : 0;
			update_post_meta( $post_id, 'publish_post_category', $default_category );
			if ( ! empty( $this->options['auto-publish'] ) ) {
				update_post_meta( $post_id, 'publish_to_discourse', 1 );
			}
		}
	}

	/**
	 * The callback function for creating the meta box.
	 *
	 * @param \WP_Post $post The current Post object.
	 */
	public function render_meta_box( $post ) {
		$post_id              = $post->ID;
		$published            = get_post_meta( $post_id, 'discourse_post_id', true );
		$publishing_error     = intval( get_post_meta( $post_id, 'wpdc_deleted_topic', true ) ) === 1;
		$force_publish        = ! empty( $this->options['force-publish'] );
		$saved                = 'publish' === get_post_status( $post_id ) ||
							   'future' === get_post_status( $post_id ) ||
							   'draft' === get_post_status( $post_id ) ||
							   'private' === get_post_status( $post_id ) ||
							   'pending' === get_post_status( $post_id );
		$publish_to_discourse = $saved ? get_post_meta( $post_id, 'publish_to_discourse', true ) : $this->options['auto-publish'];
		$publish_category_id  = $saved ? get_post_meta( $post_id, 'publish_post_category', true ) : $this->options['publish-category'];
		$default_category_id  = ! empty( $this->options['publish-category'] ) ? $this->options['publish-category'] : null;
		$pin_topic            = get_post_meta( $post_id, 'wpdc_pin_topic', true );
		$pin_until            = get_post_meta( $post_id, 'wpdc_pin_until', true );
		$unlisted             = get_post_meta( $post_id, 'wpdc_unlisted_topic', true );

		wp_nonce_field( 'publish_to_discourse', 'publish_to_discourse_nonce' );

		if ( ! $published ) {
			if ( $force_publish ) {
				$this->force_publish_markup( $default_category_id );
			} else {
				?>
				<label for="wpdc_publish_option">
					<input type="radio" name="wpdc_publish_options" value="new" checked><?php esc_html_e( 'Create new Topic' ); ?>
				</label><br>
				<label for="wpdc_publish_options">
					<input type="radio" name="wpdc_publish_options" value="link"><?php esc_html_e( 'Link to Existing Topic' ); ?>
				</label>
				<?php
				if ( is_wp_error( $this->categories ) ) {
					echo '<hr>';
					$this->category_error_markup();
				} else {
					?>
					<div class="wpdc-new-discourse-topic">
						<hr>
						<?php
						$publish_text = __( 'Publish post to Discourse', 'wp-discourse' );
						$this->publish_to_discourse_checkbox( $publish_text, $publish_to_discourse );
						?>
						<br>
						<?php $this->category_select_input( $publish_category_id ); ?>
						<hr>
						<?php $this->advanced_options_input( $pin_topic, $pin_until, $unlisted ); ?>
					</div>
					<div class="wpdc-link-to-topic hidden">
						<hr>
						<?php $this->link_to_discourse_topic_input(); ?>
					</div>
					<?php
				}
			} // End if().
		} else {
			// The post has already been published to Discourse.
			if ( $publishing_error ) {
				$this->publishing_error_markup( $force_publish );
			} else {
				$discourse_permalink = get_post_meta( $post_id, 'discourse_permalink', true );
				$discourse_link      = '<a href="' . esc_url( $discourse_permalink ) . '" target="_blank">' . esc_url( $discourse_permalink ) . '</a>';
				// translators: Discourse post_is_linked_to_discourse message. Placeholder: A link to the Discourse topic.
				$message = sprintf( __( 'This post is linked to %1$s.<br><hr>', 'wp-discourse' ), $discourse_link );
				echo wp_kses_post( $message );
				if ( $force_publish ) {
					esc_html_e( 'The Force Publish option is enabled. All post updates will be automatically republished to Discourse.', 'wp-discourse' );
				} else {
					$publish_text = __( 'Update Discourse topic', 'wp-discourse' );
					$this->update_discourse_topic_checkbox( $publish_text );
					echo '<br>';
					$this->unlink_from_discourse_checkbox();
				}
			}
		} // End if().
	}

	/**
	 * Verifies the nonce and saves the meta data.
	 *
	 * @param int $post_id The id of the current post.
	 *
	 * @return int
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['publish_to_discourse_nonce'] ) || // Input var okay.
			 ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['publish_to_discourse_nonce'] ) ), 'publish_to_discourse' ) // Input var okay.
		) {

			return 0;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {

			return 0;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return 0;
		}

		if ( isset( $_POST['publish_post_category'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'publish_post_category', intval( wp_unslash( $_POST['publish_post_category'] ) ) ); // Input var okay.
		}

		if ( isset( $_POST['publish_to_discourse'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'publish_to_discourse', intval( wp_unslash( $_POST['publish_to_discourse'] ) ) ); // Input var okay.
		} else {
			update_post_meta( $post_id, 'publish_to_discourse', 0 );
		}

		if ( isset( $_POST['update_discourse_topic'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'update_discourse_topic', intval( wp_unslash( $_POST['update_discourse_topic'] ) ) ); // Input var okay.
		} else {
			update_post_meta( $post_id, 'update_discourse_topic', 0 );
		}

		if ( ! empty( $_POST['pin_discourse_topic'] ) ) { // Input var okay.
			if ( ! empty( $_POST['pin_discourse_topic_until'] ) ) { // Input var okay.
				$pin_until = sanitize_text_field( wp_unslash( $_POST['pin_discourse_topic_until'] ) ); // Input var okay.
			} else {
				$now = new \DateTime( 'now' );
				try {
					$pin_until = $now->add( new \DateInterval( 'P2D' ) )->format( 'Y-m-d' );
				} catch ( \Exception $e ) {
					$pin_until = null;
				}
			}

			update_post_meta( $post_id, 'wpdc_pin_topic', 1 );
			update_post_meta( $post_id, 'wpdc_pin_until', $pin_until );
		}

		if ( ! empty( $_POST['wpdc_topic_tags'] ) ) { // Input var okay.
			$tags = array_map( 'sanitize_text_field', wp_unslash( $_POST['wpdc_topic_tags'] ) ); // Input var okay.
			update_post_meta( $post_id, 'wpdc_topic_tags', $tags );
		}

		if ( ! empty( $_POST['unlist_discourse_topic'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'wpdc_unlisted_topic', 1 );
		}

		// Delete all Discourse metadata that could be associated with a post.
		if ( isset( $_POST['unlink_from_discourse'] ) ) { // Input var okay.
			delete_post_meta( $post_id, 'discourse_post_id' );
			delete_post_meta( $post_id, 'discourse_topic_id' );
			delete_post_meta( $post_id, 'discourse_permalink' );
			delete_post_meta( $post_id, 'discourse_comments_raw' );
			delete_post_meta( $post_id, 'discourse_comments_count' );
			delete_post_meta( $post_id, 'discourse_last_sync' );
			delete_post_meta( $post_id, 'publish_to_discourse' );
			delete_post_meta( $post_id, 'publish_post_category' );
			delete_post_meta( $post_id, 'update_discourse_topic' );
			delete_post_meta( $post_id, 'wpdc_sync_post_comments' );
			delete_post_meta( $post_id, 'wpdc_publishing_response' );
			delete_post_meta( $post_id, 'wpdc_deleted_topic' );
		}

		if ( ! empty( $_POST['link_to_discourse_topic'] ) ) { // Input var okay.
			$topic_url = esc_url_raw( wp_unslash( $_POST['link_to_discourse_topic'] ) ); // Input var okay.
			$this->link_to_discourse_topic( $post_id, $topic_url );
		}

		return $post_id;
	}

	/**
	 * Outputs the markup that is displayed when the force_publish option is enabled.
	 *
	 * @param int $default_category_id The category_id to publish to.
	 */
	protected function force_publish_markup( $default_category_id ) {
		$category_name = $this->get_discourse_category_name( $default_category_id );
		if ( ! is_wp_error( $category_name ) ) {
			// translators: Discourse force-publish message. Placeholder: category_name.
			$message = sprintf( __( 'The <strong>force-publish</strong> option has been enabled. All WordPress posts will be published to Discourse in the <strong>%1$s</strong> category.', 'wp-discourse' ), $category_name );
        } else {
		    $publishing_url = admin_url( '/admin.php?page=publishing_options' );
		    $publishing_link = '<a href="' . esc_url( $publishing_url ) . '" target="_blank">' . __( 'Publishing Options', 'wp-discourse' ) . '</a>';
            // translators: Discourse force-publish-category-not-set message. Placeholder: publishing_options_link.
		    $message = sprintf( __( 'The <strong>force-publish</strong> option has been enabled, but you have not set a default publishing category. You can set that category on your %1s tab.', 'wp-discourse' ), $publishing_link );

        }
		echo wp_kses_post( $message );
	}

	/**
	 * Outputs the Publish to Discourse checkbox.
	 *
	 * @param string $text The label text.
	 * @param int    $publish_to_discourse Whether or not the checkbox should be checked.
	 */
	protected function publish_to_discourse_checkbox( $text, $publish_to_discourse ) {
		?>
		<label for="publish_to_discourse">
			<input type="checkbox" name="publish_to_discourse" id="publish_to_discourse" value="1"
				<?php checked( $publish_to_discourse ); ?> >
			<?php echo esc_html( $text ); ?>
		</label>
		<?php
	}

	/**
	 * Outputs the Link to Discourse topic URL input.
	 */
	protected function link_to_discourse_topic_input() {
		?>
		<label for="link_to_discourse_topic">
			<?php esc_html_e( 'Topic URL', 'wp-discourse' ); ?>
			<input type="url" name="link_to_discourse_topic" id="link_to_discourse_topic" class="widefat">
		</label>
		<?php
	}

	/**
	 * Outputs the Update Discourse Topic checkbox.
	 *
	 * @param string $text The label text.
	 * @param bool   $update_discourse_topic Whether or not the checkbox should be checked.
	 */
	protected function update_discourse_topic_checkbox( $text, $update_discourse_topic = false ) {
		?>
		<label for="update_discourse_topic"><?php echo esc_html( $text ); ?>
			<input type="checkbox" name="update_discourse_topic" id="update_discourse_topic" value="1"
				<?php checked( $update_discourse_topic ); ?> >
		</label>
		<?php
	}

	/**
	 * Outputs the unlink from Discourse Topic checkbox.
	 */
	protected function unlink_from_discourse_checkbox() {
		?>
		<label for="unlink_from_discourse"><?php esc_html_e( 'Unlink Post from Discourse?', 'wp-discourse' ); ?>
			<input type="checkbox" name="unlink_from_discourse" id="unlink_from_discourse" value="1">
		</label>
		<?php
	}

	/**
	 * Outputs the pin_topic checkbox.
	 *
	 * @param  int|bool    $pin_topic Whether or not the pin_topic checkbox has been checked.
	 * @param string|null $pin_until When to pin until.
	 */
	protected function pin_topic_input( $pin_topic, $pin_until ) {
		?>
		<label for="pin_discourse_topic">

			<input type="checkbox" name="pin_discourse_topic" id="pin_discourse_topic" value="1"
				<?php checked( $pin_topic ); ?> >
			<?php esc_html_e( 'Pin Topic', 'wp-discourse' ); ?>
		</label><br>
		<div class="wpdc-pin-topic-time hidden">
			<label for="pin_discourse_topic_until">
				<?php esc_html_e( 'Pin Until', 'wp-discourse' ); ?><br>
				<input type="date" name="pin_discourse_topic_until" value="<?php echo esc_attr( $pin_until ); ?>">
			</label>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the unlisted_topic checkbox.
	 *
	 * @param int|bool $unlisted Whether or not the checkbox has been checked.
	 */
	protected function unlisted_topic_checkbox( $unlisted ) {
		$webhook_url          = admin_url( '/admin.php?page=webhook_options' );
		$webhook_options_link = '<a href="' . esc_url( $webhook_url ) . '" target="_blank">' . __( 'Sync Comment Data webhook', 'wp-discourse' ) . '</a>';
		$info_message         = sprintf(
			// translators: Unlisted topic option description. Placeholder: webhook options link.
			__( 'If you have configured the %1s, topics will be listed when they receive a comment.', 'wp-discourse' ), $webhook_options_link
		)
		?>
		<label for="unlist_discourse_topic">

			<input type="checkbox" name="unlist_discourse_topic" value="1"
				<?php checked( $unlisted ); ?> >
			<?php esc_html_e( 'Publish as Unlisted', 'wp-discourse' ); ?><br>
			<div class="wpdc-publish-info"><?php echo wp_kses_post( $info_message ); ?></div>
		</label>
		<?php
	}

	/**
	 * Outputs the tag_topic input.
	 */
	protected function tag_topic_input() {
		?>
		<label for="discourse_topic_tags">
			<?php esc_html_e( 'Tag Topic', 'wp-discourse' ); ?><br>
			<input type="text" name="discourse_topic_tags" id="discourse-topic-tags">
			<input type="button" class="button" id="wpdc-tagadd" value="Add">
			<ul id="wpdc-tagchecklist"></ul>
			<div class="wpdc-taglist-errors"></div>
		</label>
		<?php
	}

	/**
	 * Outputs the markup for the advanced publishing options.
	 *
	 * @param int|bool    $pin_topic Whether or not to pin the topic.
	 * @param string|null $pin_until When to pin the topic until.
	 * @param int|bool    $unlisted Whether or not the topic is unlisted.
	 */
	protected function advanced_options_input( $pin_topic, $pin_until, $unlisted ) {
		?>
		<div class="wpdc-advanced-options-toggle"><?php esc_html_e( 'Advanced Options', 'wp-discourse' ); ?></div>
		<div class="wpdc-advanced-options hidden">
			<?php $this->pin_topic_input( $pin_topic, $pin_until ); ?>
			<?php $this->unlisted_topic_checkbox( $unlisted ); ?><br>
			<?php
			if ( ! empty( $this->options['allow-tags'] ) ) {
				$this->tag_topic_input();
			}
		?>
		</div>
		<?php
	}

	/**
	 * Links a WordPress post to a Discourse topic.
	 *
	 * @param int    $post_id The WordPress post_id to link to.
	 * @param string $topic_url The Discourse topic URL.
	 *
	 * @return null|\WP_Error
	 */
	protected function link_to_discourse_topic( $post_id, $topic_url ) {
		// Remove 'publish_to_discourse' metadata so we don't publish and link to the post.
		delete_post_meta( $post_id, 'publish_to_discourse' );
		$topic_url = explode( '?', $topic_url )[0];

		$topic_domain = wp_parse_url( $topic_url, PHP_URL_HOST );
		if ( get_option( 'wpdc_discourse_domain' ) !== $topic_domain ) {
			update_post_meta( $post_id, 'wpdc_linking_response', 'invalid_url' );

			return new \WP_Error( 'wpdc_configuration_error', 'An invalid topic URL was supplied when attempting to link post to Discourse topic.' );
		}
		$topic = $this->get_discourse_topic( $topic_url );

		// Check for the topic->post_stream here just to make sure it's a valid topic.
		if ( is_wp_error( $topic ) || empty( $topic->post_stream ) ) {
			update_post_meta( $post_id, 'wpdc_linking_response', 'error' );

			return new \WP_Error( 'wpdc_response_error', 'Unable to link to Discourse topic.' );
		}

		update_post_meta( $post_id, 'wpdc_linking_response', 'success' );

		$discourse_post_id        = $topic->post_stream->stream[0];
		$topic_id                 = $topic->id;
		$category_id              = $topic->category_id;
		$discourse_comments_count = $topic->posts_count - 1;
		$topic_slug               = $topic->slug;
		$discourse_permalink      = esc_url_raw( "{$this->options['url']}/t/{$topic_slug}/{$topic_id}" );

		update_post_meta( $post_id, 'discourse_post_id', $discourse_post_id );
		update_post_meta( $post_id, 'discourse_topic_id', $topic_id );
		update_post_meta( $post_id, 'publish_post_category', $category_id );
		update_post_meta( $post_id, 'discourse_permalink', $discourse_permalink );
		update_post_meta( $post_id, 'discourse_comments_count', $discourse_comments_count );
		if ( ! empty( $this->options['use-discourse-webhook'] ) ) {
			update_post_meta( $post_id, 'wpdc_sync_post_comments', 1 );
		}

		return null;
	}

	/**
	 * Outputs the category select input.
	 *
	 * @param int $publish_category_id The Discourse category_id.
	 */
	protected function category_select_input( $publish_category_id ) {
		$categories = apply_filters( 'wp_discourse_publish_categories', $this->categories );
		?>
		<label for="publish_post_category"><?php esc_html_e( 'Category', 'wp-discourse' ); ?>
			<select class="widefat" name="publish_post_category" id="publish_post_category">
				<?php foreach ( $categories as $category ) : ?>
					<option
							value="<?php echo( esc_attr( $category['id'] ) ); ?>"
						<?php selected( $publish_category_id, $category['id'] ); ?>>
						<?php echo( esc_html( $category['name'] ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Gets the Discourse category name from the category_id.
	 *
	 * @param int $category_id The Discourse category_id.
	 *
	 * @return string|\WP_Error
	 */
	protected function get_discourse_category_name( $category_id ) {
		$categories = $this->categories;
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				if ( $category_id === $category['id'] ) {

					return $category['name'];
				}
			}

			return new \WP_Error( 'wpdc_category_not_found', 'The category name could not be found. Try updating the Discourse categories on the WP Discourse publishing options tab.' );
		}

		return new \WP_Error( 'wpdc_categories_error', 'The Discourse category list could not be returned. Check your Connection settings.' );
	}

	/**
	 * The markup that is displayed when the categories can't be retrieved.
	 */
	protected function category_error_markup() {
		?>
		<div class="warning">
			<p>
				<?php
				esc_html_e(
					'The Discourse categories list is not currently available. Please check the WP Discourse connection settings,
                        or try refreshing the page.', 'wp-discourse'
				);
				?>
			</p>
		</div>
        <?php
	}

	/**
	 * The message to be displayed when a 404 or 500 error has been returned after publishing a post to Discourse.
	 *
	 * @param bool $force_publish Whether or not the force_publish option has been selected.
	 */
	protected function publishing_error_markup( $force_publish ) {
		esc_html_e(
			"An error has been returned while trying to republish your post to Discourse. The most likely cause
            is that the post's associated Discourse topic has been deleted. If that's the case, unlink the post from Discourse so that it
            can be republished as a new topic.", 'wp-discourse'
		);
		echo '<hr>';
		$this->unlink_from_discourse_checkbox();
		if ( ! $force_publish ) {
			echo '<br>';
			$publish_text = __( 'Try Updating the Topic', 'wp-discourse' );
			$this->update_discourse_topic_checkbox( $publish_text );
		}
	}
}
