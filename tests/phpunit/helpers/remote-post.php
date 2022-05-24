<?php
/**
 * Class \Test\RemotePost
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Test;

/**
 * Remote posting methods for WPDiscourse unit tests
 */
trait RemotePost {
  /**
   * Build remote post response.
   *
   * @param string $type Type of response.
   * @param string $sub_type Sub-type of response.
   */
  protected function build_response( $type, $sub_type = null ) {
		$codes    = array(
			'success'            => 200,
			'invalid_parameters' => 400,
			'forbidden'          => 403,
			'not_found'          => 404,
			'unprocessable'      => 422,
		);
		$messages = array(
			'success'            => 'OK',
			'invalid_parameters' => 'Bad Request',
			'forbidden'          => 'Forbidden',
			'not_found'          => 'Not found',
			'unprocessable'      => 'Unprocessable Entity',
		);
		if ( in_array( $type, array( 'invalid_parameters', 'unprocessable', 'not_found' ), true ) ) {
			$body = $this->response_body_json( $type, $sub_type );
			} else {
			$body = array(
				'success'   => '{}',
				'forbidden' => 'You are not permitted to view the requested resource. The API username or key is invalid.',
			)[ $type ];
			}
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $codes[ $type ],
				'message' => $messages[ $type ],
			),
		);
  }

  /**
   * Build JSON of response body.
   *
   * @param string $type Type of response.
   * @param string $sub_type Sub-type of response.
   * @param string $action_type Action type of test.
   */
  protected function response_body_json( $type, $sub_type = null, $action_type = 'create_post' ) {
		if ( in_array( $type, array( 'post_create', 'post_update', 'user', 'comments', 'site', 'users', 'sync_sso', 'groups', 'user_create', 'scopes' ), true ) ) {
			return $this->response_body_file( $type );
			}
		if ( 'unprocessable' === $type ) {
			$messages     = array(
				'title' => 'Title seems unclear, most of the words contain the same letters over and over?',
				'embed' => 'Embed url has already been taken',
			);
			$message_type = $sub_type;
			} else {
			$messages     = array(
				'invalid_parameters' => "You supplied invalid parameters to the request: $sub_type",
				'not_found'          => "Sorry, that resource doesn't exist in our system.",
				'forbidden'          => 'You are not permitted to view the requested resource. The API username or key is invalid.',
			);
			$message_type = $type;
			}
		return wp_json_encode(
          array(
              'action'     => $action_type,
              'errors'     => array( $messages[ $message_type ] ),
              'error_type' => $type,
          )
		);
  }

  /**
   * Get fixture with response body.
   *
   * @param string $file Name of response body file.
   */
  protected function response_body_file( $file ) {
		return file_get_contents( __DIR__ . "/../../fixtures/response_body/$file.json" );
  }

  /**
   * Mock remote post response.
   *
   * @param object $first_request First request in tested method.
   * @param object $second_request Second request in tested method.
   */
  protected function mock_remote_post( $first_request, $second_request = null ) {
		add_filter(
          'pre_http_request',
          function( $prempt, $args, $url ) use ( $first_request, $second_request ) {
				$is_sr   = ! empty( $second_request ) && ( strpos( $url, $second_request['url'] ) !== false );
				$request = $is_sr ? $second_request : $first_request;

				if ( $request['method'] != $args['method'] ) {
				  return new \WP_Error( 'http_request_failed', 'Incorrect method' );
					}

				if ( isset( $request['headers'] ) && $request['headers'] != $args['headers'] ) {
				  return new \WP_Error( 'http_request_failed', 'Incorrect headers' );
					}

				if ( isset( $request['body'] ) && $request['body'] != $args['body'] ) {
				  return new \WP_Error( 'http_request_failed', 'Incorrect body' );
					}

				return $request['response'];
				},
          10,
          3
		);
  }

  /**
   * Mock remote post success.
   *
   * @param string $type Type of response.
   * @param object $second_request Second request response of second request in tested method.
   */
  protected function mock_remote_post_success( $type, $method = 'GET', $second_request = null ) {
		$raw_body         = $this->response_body_json( $type );
		$response         = $this->build_response( 'success' );
		$response['body'] = $raw_body;
		$first_request    = array(
			'method'   => $method,
			'response' => $response,
		);
		$this->mock_remote_post( $first_request, $second_request );
		return json_decode( $raw_body );
  }
}
