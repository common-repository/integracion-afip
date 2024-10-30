<?php

namespace CRPlugins\Afip\Api;

/**
 * @psalm-type Request = array{method: string, headers: array<string,string>, body?: string}
 */
abstract class Client {

	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{response: mixed, request: Request}
	 */
	protected function exec( string $method, string $url, array $body, array $headers ): array {
		$args    = $this->before_request( $method, $body, $headers );
		$request = wp_safe_remote_request( $url, $args );
		if ( is_wp_error( $request ) ) {
			throw new \Exception( sprintf( 'There was an error with the request: %s', esc_html( $request->get_error_message() ) ) );
		}

		$response    = wp_remote_retrieve_body( $request );
		$args['url'] = $url;
		return $this->after_request( $response, $args );
	}


	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @psalm-return Request
	 */
	public function before_request( string $method, array $body, array $headers ): array {
		$headers['Content-Type'] = 'application/json';
		$request                 = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 15,
		);
		if ( ! empty( $body ) ) {
			/** @var string */
			$request['body'] = wp_json_encode( $body, JSON_UNESCAPED_UNICODE );
		}

		return $request;
	}

	/**
	 * @psalm-param Request $request
	 * @return array{response: mixed, request: Request}
	 */
	public function after_request( string $response, array $request ): array {
		return array(
			'response' => json_decode( $response, true ),
			'request'  => $request,
		);
	}

	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{response: mixed, request: array{method: string, headers: array<string,string>, body?: string}}
	 */
	public function get( string $endpoint, array $body = array(), array $headers = array() ): array {
		$url = $this->get_base_url() . $endpoint;
		if ( ! empty( $body ) ) {
			$url .= '?' . http_build_query( $body );
		}
		return $this->exec( 'GET', $url, array(), $headers );
	}

	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{response: mixed, request: Request}
	 */
	public function post( string $endpoint, array $body = array(), array $headers = array() ): array {
		$url = $this->get_base_url() . $endpoint;
		return $this->exec( 'POST', $url, $body, $headers );
	}

	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{response: mixed, request: Request}
	 */
	public function put( string $endpoint, array $body = array(), array $headers = array() ): array {
		$url = $this->get_base_url() . $endpoint;
		return $this->exec( 'PUT', $url, $body, $headers );
	}

	/**
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{response: mixed, request: Request}
	 */
	public function delete( string $endpoint, array $body = array(), array $headers = array() ): array {
		$url = $this->get_base_url() . $endpoint;
		return $this->exec( 'DELETE', $url, $body, $headers );
	}

	protected function add_params_to_url( string $url, string $params ): string {
		if ( strpos( $url, '?' ) !== false ) {
			$url .= '&' . $params;
		} else {
			$url .= '?' . $params;
		}
		return $url;
	}

	abstract public function get_base_url(): string;
}
