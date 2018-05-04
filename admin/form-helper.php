<?php
/**
 * Used for creating form elements.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Admin;

use WPDiscourse\Shared\PluginUtilities;

/**
 * Class FormHelper
 */
class FormHelper {
	use PluginUtilities;

	/**
	 * Used for containing a single instance of the FormHelper class throughout a request.
	 *
	 * @access protected
	 * @var FormHelper
	 */
	protected static $instance;

	/**
	 * Gives access to the plugin options.
	 *
	 * @access protected
	 * @var mixed|void
	 */
	protected $options;

	/**
	 * Gets an instance of the FormHelper class.
	 *
	 * @return FormHelper
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * FormHelper constructor.
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );
	}

	/**
	 * Sets the plugin options.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
	}

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string      $option The name of the option.
	 * @param string      $option_group The option group for the field to be saved to.
	 * @param string      $description The description of the settings field.
	 * @param null|string $type The type of input ('number', 'url', etc).
	 * @param null|int    $min The min value (applied to number inputs).
	 * @param null|int    $max The max value (applies to number inputs).
	 * @param null|string $default The default value of the input.
	 */
	public function input( $option, $option_group, $description, $type = null, $min = null, $max = null, $default = null ) {
		$options = $this->options;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		if ( ! empty( $options[ $option ] ) || ( isset( $options[ $option ] ) && 0 === $options[ $option ] ) ) {
			$value = $options[ $option ];
		} elseif ( ! empty( $default ) ) {
			$value = $default;
		} else {
			$value = '';
		}

		?>
		<input id='discourse-<?php echo esc_attr( $option ); ?>'
			   name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
			   type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
			<?php
			if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
			}
?>
			<?php
			if ( isset( $max ) ) {
				echo 'max="' . esc_attr( $max ) . '"';
			}
?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the markup for a checkbox input.
	 *
	 * @param string $option The option name.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $label The text for the label.
	 * @param string $description The description of the settings field.
	 */
	public function checkbox_input( $option, $option_group, $label = '', $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'a'      => array(
				'href'   => array(),
				'target' => array(),
			),
			'strong' => array(),
			'code'   => array(
				'class' => array(),
			),
		);
		if ( ! empty( $options[ $option ] ) && 1 === intval( $options[ $option ] ) ) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<label>
			<input name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
				   type='hidden'
				   value='0'/>
			<input id='discourse-<?php echo esc_attr( $option ); ?>'
				   name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
				   type='checkbox'
				   value='1' <?php echo esc_attr( $checked ); ?> />
			<?php echo wp_kses( $label, $allowed ); ?>
		</label>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the post-type select input.
	 *
	 * @param string $option Used to set the selected option.
	 * @param array  $post_types An array of available post types.
	 * @param string $description The description of the settings field.
	 */
	public function post_type_select_input( $option, $post_types, $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'strong' => array(),
		);

		echo "<select multiple id='discourse-allowed-post-types' class='discourse-allowed-types' name='discourse_publish[allowed_post_types][]'>";

		foreach ( $post_types as $post_type ) {

			if ( array_key_exists( $option, $options ) && in_array( $post_type, $options[ $option ], true ) ) {
				$value = 'selected';
			} else {
				$value = '';
			}

			echo '<option ' . esc_attr( $value ) . ' value="' . esc_attr( $post_type ) . '">' . esc_html( $post_type ) . '</option>';
		}

