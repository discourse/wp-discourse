<?php
/**
 * Publishing Settings.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class PublishSettings
 */
class PublishSettings {
	use PluginUtilities;

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
	 */
	protected $form_helper;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Whether or not to use some network publish settings.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $use_network_publish_settings;

	/**
	 * PublishSettings constructor.
	 *
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_publish_settings' ) );
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_publish_settings() {
		$this->options                      = $this->get_options();
		$this->use_network_publish_settings = is_multisite() && ! empty( $this->options['multisite-configuration-enabled'] );

		add_settings_section(
			'discourse_publishing_settings_section',
			__( 'Publishing Settings', 'wp-discourse' ),
			array(
				$this,
				'publishing_settings_tab_details',
			),
			'discourse_publish'
		);

		add_settings_field(
			'discourse_publish_category',
			__( 'Default Discourse Category', 'wp-discourse' ),
			array(
				$this,
				'publish_category_input',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_display_subcategories',
			__( 'Display Subcategories', 'wp-discourse' ),
			array(
				$this,
				'display_subcategories',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_publish_category_update',
			__( 'Force Category Update', 'wp-discourse' ),
			array(
				$this,
				'publish_category_input_update',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_allow_tags',
			__( 'Allow Tags', 'wp-discourse' ),
			array(
				$this,
				'allow_tags_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_max_tags',
			__( 'Maximum Number of Tags', 'wp-discourse' ),
			array(
				$this,
				'max_tags_input',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_publish_as_unlisted',
			__( 'Publish as Unlisted Topics', 'wp-discourse' ),
			array(
				$this,
				'publish_as_unlisted_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_full_post_content',
			__( 'Use Full Post Content', 'wp-discourse' ),
			array(
				$this,
				'full_post_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_custom_excerpt_length',
			__( 'Custom Excerpt Length', 'wp-discourse' ),
			array(
				$this,
				'custom_excerpt_length',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_add_featured_link',
			__( 'Add Featured Links', 'wp-discourse' ),
			array(
				$this,
				'add_featured_link_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

			add_settings_field(
				'discourse_auto_publish',
				__( 'Auto Publish', 'wp-discourse' ),
				array(
					$this,
					'auto_publish_checkbox',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);

		add_settings_field(
			'discourse_force_publish',
			__( 'Force Publish', 'wp-discourse' ),
			array(
				$this,
				'force_publish_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_force_publish_max_age',
			__( 'Force Publish Max Age', 'wp-discourse' ),
			array(
				$this,
				'force_publish_max_age_input',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_publish_failure_notice',
			__( 'Send Email Notification on Publish Failure', 'wp-discourse' ),
			array(
				$this,
				'publish_failure_notice_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_publish_failure_email_address',
			__( 'Email Address for Failure Notification', 'wp-discourse' ),
			array(
				$this,
				'publish_failure_email_address',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_auto_track',
			__( 'Auto Track Published Topics', 'wp-discourse' ),
			array(
				$this,
				'auto_track_checkbox',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		add_settings_field(
			'discourse_allowed_post_types',
			__( 'Post Types to Publish', 'wp-discourse' ),
			array(
				$this,
				'post_types_select',
			),
			'discourse_publish',
			'discourse_publishing_settings_section'
		);

		if ( version_compare( get_bloginfo( 'version' ), '5.6', '>=' ) ) {
			add_settings_field(
				'discourse_exclude_tags',
				__( 'Exclude Posts By Tag', 'wp-discourse' ),
				array(
					$this,
					'tags_select',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);
		}

		// These options should be set for the whole network when multi-site support is enabled.
		if ( ! $this->use_network_publish_settings ) {
			add_settings_field(
				'discourse_hide_name_field',
				__( 'Do Not Display Discourse Name Field', 'wp-discourse' ),
				array(
					$this,
					'hide_discourse_name_field_checkbox',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);

			// Todo: add this setting to network-options.php.
			add_settings_field(
				'discourse-username_editable',
				__( 'Discourse Username Editable', 'wp-discourse' ),
				array(
					$this,
					'discourse_username_editable_checkbox',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);

			add_settings_field(
				'discourse_direct_db_publication_flags',
				__( 'Direct Database Publication Flags', 'wp-discourse' ),
				array(
					$this,
					'direct_db_publication_flags',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);

			add_settings_field(
				'discourse_verbose_publication_logs',
				__( 'Verbose Publication Logs', 'wp-discourse' ),
				array(
					$this,
					'verbose_publication_logs',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);

			add_settings_field(
				'discourse_single_user_api_key_publication',
				__( 'Single User API Key Publication', 'wp-discourse' ),
				array(
					$this,
					'single_user_api_key_publication',
				),
				'discourse_publish',
				'discourse_publishing_settings_section'
			);
		}

		register_setting(
			'discourse_publish',
			'discourse_publish',
			array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Outputs markup for the display-subcategories checkbox.
	 */
	public function display_subcategories() {
		$this->form_helper->checkbox_input(
			'display-subcategories',
			'discourse_publish',
			__( 'Include subcategories in the list of available categories.', 'wp-discourse' ),
			__( "You need to select and save both this setting and the 'Force Category Update' setting before subcategories will be available in the category list.", 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the publish-category input.
	 */
	public function publish_category_input() {
		$this->form_helper->category_select(
			'publish-category',
			'discourse_publish',
			__(
				"The default category in which
		your posts will be published on Discourse. (This can be changed in the 'Publish to Discourse' meta-box when you create a post.)",
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the allow-tags checkbox.
	 */
	public function allow_tags_checkbox() {
		$this->form_helper->checkbox_input(
			'allow-tags',
			'discourse_publish',
			__( 'Allow post authors to add tags to Discourse topic.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the max-tags input.
	 */
	public function max_tags_input() {
		$this->form_helper->input( 'max-tags', 'discourse_publish', __( 'The maximum number of tags to allow.', 'wp-discourse' ), 'number', 0 );
	}

	/**
	 * Outputs markup for publish-as-unlisted checkbox.
	 */
	public function publish_as_unlisted_checkbox() {
		$this->form_helper->checkbox_input(
			'publish-as-unlisted',
			'discourse_publish',
			__( 'Publish posts as unlisted Discourse topics.', 'wp-discourse' ),
			__( 'If you have enabled the Sync Comment Data webhook, topics will be listed when they receive a comment.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the publish-category-update input.
	 */
	public function publish_category_input_update() {
		// Only set the force_update option for a single request.
		$discourse_publish                            = get_option( 'discourse_publish' );
		$discourse_publish['publish-category-update'] = 0;
		update_option( 'discourse_publish', $discourse_publish );

		$this->form_helper->checkbox_input(
			'publish-category-update',
			'discourse_publish',
			__( 'Update the discourse publish category list.', 'wp-discourse' ),
			__(
				"Check this box if you've added new categories to your forum and would like them to be available on WordPress. The check box
		will be reset to 'unchecked' after a single request.",
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the use-full-post checkbox.
	 */
	public function full_post_checkbox() {
		$discourse_admin_posting_url = isset( $this->options['url'] ) && ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/site_settings/category/posting' : null;
		if ( $discourse_admin_posting_url ) {
			$discourse_admin_posting_link = '<a href="' . esc_url_raw( $discourse_admin_posting_url ) . '" target="_blank" rel="noreferrer noopener">' . esc_url( $discourse_admin_posting_url ) . '</a>.';
			$description                  = __(
				"<strong>Note:</strong> to keep the 'Show Full Post' button
            from appearing under your post on Discourse, you must unselect the 'embed truncate' setting on Discourse.
			This setting is found at ",
				'wp-discourse'
			) . $discourse_admin_posting_link;
		} else {
			$description = __(
				"<strong>Note:</strong> to keep the 'Show Full Post' button from appearing under your post on Discourse, you must uncheck the 'embed truncate' setting on Discourse.
			This setting is found at http://discourse.example.com/admin/site_settings/category/posting.",
				'wp-discourse'
			);
		}

		$this->form_helper->checkbox_input( 'full-post-content', 'discourse_publish', __( 'Publish the full post to Discourse, rather than an excerpt.', 'wp-discourse' ), $description );
	}

	/**
	 * Outputs markup for the custom-excerpt-length input.
	 */
	public function custom_excerpt_length() {
		$description = __(
			'Custom excerpt length in words. If you set an excerpt in the new-post excerpt
        metabox, that excerpt will be given priority over the length set here.',
			'wp-discourse'
		);
		$this->form_helper->input( 'custom-excerpt-length', 'discourse_publish', $description, 'number', 0 );
	}

	/**
	 * Outputs markup for add-featired-link input.
	 */
	public function add_featured_link_checkbox() {
		$this->form_helper->checkbox_input(
			'add-featured-link',
			'discourse_publish',
			__(
				'Adds a link to the WordPress post
	    to the Discourse topic list and topic title.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the auto-publish checkbox.
	 */
	public function auto_publish_checkbox() {
		$this->form_helper->checkbox_input(
			'auto-publish',
			'discourse_publish',
			__( 'Mark all new posts to be published to Discourse.', 'wp-discourse' ),
			__( 'This can be overridden in the Discourse Sidebar before you publish a post.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for force-publish checkbox.
	 */
	public function force_publish_checkbox() {
		$this->form_helper->checkbox_input(
			'force-publish',
			'discourse_publish',
			__( 'Automatically publish all new posts and updates. Posts will be published to the Default Discourse Category.', 'wp-discourse' ),
			__( '<strong>This setting is only applied when the Block Editor is used to publish posts.</strong>', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the force-publish-max-age input.
	 */
	public function force_publish_max_age_input() {
		$this->form_helper->input(
			'force-publish-max-age',
			'discourse_publish',
			__(
				"Max post age in days for force published posts. Older posts will not be published to Discourse.
			Only applied if 'Force Publish' is enabled. Leave blank or set to 0 to allow all posts to be force published.",
				'wp-discourse'
			),
			'number',
			0
		);
	}

	/**
	 * Outputs markup for the publish-failure-notice checkbox.
	 */
	public function publish_failure_notice_checkbox() {
		$this->form_helper->checkbox_input(
			'publish-failure-notice',
			'discourse_publish',
			__( 'Send an email notification if publishing to Discourse fails.', 'wp-discourse' ),
			__(
				"If the 'auto publish' option is selected, this will send a notification for any posts that fail to publish to Discourse. If that setting is not enabled, it
            will only send a notification if an error is returned from Discourse.",
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the publish-failure-email-address checkbox.
	 */
	public function publish_failure_email_address() {
		$this->form_helper->input( 'publish-failure-email', 'discourse_publish', __( "Email address to notify on publishing failure (defaults to the site's admin email address.)", 'wp-discourse' ), 'email' );
	}

	/**
	 * Outputs markup for the auto-track checkbox.
	 */
	public function auto_track_checkbox() {
		$this->form_helper->checkbox_input( 'auto-track', 'discourse_publish', __( 'Author automatically tracks their published Discourse topics.', 'wp-discourse' ) );
	}

	/**
	 * Outputs markup for hide-discourse-name-field checkbox.
	 */
	public function hide_discourse_name_field_checkbox() {
		$this->form_helper->checkbox_input(
			'hide-discourse-name-field',
			'discourse_publish',
			__(
				'Removes the Discourse Name field
	    from the WordPress user profile page.',
				'wp-discourse'
			),
			__(
				'The username field is always visible to site admins.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the discourse-username-editable checkbox.
	 */
	public function discourse_username_editable_checkbox() {
		$this->form_helper->checkbox_input(
			'discourse-username-editable',
			'discourse_publish',
			__(
				'Allow users to edit their Discourse username on their WordPress profile page.',
				'wp-discourse'
			),
			__(
				"The Discourse username can always be edited by site admins from the user's profile page.",
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the post-types select input.
	 */
	public function post_types_select() {
		$this->form_helper->post_type_select_input(
			'allowed_post_types',
			$this->form_helper->post_types_to_publish( array( 'attachment' ) ),
			__( 'Hold the <strong>control</strong> button (Windows) or the <strong>command</strong> button (Mac) to select multiple post-types.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the tags select input.
	 */
	public function tags_select() {
		$this->form_helper->tags_select_input(
			'exclude_tags',
			'discourse_publish',
			__( 'Do not auto-publish posts to Discourse if they have one of these tags.', 'wp-discourse' )
		);
	}

	/**
	 * Outputs markup for the discourse_direct_db_publication_meta checkbox.
	 */
	public function direct_db_publication_flags() {
		$this->form_helper->checkbox_input(
			'direct-db-publication-flags',
			'discourse_publish',
			__(
				'Use direct database calls for flags that control publication to discourse (EXPERIMENTAL).',
				'wp-discourse'
			),
			__(
				'Potentially prevents concurrency issues arising from object cache usage.',
				'wp-discourse'
			)
		);
	}

	/**
	 * Outputs markup for the discourse_verbose_publication_logs checkbox.
	 */
	public function verbose_publication_logs() {
		$this->form_helper->checkbox_input(
			'verbose-publication-logs',
			'discourse_publish',
			__(
				'Enable verbose logs for publication.',
				'wp-discourse'
			),
			__( 'Will log successful publications as well as errors.', 'wp-discourse' ) . ' View logs in the <a href="?page=wp_discourse_options&tab=log_viewer">' . __( 'Log Viewer', 'wp-discourse' ) . '</a>.'
		);
	}

	/**
	 * Outputs markup for the discourse_direct_discourse_user_publication checkbox.
	 */
	public function single_user_api_key_publication() {
		$this->form_helper->checkbox_input(
			'single-user-api-key-publication',
			'discourse_publish',
			__(
				"Enable if you're using a Single User API Key",
				'wp-discourse'
			),
			__( 'This allows you to publish to Discourse using the Discourse Username of the post author while using a Single User API Key.', 'wp-discourse' )
		);
	}

	/**
	 * Details for the 'publishing_options' tab.
	 */
	public function publishing_settings_tab_details() {
		$setup_howto_url    = 'https://meta.discourse.org/t/wp-discourse-plugin-installation-and-setup/50752';
		$discourse_meta_url = 'https://meta.discourse.org/';
		?>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'This section is for configuring how the plugin publishes posts to Discourse.', 'wp-discourse' ); ?>
			</em>
		</p>
		<p class="wpdc-options-documentation">
			<em>
				<?php esc_html_e( 'For detailed instructions, see the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $setup_howto_url ); ?>"
				   target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'WP Discourse plugin installation and setup', 'wp-discourse' ); ?></a>
				<?php esc_html_e( 'topic on the ', 'wp-discourse' ); ?>
				<a href="<?php echo esc_url( $discourse_meta_url ); ?>" target="_blank" rel="noreferrer noopener">Discourse Meta</a>
				<?php esc_html_e( 'forum.', 'wp-discourse' ); ?>
			</em>
		</p>
		<?php
	}
}
