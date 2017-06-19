<?php
/**
 * Handles Discourse User Synchronization.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\DiscourseUser;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseUser
 */
class DiscourseUser {

	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'user_contactmethods', array( $this, 'extend_user_profile' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Adds 'discourse_username' to the user_contactmethods array.
	 *
	 * @param array $fields The array of contact methods.
	 *
	 * @return mixed
	 */
	public function extend_user_profile( $fields ) {
		if ( ! empty( $this->options['hide-discourse-name-field'] ) || ! empty( $this->options['username-as-discourse-name'])) {

			return $fields;
		} else {
			$fields['discourse_username'] = 'Discourse Username';
		}

		return $fields;
	}
}