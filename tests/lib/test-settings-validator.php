<?php

require_once( __DIR__ . '/../../lib/settings-validator.php' );

class TestSettingsValidator extends WP_UnitTestCase {
	function setUp() {
		$this->validator = new \WPDiscourse\Validator\SettingsValidator();
	}

	// Validate URL.

	function test_validate_url_returns_empty_string_for_invalid_protocol() {
		$urls = array( 'htxtp://example.com', 'example.com', 'mailto://example.com' );

		foreach ( $urls as $url ) {
			$this->assertEquals( '', $this->validator->validate_url( $url ) );
		}
	}

	function test_validate_url_adds_settings_error_for_invalid_input() {
		$urls       = array( 'htxtp://example.com', 'http://', 'http://%xample.com' );

		foreach ( $urls as $url ) {
			$num_errors = count( get_settings_errors() );
			$this->validator->validate_url( $url );
			$new_errors = count( get_settings_errors() );
			$this->assertNotSame( $num_errors, $new_errors );
		}
	}

	function test_validate_url_returns_a_valid_url() {
		$urls = array( 'http://example.com', 'https://example.com' );

		foreach ( $urls as $url ) {
			$this->assertSame( $url, $this->validator->validate_url( $url ) );
		}
	}

	function test_validate_url_strips_trailing_slash() {
		$url = 'https://example.com/';

		$this->assertSame( 'https://example.com', $this->validator->validate_url( $url ) );
	}

	// Validate api key.

	function test_validate_api_key_sanitizes_input() {
		$api_key = 'thisisatest<script>alert("thisisatest");</script>';

		$this->assertSame( 'thisisatest', $this->validator->validate_api_key( $api_key ) );
	}

	function test_validate_api_key_adds_settings_error_for_invalid_input() {
		$api_key = 'this-is-a-test';
		$num_errors = count( get_settings_errors() );

		$this->validator->validate_api_key( $api_key );

		$new_errors = count( get_settings_errors() );

		$this->assertNotSame( $num_errors, $new_errors );
	}

	function test_validate_api_key_trims_valid_input() {
		$api_key = '1wdlkjsoiuelkj3r45 ';

		$this->assertSame( '1wdlkjsoiuelkj3r45', $this->validator->validate_api_key( $api_key ) );
	}

	// Validate publish category

	function test_validate_publish_category_returns_an_int() {
		$category = '1';

		$this->assertSame( 1, $this->validator->validate_publish_category( $category ) );
	}

	// Validate publish category update.

	function test_validate_publish_category_update_returns_1_for_valid() {
		$update = '1';

		$this->assertSame( 1, $this->validator->validate_publish_category_update( $update ) );
	}

	function test_validate_publish_category_update_returns_0_for_invalid() {
		$update = 'update';

		$this->assertSame( 0, $this->validator->validate_publish_category_update( $update ) );
	}

	// Validate max comments.

	function test_validate_max_comments_adds_settings_error_for_negative_numbers_when_use_discourse_comments_is_true() {
		$num_errors = count( get_settings_errors() );

		$this->validator->validate_use_discourse_comments( 1 );
		$this->validator->validate_max_comments( - 100 );

		$new_errors = count( get_settings_errors() );


		$this->assertNotSame( $num_errors, $new_errors );
	}

	function test_validate_max_comments_sanitizes_input_but_does_not_add_error_when_discourse_comments_not_true() {
		$num_errors = count( get_settings_errors() );

		$this->assertSame( $this->validator->validate_max_comments( 'one hundred' ), '' );

		$new_errors = count( get_settings_errors() );

		$this->assertSame( $num_errors, $new_errors );
	}

	// Validate sso secret.

	function test_validate_sso_secret_sanitizes_input_but_does_not_add_error_when_not_using_sso() {
		$num_errors = count( get_settings_errors() );

		$this->assertSame( $this->validator->validate_sso_secret( 'abcde<script>fg</script>' ), 'abcde' );

		$new_errors = count( get_settings_errors() );

		$this->assertSame( $num_errors, $new_errors );
	}

	function test_validate_sso_secret_adds_settings_error_when_sso_enabled() {
		$num_errors = count( get_settings_errors() );

		$this->validator->validate_enable_sso( 1 );

		$this->validator->validate_sso_secret( 'abc' );

		$new_errors = count( get_settings_errors() );

		$this->assertNotSame( $num_errors, $new_errors );
	}

	// Validate login path.

	function test_validate_login_path_must_begin_with_forward_slash() {
		$this->validator->validate_enable_sso( 1 );

		$num_errors = count( get_settings_errors() );

		$this->validator->validate_login_path( 'my-account' );

		$new_errors = count( get_settings_errors() );

		$this->assertNotSame( $num_errors, $new_errors );
	}

	function test_validate_login_path_accepts_valid_input() {
		$paths = array( '/login', '/login/path/', '/login-path' );
		$this->validator->validate_enable_sso( 1 );

		foreach ( $paths as $path ) {
			$num_errors = count( get_settings_errors() );
			$this->validator->validate_login_path( $path );
			$new_errors = count( get_settings_errors() );
			$this->assertSame( $num_errors, $new_errors );
		}
	}
}