		echo '</select>';
		echo '<p class="description">' . wp_kses( $description, $allowed ) . '</p>';
	}

	/**
	 * Outputs the markup for the categories select input.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $description The description of the settings field.
	 */
	public function category_select( $option, $option_group, $description ) {
		$options = $this->options;

		$categories = $this->get_discourse_categories();

		if ( is_wp_error( $categories ) ) {
			esc_html_e( 'The Discourse category list will be available when you establish a connection with Discourse.', 'wp-discourse' );

			return;
		}

		$selected    = isset( $options['publish-category'] ) ? $options['publish-category'] : '';
		$option_name = $this->option_name( $option, $option_group );
		$this->option_input( $option, $option_name, $categories, $selected, $description );
	}

	/**
	 * Outputs the markup for an option input.
	 *
	 * @param string $option The name of the option to be saved.
	 * @param string $option_name Supplies the 'name' value for the select input.
	 * @param array  $group The array of items to be selected.
	 * @param int    $selected The value of the selected option.
	 * @param string $description The description of the option.
	 */
	public function option_input( $option, $option_name, $group, $selected, $description ) {
		echo '<select id="discourse-' . esc_attr( $option ) . '" name="' . esc_attr( $option_name ) . '">';

		foreach ( $group as $item ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $item['id'] ),
				selected( $selected, $item['id'], false ),
				esc_html( $item['name'] )
			);
		}

		echo '</select>';
		echo '<p class="description">' . esc_html( $description ) . '</p>';
	}


	/**
	 * Outputs the markup for a text area.
	 *
	 * @param string $option The name of the option.
	 * @param string $description The description of the settings field.
	 */
	public function text_area( $option, $description ) {
		$options = $this->options;

		if ( array_key_exists( $option, $options ) ) {
			$value = $options[ $option ];
		} else {
			$value = '';
		}

		?>
		<textarea cols=100 rows=6 id='discourse_<?php echo esc_attr( $option ); ?>'
				  name='<?php echo esc_attr( $option ); ?>'><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php

	}

	/**
	 * Returns the 'public' post-types minus the post-types in the 'excluded' array.
	 *
	 * @param array $excluded_types An array of post-types to exclude from publishing to Discourse.
	 *
	 * @return array
	 */
	public function post_types_to_publish( $excluded_types = array() ) {
		$post_types = get_post_types(
			array(
				'public' => true,
			)
		);
		foreach ( $excluded_types as $excluded ) {
			unset( $post_types[ $excluded ] );
		}

		return apply_filters( 'discourse_post_types_to_publish', $post_types );
	}

	/**
	 * The callback for validating the 'discourse' options.
	 *
	 * @param array $inputs The inputs to be validated.
	 *
	 * @return array
	 */
	public function validate_options( $inputs ) {
		$output = array();

		if ( ! empty( $inputs ) ) {
			foreach ( $inputs as $key => $input ) {
				$filter = 'wpdc_validate_' . str_replace( '-', '_', $key );

				if ( ! has_filter( $filter ) ) {
					// It's safe to log errors here. This should never have to be called on a production site.
					error_log( 'Missing validation filter: ' . $filter );
				}
				$output[ $key ] = apply_filters( $filter, $input );
			}
		}

		return $output;
	}

	/**
	 * Adds notices to indicate the connection status with Discourse.
	 *
	 * This method is called by the `load-{settings_page_hook}` action - see https://codex.wordpress.org/Plugin_API/Action_Reference/load-(page).
	 */
	public function connection_status_notice() {
		if ( ! empty( $_GET['tab'] ) ) { // Input var okay.
			$current_page = sanitize_key( wp_unslash( $_GET['tab'] ) ); // Input var okay.
		} elseif ( ! empty( $_GET['page'] ) ) { // Input var okay.
			$current_page = sanitize_key( wp_unslash( $_GET['page'] ) ); // Input var okay.
		} else {
			$current_page = null;
		}

		if ( $current_page && ( 'sso_provider' === $current_page ) ) {
			// Check if the user saving the options has an email address on Discourse.
			$current_user_email = wp_get_current_user()->user_email;
			$discourse_user     = $this->get_discourse_user_by_email( $current_user_email );
			if ( is_wp_error( $discourse_user ) || empty( $discourse_user->admin ) ) {
				add_action( 'admin_notices', array( $this, 'no_matching_discourse_user' ) );
			}
		}

		// Only check the connection status on the main settings tab.
		if ( $current_page && ( 'wp_discourse_options' === $current_page || 'connection_options' === $current_page ) ) {

			if ( ! $this->check_connection_status() ) {
				add_action( 'admin_notices', array( $this, 'disconnected' ) );

			} else {
				add_action( 'admin_notices', array( $this, 'connected' ) );
			}
		}
	}

	/**
	 * Outputs the markup for the no_matching_discourse_user notice.
	 */
	public function no_matching_discourse_user() {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
					<?php
					$current_user_email = wp_get_current_user()->user_email;
					$message            = sprintf(
						// translators: Discourse admin-email-mismatch message. Placeholder: The current user's email address.
						__(
							'There is no admin user on Discourse with the email address <strong>%s</strong>. If you have
                                             an existing Discourse admin account, before enabling SSO please ensure that your email
                                             addresses on Discourse and WordPress match. This is required for SSO login to an
                                             existing Discourse account.', 'wp-discourse'
						), esc_attr( $current_user_email )
					);

					$allowed = array(
						'strong' => array(),
					);

					echo wp_kses( $message, $allowed );
					?>
			</p>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the 'disconnected' notice that is displayed on the 'connection_options' tab.
	 */
	public function disconnected() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong>
				<?php
				esc_html_e(
					'You are not connected to Discourse. If you are setting up the plugin, this
                notice should go away after completing the form on this page.', 'wp-discourse'
				);
?>
</strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Outputs the markup for the 'connected' notice that is displayed on the 'connection_options' tab.
	 */
	public function connected() {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'You are connected to Discourse!', 'wp-discourse' ); ?></strong>
			</p>
		</div>
		<?php
	}

	/**
	 * Creates the full option name for the form `name` fields.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The group to save the option to, each option group is saved as an array in the `wp_options` table.
	 *
	 * @return string
	 */
	protected function option_name( $option, $option_group ) {
		return $option_group . '[' . esc_attr( $option ) . ']';
	}
}
