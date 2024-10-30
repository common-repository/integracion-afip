<?php

namespace CRPlugins\Afip\Api;

use CRPlugins_Afip;

class AfipApi extends Client {

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
	 * @param mixed[] $body
	 * @param array<string,string> $headers
	 * @return array{method: string, headers: array<string,string>}
	 */
	public function before_request( string $method, array $body, array $headers ): array {
		$headers['X-Agent']       = sprintf( 'afip-woocommerce-plugin/%s', CRPlugins_Afip::PLUGIN_VER );
		$headers['X-Origin']      = get_site_url();
		$headers['X-Environment'] = $this->environment;
		$headers['Authorization'] = $this->apikey;

		return parent::before_request( $method, $body, $headers );
	}

	public function get_base_url(): string {
		return self::PROD_BASE_URL;
	}

	public function get_api_key(): string {
		return $this->apikey;
	}

	public function get_environment(): string {
		return $this->environment;
	}
}
