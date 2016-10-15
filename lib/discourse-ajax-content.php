<?php
/**
 * Syncs and displays Discourse content through ajax calls.
 */

namespace WPDiscourse\DiscourseAjaxContent;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseAjaxContent {
	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

}