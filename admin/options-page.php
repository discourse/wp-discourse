<?php
/**
 * Options Page.
 *
 * @package WordPress
 */

namespace WPDiscourse\Admin;

/**
 * Class OptionsPage
 */
class OptionsPage {

	/**
	 * Used for containing a single instance of the OptionsPage class throughout a request.
	 *
	 * @access protected
	 * @var null|OptionsPage
	 */
	protected static $instance;

	/**
	 * Gets an instance of the OptionsPage class.
	 *
	 * @return OptionsPage
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * OptionsPage constructor.
	 */
	protected function __construct() {
		// Empty constructor.
	}

	/**
	 * Displays the options options page and options page tabs.
	 *
	 * @param string      $active_tab The current tab, used if `$_GET['tab']` is not set.
	 * @param null|string $parent_tab An optional parent tab, useful for plugins that add a second-level menu.
	 * @param bool        $form Whether or not to display the form on the page.
	 */
	public function display( $active_tab = '', $parent_tab = null, $form = true ) {
		?>
		<div class="wrap discourse-options-page-wrap">
			<h2>
				<img
						src="<?php echo esc_attr( WPDISCOURSE_LOGO ); ?>"
						alt="Discourse logo" class="discourse-logo">
				<?php esc_html_e( 'WP Discourse', 'wp-discourse' ); ?>
			</h2>
			<?php settings_errors(); ?>

			<?php
			if ( isset( $_GET['tab'] ) ) { // Input var okay.
				$tab = sanitize_key( wp_unslash( $_GET['tab'] ) ); // Input var okay.
			} elseif ( $active_tab ) {
				$tab = $active_tab;
			} else {
				$tab = 'connection_options';
			}

			if ( isset( $_GET['parent_tab'] ) ) { // Input var okay.
				$parent = sanitize_key( wp_unslash( $_GET['parent_tab'] ) ); // Input var okay.
			} else {
				$parent = $parent_tab;
			}
			?>

			<h2 class="nav-tab-wrapper nav-tab-first-level">
				<a href="?page=wp_discourse_options&tab=connection_options"
				   class="nav-tab <?php echo 'connection_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Connection', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=publishing_options"
				   class="nav-tab <?php echo 'publishing_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Publishing', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=commenting_options"
				   class="nav-tab <?php echo 'commenting_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Commenting', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=text_content_options"
				   class="nav-tab <?php echo 'text_content_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Text Content', 'wp-discourse' ); ?>
				</a>
				<a href="?page=wp_discourse_options&tab=webhook_options"
				   class="nav-tab <?php echo 'webhook_options' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Webhooks', 'wp-discourse' ); ?>
				</a>

				<?php $sso_active = 'sso_options' === $tab || 'sso_options' === $parent; ?>

				<a href="?page=wp_discourse_options&tab=sso_options"
				   class="nav-tab <?php echo $sso_active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'DiscourseConnect', 'wp-discourse' ); ?>
				</a>

				<a href="?page=wp_discourse_options&tab=log_viewer"
				   class="nav-tab <?php echo 'log_viewer' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'wp-discourse' ); ?>
				</a>

				<?php
				/**
				 * Can be used for adding tabs.
				 *
				 * @param string $tab The current tab.
				 * @param null|string The parent tab.
				 */
				do_action( 'wpdc_options_page_append_settings_tabs', $tab, $parent );
				?>

			</h2>

			<?php
			/**
			 * Called after the settings tabs.
			 *
			 * @param string $tab The current tab.
			 * @param null|string $parent The parent tab.
			 */
			do_action( 'wpdc_options_page_after_settings_tabs', $tab, $parent );
			?>

			<?php if ( $form ) : ?>

				<form action="options.php" method="post" class="wp-discourse-options-form">
					<?php
					if ( 'connection_options' === $tab ) {
						settings_fields( 'discourse_connect' );
						do_settings_sections( 'discourse_connect' );
					}

					if ( 'publishing_options' === $tab ) {
						settings_fields( 'discourse_publish' );
						do_settings_sections( 'discourse_publish' );
					}

					if ( 'commenting_options' === $tab ) {
						settings_fields( 'discourse_comment' );
						do_settings_sections( 'discourse_comment' );
					}

					if ( 'text_content_options' === $tab ) {
						settings_fields( 'discourse_configurable_text' );
						do_settings_sections( 'discourse_configurable_text' );
					}

					if ( 'webhook_options' === $tab ) {
						settings_fields( 'discourse_webhook' );
						do_settings_sections( 'discourse_webhook' );
					}

					if ( 'log_viewer' === $tab ) {
						settings_fields( 'discourse_logs' );
						do_settings_sections( 'discourse_logs' );
					}

					do_action( 'wpdc_options_page_after_tab_switch', $tab );

					$multisite_configuration = get_site_option( 'wpdc_multisite_configuration' );
					$hide_submit_button      = ( is_multisite() &&
										  ( 'connection_options' === $tab || 'webhook_options' === $tab || 'sso_options' === $tab || 'sso_common' === $tab ) &&
										  ! empty( $multisite_configuration ) );

					if ( ! $hide_submit_button ) {
						submit_button( 'Save Options', 'primary', 'discourse_save_options', false );
					}
					?>
				</form>
				<?php
				/**
				 * Called after the setting-page form.
				 *
				 * @param string $tab The active tab.
				 *
				 * @hooked ConfigurableTextSettings::reset_options_form - 10
				 */
				do_action( 'wpdc_options_page_after_form', $tab );
				?>
			<?php endif; ?>

		</div>
		<?php
	}
}
