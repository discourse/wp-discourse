<?php

require_once( __DIR__ . '/../../lib/utilities.php' );
use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class TestUtilities extends WP_UnitTestCase {

	function test_avatar_substitutes_size_into_template() {
		$template = 'http://forum.example.com/user_avatar/scossar/{size}/1_1.png';

		$this->assertEquals( 'http://forum.example.com/user_avatar/scossar/60/1_1.png',
			DiscourseUtilities::avatar( $template, 60 ) );
	}

	function test_homepage_returns_discourse_users_url() {
		$post           = new \stdClass();
		$post->username = 'scossar';

		$this->assertEquals( 'http://forum.example.com/users/scossar',
			DiscourseUtilities::homepage( 'http://forum.example.com', $post ) );
	}

	function test_convert_relative_img_src_to_absolute_when_supplied_with_absolute_src() {
		$content = '<img src="http://example.com/uploads/example.png" />';

		$this->assertEquals( $content, DiscourseUtilities::convert_relative_img_src_to_absolute( 'http://example.com', $content ) );
	}

	function test_convert_relative_img_src_to_absolute_when_supplied_with_relative_src() {
		$content = '<img src="/uploads/example.png" />';

		$this->assertEquals( '<img src="http://example.com/uploads/example.png" />', DiscourseUtilities::convert_relative_img_src_to_absolute( 'http://example.com', $content ) );
	}
	
	function test_get_discourse_categories() {
		// Hmmm.
		$this->markTestIncomplete(
			'This will be difficult to test as it is written.'
		);
	}



}