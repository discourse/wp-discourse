<?php

require_once( __DIR__ . '/../../lib/utilities.php' );
use WPDiscourse\Utilities\Utilities as DiscourseUtilities;
use phpmock\phpunit\PHPMock;

class TestUtilities extends \PHPUnit_Framework_TestCase {
	use PHPMock;

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

	function test_get_discourse_categories_returns_cached_categories_when_force_update_false_and_there_are_cached_categories() {

		$options = array(
			'api-key'                 => 'thisisatest',
			'publish-username'        => 'system',
			'url'                     => 'http://forum.example.com',
			'publish-category-update' => 0,
		);
		update_option( 'discourse', $options );

		$categories = array(
			'category one',
			'category two',
			'category three',
		);

		$get_transient = $this->getFunctionMock( 'WPDiscourse\Utilities', 'get_transient' );
		$get_transient->expects( $this->once() )
		              ->with( 'discourse_settings_categories_cache' )
		              ->willReturn( $categories );

		$this->assertEquals( $categories, DiscourseUtilities::get_discourse_categories() );
	}

	function test_discourse_categories_returns_cached_categories_when_remote_returns_an_error() {
		$options = array(
			'api-key'                 => 'thisisatest',
			'publish-username'        => 'system',
			'url'                     => 'http://forum.example.com',
			'publish-category-update' => 1,
		);
		update_option( 'discourse', $options );

		$categories = array(
			'category one',
			'category two',
			'category three',
		);

		$get_transient = $this->getFunctionMock( 'WPDiscourse\Utilities', 'get_transient' );
		$get_transient->expects( $this->once() )
		              ->with( 'discourse_settings_categories_cache' )
		              ->willReturn( $categories );

		$wp_remote_get = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_get' );
		$wp_remote_get->expects( $this->once() )
		              ->with( $this->anything() )
		              ->willReturn( new \WP_Error );

		$this->assertEquals( $categories, DiscourseUtilities::get_discourse_categories() );
	}

	public function test_get_discourse_categories_sets_transient() {
		$options = array(
			'api-key'                 => 'thisisatest',
			'publish-username'        => 'system',
			'url'                     => 'http://forum.example.com',
			'publish-category-update' => 1,
		);
		update_option( 'discourse', $options );

		$response = array(
			'categories' => array(
				array(
					'id'   => 1,
					'name' => 'category one',
				),
				array(
					'id'   => 2,
					'name' => 'category two',
				),
				array(
					'id'   => 3,
					'name' => 'category three',
				),
			),
		);

		// Mocks getting the site.json from Discourse.
		$wp_remote_get = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_get' );
		$wp_remote_get->expects( $this->once() )
		              ->with( $this->anything() );

		// Mocks passing validation.
		$wp_remote_retrieve_response_code = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_retrieve_response_code' );
		$wp_remote_retrieve_response_code->expects( $this->any() )
		                                 ->with( $this->anything() )
		                                 ->willReturn( 200 );

		// Mocks retrieving the body.
		$wp_remote_retrieve_body = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_retrieve_body' );
		$wp_remote_retrieve_body->expects( $this->any() )
		                        ->with( $this->anything() );

		// Mocks json_decode. Returns the $response array created above. What is being
		// tested runs starts here.
		$json_decode = $this->getFunctionMock( 'WPDiscourse\Utilities', 'json_decode' );
		$json_decode->expects( $this->once() )
		            ->with( $this->anything(), true )
		            ->willReturn( $response );

		$set_transient = $this->getFunctionMock( 'WPDiscourse\Utilities', 'set_transient' );
		$set_transient->expects( $this->once() )
		              ->with(
			              'discourse_settings_categories_cache',
			              $response['categories'],
			              HOUR_IN_SECONDS
		              );

		DiscourseUtilities::get_discourse_categories();
	}

	public function test_get_discourse_categories_removes_subcategories_when_display_subcategories_is_not_set() {
		$options = array(
			'api-key'                 => 'thisisatest',
			'publish-username'        => 'system',
			'url'                     => 'http://forum.example.com',
			'publish-category-update' => 1,
			'display-subcategories' => 0
		);
		update_option( 'discourse', $options );

		$response = array(
			'categories' => array(
				array(
					'id'   => 1,
					'name' => 'category one',
				),
				array(
					'id'   => 2,
					'name' => 'category two',
					'parent_category_id' => 1,
				),
				array(
					'id'   => 3,
					'name' => 'category three',
				),
			),
		);

		// Mocks getting the site.json from Discourse.
		$wp_remote_get = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_get' );
		$wp_remote_get->expects( $this->once() )
		              ->with( $this->anything() );

		// Mocks passing validation.
		$wp_remote_retrieve_response_code = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_retrieve_response_code' );
		$wp_remote_retrieve_response_code->expects( $this->any() )
		                                 ->with( $this->anything() )
		                                 ->willReturn( 200 );

		// Mocks retrieving the body.
		$wp_remote_retrieve_body = $this->getFunctionMock( 'WPDiscourse\Utilities', 'wp_remote_retrieve_body' );
		$wp_remote_retrieve_body->expects( $this->any() )
		                        ->with( $this->anything() );

		// Mocks json_decode. Returns the $response array created above. What is being
		// tested runs starts here.
		$json_decode = $this->getFunctionMock( 'WPDiscourse\Utilities', 'json_decode' );
		$json_decode->expects( $this->once() )
		            ->with( $this->anything(), true )
		            ->willReturn( $response );

		// The subcategory is removed.
		$this->assertEquals( 2, count( DiscourseUtilities::get_discourse_categories() ) );
	}
}