<?php
/**
 * Publishing Settings.
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class PublishSettings {
	protected $options;
	protected $form_helper;

	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		$this->options = DiscourseUtilities::get_options();

		add_settings_section( 'discourse_publishing_settings_section', __( 'Publishing Settings', 'wp-discourse' ), array(
			$this,
			'publishing_settings_tab_details',
		), 'discourse_publish' );

		add_settings_field( 'discourse_display_subcategories', __( 'Display subcategories', 'wp-discourse' ), array(
			$this,
			'display_subcategories',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_publish_category', __( 'Published category', 'wp-discourse' ), array(
			$this,
			'publish_category_input',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_publish_category_update', __( 'Force category update', 'wp-discourse' ), array(
			$this,
			'publish_category_input_update',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_full_post_content', __( 'Use full post content', 'wp-discourse' ), array(
			$this,
			'full_post_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_custom_excerpt_length', __( 'Custom excerpt length', 'wp-discourse' ), array(
			$this,
			'custom_excerpt_length',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_auto_publish', __( 'Auto Publish', 'wp-discourse' ), array(
			$this,
			'auto_publish_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_auto_track', __( 'Auto Track Published Topics', 'wp-discourse' ), array(
			$this,
			'auto_track_checkbox',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		add_settings_field( 'discourse_allowed_post_types', __( 'Post Types to publish to Discourse', 'wp-discourse' ), array(
			$this,
			'post_types_select',
		), 'discourse_publish', 'discourse_publishing_settings_section' );

		register_setting( 'discourse_publish', 'discourse_publish', array(
			$this->form_helper,
			'validate_options',
		) );
	}

	/**
	 * ---------------------------
	 * Publishing settings fields.
	 * ---------------------------
	 */

	/**
	 * Outputs markup for the display-subcategories checkbox.
	 */
	public function display_subcategories() {
		$this->form_helper->checkbox_input( 'display-subcategories', 'discourse_publish', __( 'Include subcategories in the list of available categories. You need to
		save this setting before subcategories will be available in the category list.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category input.
	 */
	public function publish_category_input() {
		$this->form_helper->category_select( 'publish-category', 'discourse_publish', __( 'The default category that your posts will have on Discourse (this can be changed in the \'Publish to Discourse\' meta-box when you create a post.)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the publish-category-update input.
	 */
	public function publish_category_input_update() {
		$this->form_helper->checkbox_input( 'publish-category-update', 'discourse_publish', __( 'Update the discourse publish category list, (normally set to refresh every hour.)', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the use-full-post checkbox.
	 */
	public function full_post_checkbox() {
		$discourse_admin_posting_url = isset( $this->options['url'] ) && ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/site_settings/category/posting' : null;
		if ( $discourse_admin_posting_url ) {
			$discourse_admin_posting_link = '<a href="' . esc_url_raw( $discourse_admin_posting_url ) . '" target="_blank">' . esc_url( $discourse_admin_posting_url ) . '</a>.';
			$description                  = __( '<strong>Note:</strong> to keep the \'Show Full Post\'</strong> button
            from appearing under your post on Discourse, you must unselect the <strong>\'embed truncate\'</strong> setting on Discourse.
			This setting is found at ', 'wp-discourse' ) . $discourse_admin_posting_link;
		} else {
			$description = __( '<strong>Note: to keep the \'Show Full Post\'</strong> button from appearing under your post on Discourse, you must uncheck the <strong>\'embed truncate\'</strong> setting on Discourse.
			This setting is found at http://discourse.example.com/admin/site_settings/category/posting.', 'wp-discourse' );
		}

		$this->form_helper->checkbox_input( 'full-post-content', 'discourse_publish', __( 'Use the full post for content rather than an excerpt.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the custom-excerpt-length input.
	 */
	public function custom_excerpt_length() {
		$this->form_helper->input( 'custom-excerpt-length', 'discourse_publish', __( 'Custom excerpt length in words (default: 55).', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for the auto-publish checkbox.
	 */
	public function auto_publish_checkbox() {
		$this->form_helper->checkbox_input( 'auto-publish', 'discourse_publish', __( 'Publish all new posts to Discourse.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the auto-track checkbox.
	 */
	public function auto_track_checkbox() {
		$this->form_helper->checkbox_input( 'auto-track', 'discourse_publish', __( 'Author automatically tracks published Discourse topics.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for the post-types select input.
	 */
	public function post_types_select() {
		$this->form_helper->post_type_select_input( 'allowed_post_types',
			$this->form_helper->post_types_to_publish( array( 'attachment' ) ),
		__( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple options.', 'wp-discourse' ) );
	}

	/**
	 * Details for the 'publishing_options' tab.
	 */
	function publishing_settings_tab_details() {
		?>
		<p class="documentation-link">
			<em><?php esc_html_e( 'This section is for configuring how the plugin publishes posts to Discourse. For detailed instructions, see the  ', 'wp-discourse' ); ?></em>
			<a href="https://github.com/discourse/wp-discourse/wiki/Setup">Setup</a>
			<em><?php esc_html_e( ' section of the WP Discourse wiki.', 'wp-discourse' ); ?></em>
		</p>
		<?php
	}
}
