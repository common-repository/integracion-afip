<?php

namespace CRPlugins\Afip\Documents;

use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Sdk\AfipSdk;
use WC_Order;

defined( 'ABSPATH' ) || exit;

abstract class DocumentProcessor {
	/**
	 * @var AfipSdk
	 */
	protected $sdk;

	public function __construct( AfipSdk $sdk ) {
		$this->sdk = $sdk;
	}

	abstract public function get_file_path( WC_Order $order );
	abstract public function get_file_uri( string $file_name );
	abstract public function get_file_name( WC_Order $order );
	abstract public function get_remote_content( WC_Order $order );

	public function get_local_content( WC_Order $order ): string {
		if ( ! $this->file_exists( $order ) ) {
			return '';
		}

		return base64_encode( file_get_contents( $this->get_file_path( $order ) ) ); // phpcs:ignore
	}

	public function file_exists( WC_Order $order ): bool {
		$file = $this->get_file_uri( $this->get_file_name( $order ) );
		return file_exists( $file ) && filesize( $file );
	}


	public function create_file_from_base64( string $base64_data, WC_Order $order ): void {
		$content = base64_decode( $base64_data );

		$file = $this->get_file_uri( $this->get_file_name( $order ) );

		$file_stream = fopen( $file, 'w' ); // phpcs:ignore
		if ( ! $file_stream ) {
			Helper::log_error( 'Could not open file ' . $file );
			return;
		}

		fwrite( $file_stream, $content ); // phpcs:ignore
		fclose( $file_stream ); // phpcs:ignore
	}
}
