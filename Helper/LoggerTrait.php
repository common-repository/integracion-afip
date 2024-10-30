<?php

namespace CRPlugins\Afip\Helper;

use WC_Logger_Interface;

trait LoggerTrait {

	/**
	 * @var ?WC_Logger_Interface
	 */
	private static $logger = null;

	public static function log_info( string $msg ): void {
		if ( ! self::$logger ) {
			self::$logger = wc_get_logger();
		}
		self::$logger->info( $msg, array( 'source' => 'WooCommerce Afip' ) );
	}

	public static function log_error( string $msg ): void {
		if ( ! self::$logger ) {
			self::$logger = wc_get_logger();
		}
		self::$logger->error( $msg, array( 'source' => 'WooCommerce Afip' ) );
	}

	public static function log_warning( string $msg ): void {
		if ( ! self::$logger ) {
			self::$logger = wc_get_logger();
		}
		self::$logger->warning( $msg, array( 'source' => 'WooCommerce Afip' ) );
	}

	public static function log_for_debug( string $msg ): void {
		if ( ! self::$logger ) {
			self::$logger = wc_get_logger();
		}
		self::$logger->debug( $msg, array( 'source' => 'WooCommerce Afip' ) );
	}
}
