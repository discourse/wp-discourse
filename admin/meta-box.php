<?php
/**
 * Adds a Discourse Publish meta box to posts that may be published to Discourse.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class MetaBox
 */
class MetaBox {

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * MetaBox constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 1 );
	}

	/**
	 * Setup options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
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
				'discourse-publish-meta-box', esc_html__( 'Publish to Discourse' ), array(
					$this,
					'render_meta_box',
				), null, 'side', 'high', null
			);
		}
	}

	/**
	 * The callback function for creating the meta box.
	 *
	 * @param object $post The current Post object.
	 */
	public function render_meta_box( $post ) {
		$post_id                = $post->ID;
		$published              = get_post_meta( $post_id, 'discourse_post_id', true );
		$publishing_error       = intval( get_post_meta( $post_id, 'wpdc_deleted_topic', true ) ) === 1;
		$force_publish          = ! empty( $this->options['force-publish'] );
		$saved                  = 'publish' === get_post_status( $post_id ) ||
								  'future' === get_post_status( $post_id ) ||
								  'draft' === get_post_status( $post_id ) ||
								  'private' === get_post_status( $post_id ) ||
								  'pending' === get_post_status( $post_id );
		$categories             = DiscourseUtilities::get_discourse_categories();
		$categories             = apply_filters( 'wp_discourse_publish_categories', $categories, $post );
		$selected_category_name = '';

		if ( ! $saved ) {
			$publish_to_discourse = isset( $this->options['auto-publish'] ) ? intval( $this->options['auto-publish'] ) : 0;
			$selected_category    = isset( $this->options['publish-category'] ) ? intval( $this->options['publish-category'] ) : 1;
		} else {
			$publish_to_discourse = get_post_meta( $post_id, 'publish_to_discourse', true );
			$selected_category    = get_post_meta( $post_id, 'publish_post_category', true );
			if ( ! is_wp_error( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( intval( $selected_category ) === $category['id'] ) {
						$selected_category_name = $category['name'];
						break;
					}
				}
			}
		}

		wp_nonce_field( 'publish_to_discourse', 'publish_to_discourse_nonce' );

		if ( $published ) {
			if ( $publishing_error ) {
				$this->unlink_from_discourse();
			} else {
				// The post has been published. Unless 'force-publish' is enabled, display the Update Discourse topic checkbox.
				// translators: Discourse post has been published message. Placeholder: Discourse category name in which the post has been published.
				$message = sprintf( __( 'This post has been published to Discourse in the <strong>%s</strong> category.', 'wp-discourse' ), esc_attr( $selected_category_name ) );
				$allowed = array(
					'strong' => array(),
				);
				echo wp_kses( $message, $allowed ) . '<br><hr>';

				if ( $force_publish ) {
					esc_html_e( 'The Force Publish option is enabled. All post updates will be automatically republished to Discourse.', 'wp-discourse' );
				} else {
					$publish_text = __( 'Update Discourse topic', 'wp-discourse' );
					$this->update_discourse_topic_checkbox( $publish_text, 0 );
				}
			}
		} else {
			// The post has not been published. Display the Publish post checkbox unless 'force-publish' is enabled.
			if ( $force_publish ) {
				// translators: Discourse force-publish message.
				$message = sprintf( __( 'The <strong>force-publish</strong> option has been enabled. All WordPress posts will be published to Discourse.', 'wp-discourse' ) );
				$allowed = array(
					'strong' => array(),
				);
				echo wp_kses( $message, $allowed ) . '<br><hr>';

			} else {
				$publish_text = __( 'Publish post to Discourse', 'wp-discourse' );
				$this->publish_to_discourse_checkbox( $publish_text, $publish_to_discourse );
			}

			if ( is_wp_error( $categories ) ) {
				?>
				<hr>
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
						<?php // For a new post when the category list can't be displayed, publish to the default category. ?>
						<input type="hidden" name="publish_post_category" value="<?php echo esc_attr( $selected_category ); ?>">
						<?php
			} else {
				?>
				<div>
				<label for="publish_post_category"><?php esc_html_e( 'Category', 'wp-discourse' ); ?>
					<select name="publish_post_category" id="publish_post_category">
						<?php foreach ( $categories as $category ) : ?>
							<option
									value="<?php echo( esc_attr( $category['id'] ) ); ?>"
								<?php selected( $selected_category, $category['id'] ); ?>>
								<?php echo( esc_html( $category['name'] ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				</div>
				<?php
			}
		}// End if().
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

		if ( isset( $_POST['unlink_from_discourse'] ) ) { // Input var okay.
			delete_post_meta( $post_id, 'discourse_post_id' );
			delete_post_meta( $post_id, 'discourse_topic_id' );
			delete_post_meta( $post_id, 'discourse_permalink' );
			delete_post_meta( $post_id, 'wpdc_publishing_response' );
			delete_post_meta( $post_id, 'wpdc_deleted_topic' );
		}

		return $post_id;
	}

	/**
	 * Outputs the Publish to Discourse checkbox.
	 *
	 * @param string $text The label text.
	 * @param int    $publish_to_discourse Whether or not the checkbox should be checked.
	 */
	protected function publish_to_discourse_checkbox( $text, $publish_to_discourse ) {
		?>
		<label for="publish_to_discourse"><?php echo esc_html( $text ); ?>
			<input type="checkbox" name="publish_to_discourse" id="publish_to_discourse" value="1"
				<?php checked( $publish_to_discourse ); ?> >
		</label>
		<?php
	}

	/**
	 * Outputs the Update Discourse Topic checkbox.
	 *
	 * @param string $text The label text.
	 * @param int    $update_discourse_topic Whether or not the checkbox should be checked.
	 */
	protected function update_discourse_topic_checkbox( $text, $update_discourse_topic ) {
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
	protected function unlink_from_discourse() {
		?>
		<p>
			<?php
			esc_html_e(
				"An error has been returned while trying to republish your post to Discourse. The most likely cause
            is that the post's associated Discourse topic has been deleted. If that's the case, unlink the post from Discourse so that it
            can be republished as a new topic.", 'wp-discourse'
			);
?>
		</p>
		<label for="unlink_from_discourse"><?php esc_html_e( 'Unlink Post from Discourse', 'wp-discourse' ); ?>
			<input type="checkbox" name="unlink_from_discourse" id="unlink_from_discourse" value="1">
		</label>
		<?php
	}
}
