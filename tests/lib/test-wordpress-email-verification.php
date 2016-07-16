<?php

require_once( __DIR__ . '/../../lib/wordpress-email-verification.php' );

class TestWordPressEmailVerification extends WP_UnitTestCase {
	protected $email_verifier;
	protected $email_verification_key_name = 'discourse_email_verification_key';

	public function setUp() {
		$this->email_verifier = new \WPDiscourse\WordPressEmailVerification\WordPressEmailVerification( $this->email_verification_key_name, 'discourse' );
	}

	public function tearDown() {
        unset( $_POST['mail_key'] );
		unset( $_POST['verify_email_nonce'] );
	}

	public function test_flag_email_flags_all_users_after_registration() {
		$user_id = $this->factory->user->create();

		$this->assertFalse( $this->email_verifier->is_verified( $user_id ) );
	}

	public function test_verify_email_after_password_reset_removes_email_flag_when_sigs_match() {
		$email_verification_sig = time() . '_' . '1234';
		$_POST['mail_key']   = $email_verification_sig;
		$email_nonce = wp_create_nonce( 'verify_email' );
		$_POST['verify_email_nonce'] = $email_nonce;
		$user_id                = $this->factory->user->create();
		update_user_meta( $user_id, $this->email_verification_key_name, $email_verification_sig );
		$user   = get_userdata( $user_id );

		$this->email_verifier->verify_email_after_password_reset( $user );
		$this->assertTrue( $this->email_verifier->is_verified( $user_id ) );
	}

	public function test_verify_email_after_password_reset_doesnt_remove_flag_when_sigs_dont_match() {
		$email_verification_sig = time() . '_' . '1234';
		$_POST['mail_key']   = $email_verification_sig;
		$email_nonce = wp_create_nonce( 'verify_email' );
		$_POST['verify_email_nonce'] = $email_nonce;
		$user_id                = $this->factory->user->create();
		update_user_meta( $user_id, $this->email_verification_key_name, 'differentkey' );
		$user   = get_userdata( $user_id );

		$this->email_verifier->verify_email_after_password_reset( $user );
		$this->assertFalse( $this->email_verifier->is_verified( $user_id ) );
	}

	public function test_verify_email_after_login_calls_process_expired_sig_when_sig_is_expired() {
		$expired                = time() - ( 2 * HOUR_IN_SECONDS );
		$expired_verification_sig = $expired . '_' . '1234';
		$_POST['mail_key']      = $expired_verification_sig;
		$email_nonce = wp_create_nonce( 'verify_email' );
		$_POST['verify_email_nonce'] = $email_nonce;
		$user_id                = $this->factory->user->create();
		$user                   = get_userdata( $user_id );
		update_user_meta( $user_id, $this->email_verification_key_name, $expired_verification_sig );

		$wordpress_email_verification_mock = $this
			->getMockBuilder( '\WPDiscourse\WordPressEmailVerification\WordPressEmailVerification' )
			->setConstructorArgs( [ $this->email_verification_key_name, 'discourse' ] )
			->setMethods( [ 'process_expired_sig' ] )
			->getMock();

		$wordpress_email_verification_mock->expects( $this->once() )
		                                  ->method( 'process_expired_sig' )
		                                  ->with( $user_id );

		$wordpress_email_verification_mock->verify_email_after_login( $user->user_login, $user );
	}

	public function test_verify_email_after_login_calls_process_mismatched_sig_when_sigs_mismatched() {
		$time = time();
		$email_verification_sig = $time . '_' . '1234';
		$mismatched_sig = $time . '_' . '5678';
		$_POST['mail_key'] = $email_verification_sig;
		$email_nonce = wp_create_nonce( 'verify_email' );
		$_POST['verify_email_nonce'] = $email_nonce;
		$user_id = $this->factory->user->create();
		$user = get_userdata( $user_id );
		update_user_meta( $user_id, $this->email_verification_key_name, $mismatched_sig );

		$wordpress_email_verification_mock = $this
			->getMockBuilder( '\WPDiscourse\WordPressEmailVerification\WordPressEmailVerification' )
			->setConstructorArgs( [ $this->email_verification_key_name, 'discourse' ] )
			->setMethods( [ 'process_mismatched_sig' ] )
			->getMock();

		$wordpress_email_verification_mock->expects( $this->once() )
		                                  ->method( 'process_mismatched_sig' )
		                                  ->with( $user_id );

		$wordpress_email_verification_mock->verify_email_after_login( $user->user_login, $user );
	}

	public function test_verify_email_after_login_removes_flag_when_sigs_match() {
		$email_verification_sig = time() . '_' . '1234';
		$_POST['mail_key']      = $email_verification_sig;
		$email_nonce = wp_create_nonce( 'verify_email' );
		$_POST['verify_email_nonce'] = $email_nonce;
		$user_id                = $this->factory->user->create();
		$user                   = get_userdata( $user_id );
		update_user_meta( $user_id, $this->email_verification_key_name, $email_verification_sig );

		$this->email_verifier->verify_email_after_login( $user->user_login, $user );
		$this->assertTrue( $this->email_verifier->is_verified( $user_id ) );
	}
}