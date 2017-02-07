<?php
/**
 * Connection Settings
 */

namespace WPDiscourse\ConnectionSettings;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class ConnectionSettings {
	protected $options;

	protected $utilities;

	public function __construct() {

		// Todo: Is this the right action?
		add_action( 'admin_init', array( $this, 'admin_init'));
	}

	public function admin_init() {
		$this->options = DiscourseUtilities::get_options();
	}
}