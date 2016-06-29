<?php
/**
 * Adds a Discourse Publish meta box to posts that may be published to Discourse.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\MetaBox;

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
		$this->options = get_option( 'discourse' );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 1 );
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
			add_meta_box( 'discourse-publish-meta-box', esc_html__( 'Publish to Discourse' ), array(
				$this,
				'render_meta_box',
			), null, 'side', 'high', null );
		}
	}

	/**
	 * The callback function for creating the meta box.
	 *
	 * @param object $post The current Post object.
	 */
	public function render_meta_box( $post ) {
		$categories = DiscourseUtilities::get_discourse_categories();

		if ( is_wp_error( $categories ) ) {
			$selected_category    = null;
			$publish_to_discourse = 0;
		} elseif ( ! get_post_meta( $post->ID, 'has_been_saved', true ) ) {

			// If the post has not yet been saved, use the default setting. If it has been saved use the meta value.
			$selected_category    = isset( $this->options['publish-category'] ) ? intval( $this->options['publish-category'] ) : 1;
			$publish_to_discourse = isset( $this->options['auto-publish'] ) ? intval( $this->options['auto-publish'] ) : 0;
		} else {

			$selected_category    = get_post_meta( $post->ID, 'publish_post_category', true );
			$publish_to_discourse = get_post_meta( $post->ID, 'publish_to_discourse', true );
		}

		wp_nonce_field( 'publish_to_discourse', 'publish_to_discourse_nonce' );
		?>

		<label for="publish_to_discourse"><?php esc_html_e( 'Publish post to Discourse:', 'wp-discourse' ); ?>
			<input type="checkbox" name="publish_to_discourse" id="publish_to_discourse" value="1"
				<?php checked( $publish_to_discourse ); ?> >
		</label>
		<br>
		<label for="publish_post_category"><?php esc_html_e( 'Category to publish to:', 'wp-discourse' ); ?>

			<?php if ( is_null( $selected_category ) ) : ?>
				<div class="warning">
					<p>
						<?php
						esc_html_e( "The Discourse categories list is not currently available. To publish this post to Discourse, please check the wp-discourse settings for 'Discourse URL', 'API Key', and 'Publishing username'. Also, make sure that your Discourse forum is online.", 'wp-discourse' );
						?>
					</p>
				</div>
			<?php else : ?>

				<select name="publish_post_category" id="publish_post_category">
					<?php foreach ( $categories as $category ) : ?>
						<option
							value="<?php echo( esc_attr( $category['id'] ) ); ?>"
							<?php selected( $selected_category, $category['id'] ); ?>>
							<?php echo( esc_html( $category['name'] ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>

			<?php endif; ?>

		</label>

		<?php
	}

	/**
	 * Verifies the nonce and saves the meta data.
	 *
	 * @param int $post_id The id of the current post.
	 *
	 * @return int
	 */
	function save_meta_box( $post_id ) {
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

		// Indicate that the post has been saved so that the meta-box gets its values from the meta-data instead of the defaults.
		update_post_meta( $post_id, 'has_been_saved', 1 );

		if ( isset( $_POST['publish_post_category'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'publish_post_category', intval( wp_unslash( $_POST['publish_post_category'] ) ) ); // Input var okay.
		}

		if ( isset( $_POST['publish_to_discourse'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'publish_to_discourse', intval( wp_unslash( $_POST['publish_to_discourse'] ) ) ); // Input var okay.
		} else {
			update_post_meta( $post_id, 'publish_to_discourse', 0 );
		}

		return $post_id;
	}
}
