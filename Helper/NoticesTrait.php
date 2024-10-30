<?php

namespace CRPlugins\Afip\Helper;

trait NoticesTrait {

	public static function check_notices(): void {
		$notices_types = array( 'error', 'success', 'info' );
		foreach ( $notices_types as $type ) {
			/** @var string[]|false $notices */
			$notices = get_transient( 'wc-afip-' . $type . '-notices' );
			if ( empty( $notices ) ) {
				continue;
			}
			foreach ( $notices as $notice ) {
				echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible">';
				echo '<p>' . wp_kses(
					$notice,
					array(
						'br'     => array(),
						'strong' => array(),
						'b'      => array(),
					)
				) . '</p>';
				echo '</div>';
			}
			delete_transient( 'wc-afip-' . $type . '-notices' );
		}
	}

	private static function add_notice( string $type, string $msg, bool $do_action = false ): void {
		/** @var string[]|false $notices */
		$notices = get_transient( 'wc-afip-' . $type . '-notices' );
		if ( ! empty( $notices ) ) {
			$notices[] = $msg;
		} else {
			$notices = array( $msg );
		}
		set_transient( 'wc-afip-' . $type . '-notices', $notices, 60 );
		if ( $do_action ) {
			do_action( 'admin_notices' );
		}
	}

	public static function add_error( string $msg, bool $do_action = false ): void {
		self::add_notice( 'error', $msg, $do_action );
	}

	public static function add_success( string $msg, bool $do_action = false ): void {
		self::add_notice( 'success', $msg, $do_action );
	}

	public static function add_info( string $msg, bool $do_action = false ): void {
		self::add_notice( 'info', $msg, $do_action );
	}
}
