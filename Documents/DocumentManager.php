<?php

namespace CRPlugins\Afip\ShippingLabels;

use CRPlugins\Afip\Helper\Helper;
use ZipArchive;

defined( 'ABSPATH' ) || exit;

class DocumentManager {

	/**
	 * @param string[] $files
	 */
	public static function create_zip( string $zip_name, array $files ): void {
		$zip      = new ZipArchive();
		$zip_name = sprintf( '%s/%s.zip', Helper::get_invoice_folder_path(), $zip_name );

		// Override old zip
		if ( file_exists( $zip_name ) ) {
			unlink( $zip_name ); // phpcs:ignore
		}

		$zip->open( $zip_name, ZipArchive::CREATE );
		foreach ( $files as $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$zip->addFile( $file, basename( $file ) );
		}

		$zip->close();
	}

	public static function get_zip_url( string $zip_name ): string {
		if ( ! file_exists(
			sprintf(
				'%s/%s.zip',
				Helper::get_invoice_folder_path(),
				$zip_name
			)
		) ) {
			return '';
		}

		return sprintf(
			'%s/%s.zip',
			Helper::get_invoice_folder_url(),
			$zip_name
		);
	}
}
