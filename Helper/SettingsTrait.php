<?php

namespace CRPlugins\Afip\Helper;

/**
 * About WordPress settings:
 *
 * Strings are saved as string
 * Ints are saved as int
 * Floats are saved as float
 * Arrays are saved as array
 * True is saved as true
 * False is saved as null
 * Null is saved as '' (empty string)
 *
 * To avoid confusion we will be using '' for empty values string, 0 for int|float, and treat bool as string ('true'|'false')
 */
trait SettingsTrait {

	/**
	 * @param mixed $value
	 */
	public static function save_option( string $key, $value ): void {
		update_option( 'wc-afip-' . $key, $value );
	}

	/**
	 * @param mixed $default_value
	 * @return mixed
	 */
	public static function get_option( string $key, $default_value = null ) {
		return get_option( 'wc-afip-' . $key, $default_value );
	}

	/**
	 * @param mixed $default_value
	 */
	public static function is_enabled( string $key, $default_value = null ): bool {
		return get_option( 'wc-afip-' . $key, $default_value ) === 'true';
	}

	public static function delete_option( string $key ): void {
		delete_option( 'wc-afip-' . $key );
	}
}
