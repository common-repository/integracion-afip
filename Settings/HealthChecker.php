<?php

namespace CRPlugins\Afip\Settings;

use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Sdk\AfipSdk;

class HealthChecker {

	/**
	 * @var AfipSdk
	 */
	private $sdk;

	public function __construct( AfipSdk $sdk ) {
		$this->sdk = $sdk;
	}

	public function are_file_permissions_valid(): bool {
		$valid = false;
		$file  = sprintf( '%s/test.pdf', Helper::get_invoice_folder_path() );

		try {
			$file_stream = fopen( $file, 'w' ); // phpcs:ignore
			if ( ! $file_stream ) {
				throw new \Exception( 'Error' );
			}

			fwrite( $file_stream, '\n' ); // phpcs:ignore
			fclose( $file_stream ); // phpcs:ignore
			unlink( $file ); // phpcs:ignore
			$valid = true;
		} catch ( \Throwable $th ) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * @return array{cert: boolean, api: boolean}
	 */
	public function get_api_status(): array {
		$response = $this->sdk->get_api_status();
		if ( isset( $response['error'] ) ) {
			return array(
				'cert' => false,
				'api'  => false,
			);
		}

		return array(
			'cert' => $response['cert'],
			'api'  => $response['api'],
		);
	}
}
