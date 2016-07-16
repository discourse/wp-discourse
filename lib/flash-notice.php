<?php

namespace WPDiscourse\FlashNotice;

class FlashNotice {

	public function __construct() {
		add_action( 'init', array( $this, 'start_session' ) );
		add_action( 'wp_logout', array( $this, 'end_session' ) );
		add_action( 'wp_login', array( $this, 'end_session' ) );
		add_action( 'wp_footer', array( $this, 'display_flash_notice' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'discourse_flash_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'discourse_flash_notice_js' ) );
	}

	public function discourse_flash_styles() {
		wp_register_style( 'discourse_flash_styles', plugins_url( '/../css/discourse-flash-styles.css', __FILE__ ) );
		wp_enqueue_style( 'discourse_flash_styles' );
	}

	public function discourse_flash_notice_js() {
		wp_register_script( 'discourse_flash_notice_js', plugins_url( '/../js/discourse-flash-notice.js', __FILE__ ), array( 'jquery' ), get_option( 'discourse_version' ), true);
		wp_enqueue_script( 'discourse_flash_notice_js' );
	}

	public function start_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}

	public function end_session() {
		session_destroy();
	}

	public static function set_flash_notice( $notice_type, $notice ) {
		$_SESSION['discourse_flash_notice'][$notice_type] =  $notice;
	}


	public function display_flash_notice() {
		if ( isset( $_SESSION['discourse_flash_notice'] ) ) {
			$notices = $_SESSION['discourse_flash_notice'];
			$output = '<div class="discourse-flash-notice-container"><div class="discourse-flash-notices">';

			foreach ( $notices as $notice_type => $notice ) {
				$output .= '<div class="discourse-flash-notice ' . $notice_type . '"><p>' . $notice . '</p><a href="#" class="discourse-close-flash-notice">x</a></div>';
			}

			$output .= '</div></div>';

			unset( $_SESSION['discourse_flash_notice'] );
			echo $output;
		}
	}

}