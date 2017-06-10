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
		if ( $this->should_display_metabox( $post_type ) ) {
			add_meta_box( 'discourse-publish-meta-box', esc_html__( 'Publish to Discourse' ), array(
				$this,
				'render_meta_box',
			), null, 'side', 'high', null );
		}
	}

	/**
	 * Verify metabox display rights.
	 *
	 * @method should_display_metabox
	 *
	 * @param  string $post_type current post type.
	 *
	 * @return boolean
	 */
	private function should_display_metabox( $post_type ) {
		 return isset( $this->options['allowed_post_types'] ) &&
		 	in_array( $post_type, $this->options['allowed_post_types'], true ) &&
		 	current_user_can( 'publish_posts' );
	}

	/**
	 * The callback function for creating the meta box.
	 *
	 * @param object $post The current Post object.
	 */
	public function render_meta_box( $post ) {
		$post_id                = $post->ID;
		$published              = get_post_meta( $post_id, 'discourse_post_id', true );
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
		?>

        <label
                for="publish_to_discourse"><?php esc_html_e( 'Publish post to Discourse:', 'wp-discourse' ); ?>
            <input type="checkbox" name="publish_to_discourse" id="publish_to_discourse" value="1"
				<?php checked( $publish_to_discourse ); ?> >
        </label>
        <br>
		<?php if ( $published ) : ?>
            <hr>
			<?php
			// translators: Discourse post has been published message. Placeholder: Discourse category name in which the post has been published.
			$message = sprintf( __( 'This post has been published to Discourse in the <strong>%s</strong> category.', 'wp-discourse' ), esc_attr( $selected_category_name ) );
			$allowed = array(
				'strong' => array(),
			);
			echo wp_kses( $message, $allowed );
			?>

		<?php elseif ( is_wp_error( $categories ) ) : ?>
            <hr>
            <div class="warning">
                <p>
					<?php
					esc_html_e( 'The Discourse categories list is not currently available. Please check the WP Discourse connection settings,
					or try refreshing the page.', 'wp-discourse' );
					?>
                </p>
            </div>
			<?php // For a new post when the category list can't be displayed, publish to the default category. ?>
            <input type="hidden" name="publish_post_category" value="<?php echo esc_attr( $selected_category ); ?>">

		<?php else : ?>
            <label for="publish_post_category"><?php esc_html_e( 'Category to publish to:', 'wp-discourse' ); ?>
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
		<?php endif; ?>
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
