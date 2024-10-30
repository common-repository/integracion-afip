<?php

namespace CRPlugins\Afip\Api;

use CRPlugins_Afip;

class AfipFilesApi extends Client {

	public const PROD_BASE_URL = 'https://afipapi.crplugins.com.ar/api/v2';

	/**
	 * @var string
	 */
	private $apikey;

	/**
	 * @var string
	 */
	private $environment;

	public function __construct( string $apikey, string $environment ) {
		$this->apikey      = $apikey;
		$this->environment = $environment;
	}

	/**
	 * @param string $method
	 * @param array<string,string> $body
	 * @param array<string,string> $headers
	 * @return array{method: string, headers: array<string,string>}
	 */
	public function before_request( string $method, array $body, array $headers ): array {
		$headers['X-Agent']       = sprintf( 'afip-woocommerce-plugin/%s', CRPlugins_Afip::PLUGIN_VER );
		$headers['X-Origin']      = get_site_url();
		$headers['X-Environment'] = $this->environment;
		$headers['Authorization'] = $this->apikey;

		$password                = wp_generate_password( 24 );
		$headers['Content-Type'] = sprintf( 'multipart/form-data; boundary=%s', $password );

		$payload = '';
		// Upload the file
		foreach ( $body as $name => $value ) {
			// Check if the value is an array (for multiple files)
			if ( is_array( $value ) ) {
				foreach ( $value as $file_path ) {
					$payload .= sprintf( '--%s', $password );
					$payload .= "\r\n";
					$payload .= sprintf( 'Content-Disposition: form-data; name="%s[]"; filename="%s"' . "\r\n", $name, basename( $file_path ) );
					$payload .= "\r\n";
					$payload .= file_get_contents($file_path); // phpcs:ignore
					$payload .= "\r\n";
				}
			} else {
				// Handle single value fields
				$payload .= sprintf( '--%s', $password );
				$payload .= "\r\n";
				$payload .= sprintf( 'Content-Disposition: form-data; name="%s"' . "\r\n\r\n", $name );
				$payload .= $value; // Add the single value
				$payload .= "\r\n";
			}
		}
		$payload .= sprintf( '--%s--', $password );

		return array(
			'method'  => $method,
			'headers' => $headers,
			'body'    => $payload,
		);
	}

	public function get_base_url(): string {
		return self::PROD_BASE_URL;
	}
}
