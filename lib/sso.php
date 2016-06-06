<?php

/**
 * Single-sign-on for Discourse via PHP
 *
 * @link https://github.com/ArmedGuy/discourse_sso_php
 */
class Discourse_SSO {
	private $sso_secret;

	function __construct( $secret ) {
		$this->sso_secret = $secret;
	}

	public function validate( $payload, $sig ) {
		$payload = urldecode( $payload );
		if ( hash_hmac( "sha256", $payload, $this->sso_secret ) === $sig ) {
			return true;
		} else {
			return false;
		}
	}

	public function getNonce( $payload ) {
		$payload = urldecode( $payload );
		$query   = array();
		parse_str( base64_decode( $payload ), $query );
		if ( isset( $query["nonce"] ) ) {
			return $query["nonce"];
		} else {
			throw new Exception( "Nonce not found in payload!" );
		}
	}

	public function buildLoginString( $params ) {
		if ( ! isset( $params["external_id"] ) ) {
			throw new Exception( "Missing required parameter 'external_id'" );
		}
		if ( ! isset( $params["nonce"] ) ) {
			throw new Exception( "Missing required parameter 'nonce'" );
		}
		if ( ! isset( $params["email"] ) ) {
			throw new Exception( "Missing required parameter 'email'" );
		}
		$payload = base64_encode( http_build_query( $params ) );
		$sig     = hash_hmac( "sha256", $payload, $this->sso_secret );

		return http_build_query( array( "sso" => $payload, "sig" => $sig ) );
	}
}
