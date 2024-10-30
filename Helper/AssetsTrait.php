<?php

namespace CRPlugins\Afip\Helper;

use CRPlugins_Afip;

trait AssetsTrait {

	public static function get_assets_folder_url(): string {
		return plugin_dir_url( CRPlugins_Afip::MAIN_FILE ) . 'assets/dist';
	}

	public static function get_assets_folder_path(): string {
		return plugin_dir_path( CRPlugins_Afip::MAIN_FILE ) . 'assets/dist';
	}

	public static function get_invoice_folder_path(): string {
		return plugin_dir_path( CRPlugins_Afip::MAIN_FILE ) . 'labels';
	}

	public static function get_credit_note_folder_path(): string {
		return plugin_dir_path( CRPlugins_Afip::MAIN_FILE ) . 'credit-notes';
	}

	public static function get_invoice_folder_url(): string {
		return plugin_dir_url( CRPlugins_Afip::MAIN_FILE ) . 'labels';
	}

	public static function get_credit_note_folder_url(): string {
		return plugin_dir_url( CRPlugins_Afip::MAIN_FILE ) . 'credit-notes';
	}

	public static function get_uploads_dir(): string {
		return wp_get_upload_dir()['basedir'] . '/integracion-afip';
	}

	public static function get_uploads_url(): string {
		return wp_get_upload_dir()['baseurl'] . '/integracion-afip';
	}

	public static function get_logo_url(): ?string {
		$path = sprintf( '%s/logo.jpg', self::get_uploads_dir() );
		if ( file_exists( $path ) ) {
			return sprintf( '%s/logo.jpg', self::get_uploads_url() );
		}

		$path = sprintf( '%s/logo.png', self::get_uploads_dir() );
		if ( file_exists( $path ) ) {
			return sprintf( '%s/logo.png', self::get_uploads_url() );
		}

		return null;
	}

	public static function delete_logo(): void {
		$path = sprintf( '%s/logo.jpg', self::get_uploads_dir() );
		if ( file_exists( $path ) ) {
			unlink( $path ); // phpcs:ignore
		}

		$path = sprintf( '%s/logo.png', self::get_uploads_dir() );
		if ( file_exists( $path ) ) {
			unlink( $path ); // phpcs:ignore
		}
	}

	public static function locate_template( string $template_name ): string {

		// Set variable to search in the templates folder of theme.
		$template_path = 'templates/';

		// Set default plugin templates path.
		$default_path = plugin_dir_path( CRPlugins_Afip::MAIN_FILE ) . 'templates/';

		// Search template file in theme folder.
		$template = locate_template(
			array(
				'integracion-afip/templates/' . $template_name,
				$template_name,
			)
		);

		// Get plugins template file.
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		return apply_filters( 'wc_afip_locate_template', $template, $template_name, $template_path, $default_path );
	}
}
