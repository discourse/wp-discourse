<?php

namespace WPDiscourse\OptionInput;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class OptionInput {
	protected $options;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $description The description of the settings field.
	 * @param null|string $type The type of input ('number', 'url', etc).
	 * @param null|int $min The min value (applied to number inputs).
	 * @param null|int $max The max value (applies to number inputs).
	 */
	public function text_input( $option, $option_group, $description, $type = null, $min = null, $max = null ) {
		$options = $this->options;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		if ( isset( $options[ $option ] ) ) {
			$value = $options[ $option ];
		} else {
			$value = '';
		}

		?>
		<input id='discourse-<?php echo esc_attr( $option ); ?>'
		       name='<?php echo esc_attr( $this->option_name( $option, $option_group ) ); ?>'
		       type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
			<?php if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
			} ?>
			<?php if ( isset( $max ) ) {
				echo 'max="' . esc_attr( $max ) . '"';
			} ?>
			   value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"/>
		<p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Outputs the markup for an input box, defaults to outputting a text input, but
	 * can be used for other types.
	 *
	 * This function is a temporary workaround for adding a default value to a text input when
	 * no options is set. Eventually, the $default parameter can be added to the text_input function.
	 *
	 * @param string $option The name of the option.
	 * @param string $option_group The option group for the field to be saved to.
	 * @param string $description The description of the settings field.
	 * @param string $default The default value to use when the option isn't set.
	 * @param null|string $type The type of input ('number', 'url', etc).
	 * @param null|ing $min The min value (applied to number inputs).
	 * @param null|int $max The max value (applies to number inputs).
	 */
	public function empty_option_text_input( $option, $option_group, $description, $default = '', $type = null, $min = null, $max = null ) {
		$options = $this->options;
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		if ( ! empty( $options[ $option ] ) ) {
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
			<?php if ( isset( $min ) ) {
				echo 'min="' . esc_attr( $min ) . '"';
			} ?>
			<?php if ( isset( $max ) ) {
				echo 'max="' . esc_attr( $max ) . '"';
			} ?>
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
		);
		if ( ! empty( $options[ $option ] ) && 1 === intval( $options[ $option ] ) ) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		?>
		<label>
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
	 * @param array $post_types An array of available post types.
	 * @param string $description The description of the settings field.
	 */
	public function post_type_select_input( $option, $post_types, $description = '' ) {
		$options = $this->options;
		$allowed = array(
			'strong' => array(),
		);

		echo "<select multiple id='discourse-allowed-post-types' class='discourse-allowed-types' name='discourse_publish[allowed_post_types][]'>";

		foreach ( $post_types as $post_type ) {

			if ( array_key_exists( $option, $options ) and in_array( $post_type, $options[ $option ], true ) ) {
				$value = 'selected';
			} else {
				$value = '';
			}

			echo '<option ' . esc_attr( $value ) . " value='" . esc_attr( $post_type ) . "'>" . esc_html( $post_type ) . '</option>';
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

		$categories = DiscourseUtilities::get_discourse_categories();

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
	 * @param array $group The array of items to be selected.
	 * @param int $selected The value of the selected option.
	 * @param string $description The description of the option.
	 */
	public function option_input( $option, $option_name, $group, $selected, $description ) {
		echo '<select id="discourse-' . esc_attr( $option ) . '" name="' . esc_attr( $option_name ) . '">';

		foreach ( $group as $item ) {
			printf( '<option value="%s"%s>%s</option>',
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
	 * @return mixed|void
	 */
	public function post_types_to_publish( $excluded_types = array() ) {
		$post_types = get_post_types( array( 'public' => true ) );
		foreach ( $excluded_types as $excluded ) {
			unset( $post_types[ $excluded ] );
		}

		return apply_filters( 'discourse_post_types_to_publish', $post_types );
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