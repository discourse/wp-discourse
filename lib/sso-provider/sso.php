<?php
/**
 * Single-sign-on for Discourse via PHP
 *
 * @link https://github.com/ArmedGuy/discourse_sso_php
 * @package WPDiscourse
 */

namespace WPDiscourse\SSO;

/**
 * Class Discourse_SSO
 */
class SSO {

	/**
	 * The SSO secret key.
	 *
	 * @access private
	 * @var string
	 */
	private $sso_secret;

	/**
	 * Discourse_SSO constructor.
	 *
	 * @param string $secret The SSO secret key.
	 */
	public function __construct( $secret ) {
		$this->sso_secret = $secret;
	}

	/**
	 * Validates the payload against the sig.
	 *
	 * @param string $payload A Base64 encoded string.
	 * @param string $sig HMAC-SHA256 of $sso_secret, $payload should be equal to $sig.
	 *
	 * @return bool
	 */
	public function validate( $payload, $sig ) {
		$payload = urldecode( $payload );
		if ( hash_hmac( 'sha256', $payload, $this->sso_secret ) === $sig ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets the nonce from the payload.
	 *
	 * @param string $payload A Base64 encoded string.
	 *
	 * @return mixed
	 * @throws \Exception Thrown when the nonce in not found in the payload.
	 */
	public function get_nonce( $payload ) {
		$payload = urldecode( $payload );
		$query   = array();
		parse_str( base64_decode( $payload ), $query );
		if ( isset( $query['nonce'] ) ) {
			return $query['nonce'];
		} else {
			throw new \Exception( 'Nonce not found in payload!' );
		}
	}

	/**
	 * Creates the sso-login query params that are sent to Discourse.
	 *
	 * @param array $params The array of parameters to send.
	 *
	 * @return string
	 * @throws \Exception Thrown when the required params aren't present.
	 */
	public function build_login_string( $params ) {
		if ( ! isset( $params['external_id'] ) ) {
			throw new \Exception( "Missing required parameter 'external_id'" );
		}
		if ( ! isset( $params['nonce'] ) ) {
			throw new \Exception( "Missing required parameter 'nonce'" );
		}
		if ( ! isset( $params['email'] ) ) {
			throw new \Exception( "Missing required parameter 'email'" );
		}
		$payload = base64_encode( http_build_query( $params ) );
		$sig     = hash_hmac( 'sha256', $payload, $this->sso_secret );

		return http_build_query(
			array(
				'sso' => $payload,
				'sig' => $sig,
			)
		);
	}
}
