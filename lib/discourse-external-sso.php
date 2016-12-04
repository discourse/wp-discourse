<?php
/**
 * Allows for Single Sign On between WordPress and Discourse with Discourse as SSO Provider
 *
 * @package WPDiscourse\DiscourseSSO
 */

namespace WPDiscourse\DiscourseSSO;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseExternalSSO
{
	private $sso_meta_key = 'discourse_sso_user_id';

	public function __construct()
	{
		add_action('parse_request', array($this, 'parse_request'), 5);
	}

	/**
	 * Parse Reuqst Hook
	 */
	public function parse_request()
	{
		if (empty($_GET['sso']) || empty($_GET['sig'])) {
			return;
		}

		$this->options = DiscourseUtilities::get_options();

		if (!$this->is_valid_signatiure()) {
			return;
		}

		$this->disable_notification_on_user_update();
		$user_id = $this->get_user_id();

		if (is_wp_error($user_id)) {
			return;
		}

		$this->update_user($user_id);
		$this->auth_user($user_id);
	}

	/**
	 * Update WP user with discourse user data
	 *
	 * @param  int      $user_id the user ID
	 */
	private function update_user($user_id)
	{
		$query = $this->get_sso_response();

		$updated_user = array_merge([
			'ID' => $user_id,
			'user_login' => $query['username'],
			'user_email' => $query['email'],
			'user_nicename' => $query['name'],
			'display_name' => $query['name'],
			'first_name' => $query['name'],
		], $updated_user);

		$updated_user = apply_filters('discourse_as_sso_provider_updated_user', $updated_user, $query);

		wp_update_user($updated_user);

		update_user_meta($user_id, $this->sso_meta_key, $query['external_id']);
	}

	/**
	 * Set suth cookies
	 *
	 * @param  int    $user_id the user ID
	 */
	private function auth_user($user_id)
	{
		$query = $this->get_sso_response();

		wp_set_current_user($user_id, $query['username']);
		wp_set_auth_cookie($user_id);
		do_action('wp_login', $query['username']);
		wp_redirect(home_url('/'));
	}

	/**
	 * Disable built in notification on email/password changes.
	 */
	private function disable_notification_on_user_update()
	{
		add_filter('send_password_change_email', '__return_false');
		add_filter('send_email_change_email', '__return_false');
	}

	/**
	 * Get user id or create an user
	 *
	 * @return int      user id
	 */
	private function get_user_id()
	{
		if (is_user_logged_in()) {
			return get_current_user_id();
		} else {

			$user_query = new \WP_User_Query([
				'meta_key' => $this->sso_meta_key,
				'meta_value' => $this->get_sso_response('external_id'),
			]);

			$user_query_results = $user_query->get_results();

			if (empty($user_query_results)) {
				$user_password = wp_generate_password($length = 12, $include_standard_special_chars = true);
				return wp_create_user(
					$this->get_sso_response('username'),
					$user_password,
					$this->get_sso_response('email')
				);
			}

			return $user_query_results{0}->ID;
		}
	}

	/**
	 * Validates SSO signature
	 *
	 * @return boolean
	 */
	private function is_valid_signatiure()
	{
		$sso = urldecode($this->get_sso_response('raw'));
		return hash_hmac('sha256', $sso, $this->get_sso_secret()) == $this->get_sso_signature();
	}

	private function get_sso_signature()
	{
		return sanitize_text_field($_GET['sig']);
	}

	private function get_sso_secret()
	{
		return $this->options['sso-secret'];
	}

	/**
	 * Parse SSO response
	 *
	 * @method get_sso_response
	 *
	 * @param  string           $return_key
	 *
	 * @return string
	 */
	private function get_sso_response($return_key = null)
	{
		if (empty($_GET['sso'])) {
			return null;
		};

		if ($return_key == 'raw') {
			return $_GET['sso'];
		}

		$sso = urldecode(sanitize_text_field($_GET['sso']));

		$response = [];

		parse_str(base64_decode($sso), $response);
		$response = array_map('sanitize_text_field', $response);

		if (empty($response['external_id'])) {
			return null;
		}

		if (is_string($return_key) && isset($response[$return_key])) {
			return $response[$return_key];
		}

		return $response;
	}
}
