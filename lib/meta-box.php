<?php
namespace WPDiscourse\MetaBox;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class MetaBox {
	protected $options;
	
	public function __construct() {
		$this->options = get_option( 'discourse' );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
	}
	public function add_meta_box( $post_type ) {
		if ( in_array( $post_type, $this->options['allowed_post_types'] ) ) {
			add_meta_box( 'discourse-publish-meta-box', __( 'Publish to Discourse' ), array(
				$this,
				'render_meta_box'
			), 'post', 'side', 'high', null );
		}
	}
	public function render_meta_box( $post ) {
		$categories = DiscourseUtilities::get_discourse_categories();

		// If the post has not yet been saved, use the default setting. If it has been saved use the meta value.
		if ( ! get_post_meta( $post->ID, 'has_been_saved', true ) ) {
			$selected_category = intval( $this->options['publish-category'] );
			$publish_to_discourse = isset( $this->options['auto-publish'] ) ? intval( $this->options['auto-publish'] ) : 0;
		} else {
			$selected_category = get_post_meta( $post->ID, 'publish_post_category', true );
			$publish_to_discourse = get_post_meta( $post->ID, 'publish_to_discourse', true );
		}
		ob_start();
		wp_nonce_field( 'publish_to_discourse', 'publish_to_discourse_nonce' );
		?>

		<label for="publish_to_discourse"><?php _e( 'Publish post to Discourse:', 'wp-discourse' ); ?>
			<input type="checkbox" name="publish_to_discourse" id="publish_to_discourse" value="1"
				<?php checked( $publish_to_discourse ); ?> >
		</label>
		<br>
		<label for="publish_post_category"><?php _e( 'Category to publish to:', 'wp-discourse' ); ?>
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

		<?php
		echo ob_get_clean();
	}
	function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['publish_to_discourse_nonce'] ) || ! wp_verify_nonce( $_POST['publish_to_discourse_nonce'], 'publish_to_discourse' ) ) {
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
		if ( isset( $_POST['publish_post_category'] ) ) {
			update_post_meta( $post_id, 'publish_post_category', $_POST['publish_post_category'] );
		}
		if ( isset( $_POST['publish_to_discourse'] ) ) {
			update_post_meta( $post_id, 'publish_to_discourse', $_POST['publish_to_discourse'] );
		} else {
			update_post_meta( $post_id, 'publish_to_discourse', 0 );
		}
		return $post_id;
	}
}