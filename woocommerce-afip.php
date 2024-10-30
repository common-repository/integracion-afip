<?php

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use CRPlugins\Afip\Api\AfipApi;
use CRPlugins\Afip\Checkout\CheckoutModifier;
use CRPlugins\Afip\Container\Container;
use CRPlugins\Afip\Container\ContainerInterface;
use CRPlugins\Afip\Documents\CreditNoteProcessor;
use CRPlugins\Afip\Documents\DocumentCleanerCron;
use CRPlugins\Afip\Documents\InvoiceProcessor;
use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Orders\Metabox;
use CRPlugins\Afip\Orders\OrderProcessor;
use CRPlugins\Afip\Orders\OrdersList;
use CRPlugins\Afip\Rest\Routes;
use CRPlugins\Afip\Sdk\AfipSdk;
use CRPlugins\Afip\Settings\HealthChecker;
use CRPlugins\Afip\Settings\MainSettings;

/**
 * Plugin Name: AFIP para WooCommerce
 * Description: Integración entre AFIP y WooCommerce
 * Version: 3.1.0
 * Requires PHP: 7.1
 * Author: CRPlugins
 * Author URI: https://crplugins.com.ar
 * Text Domain: wc-afip
 * Domain Path: /i18n/languages/
 * WC requires at least: 4.2
 * WC tested up to: 9.3.3
 */

defined( 'ABSPATH' ) || exit;

class CRPlugins_Afip {

	public const PLUGIN_NAME = 'AFIP para WooCommerce';
	public const MAIN_FILE   = __FILE__;
	public const MAIN_DIR    = __DIR__;
	public const PLUGIN_VER  = '3.1.0';

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( self::MAIN_FILE ), array( $this, 'create_settings_link' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibility' ) );
	}

	public function init(): void {
		if ( ! $this->check_system() ) {
			return;
		}

		spl_autoload_register(
			function ( string $class_name ) {
				if ( strpos( $class_name, 'CRPlugins\Afip' ) === false ) {
					return;
				}
				$file = str_replace( 'CRPlugins\\Afip\\', '', $class_name );
				$file = str_replace( '\\', '/', $file );
				/** @psalm-suppress UnresolvableInclude */
				require_once sprintf( '%s/%s.php', __DIR__, $file );
			}
		);

		$this->init_container();
		$this->init_classes();
		$this->load_textdomain();
	}

	public function check_system( bool $show_notice = true ): bool {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$system = self::check_components();

		if ( $system['flag'] ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			if ( $show_notice ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p><strong>' . esc_attr( self::PLUGIN_NAME ) . '</strong> ' . sprintf( esc_html__( 'Requires at least %1$s version %2$s or greater.', 'wc-afip' ), esc_html( $system['flag'] ), esc_html( $system['version'] ) );
				echo '</div>';
			}
			return false;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			if ( $show_notice ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . esc_html__( 'WooCommerce must be active before using', 'wc-afip' ) . ' <strong>' . esc_html( self::PLUGIN_NAME ) . '</strong></p>';
				echo '</div>';
			}
			return false;
		}

		return true;
	}

	/**
	 * @return array{flag: string, version: string}
	 */
	private static function check_components(): array {

		global $wp_version;
		/** @var string $wp_version */
		$flag    = '';
		$version = '';

		if ( version_compare( PHP_VERSION, '7.1', '<' ) ) {
			$flag    = 'PHP';
			$version = '7.1';
		} elseif ( version_compare( $wp_version, '5.0', '<' ) ) {
			$flag    = 'WordPress';
			$version = '5.0';
		} elseif ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '4.0', '<' ) ) {
			$flag    = 'WooCommerce';
			$version = '4.0';
		}

		return array(
			'flag'    => $flag,
			'version' => $version,
		);
	}

	public function init_container(): void {
		$container = Container::instance();
		$container->set(
			AfipApi::class,
			static function ( ContainerInterface $container ): AfipApi { // phpcs:ignore
				return new AfipApi(
					Helper::get_option( 'apikey', '' ),
					Helper::get_option( 'environment' ) === 'production' ? 'production' : 'testing'
				);
			}
		);
		$container->set(
			AfipSdk::class,
			static function ( ContainerInterface $container ): AfipSdk {
				return new AfipSdk( $container->get( AfipApi::class ) );
			}
		);
		$container->set(
			InvoiceProcessor::class,
			static function ( ContainerInterface $container ): InvoiceProcessor {
				return new InvoiceProcessor( $container->get( AfipSdk::class ) );
			}
		);
		$container->set(
			CreditNoteProcessor::class,
			static function ( ContainerInterface $container ): CreditNoteProcessor {
				return new CreditNoteProcessor( $container->get( AfipSdk::class ) );
			}
		);
		$container->set(
			OrderProcessor::class,
			static function ( ContainerInterface $container ): OrderProcessor {
				return new OrderProcessor(
					$container->get( AfipSdk::class ),
					$container->get( InvoiceProcessor::class ),
					$container->get( CreditNoteProcessor::class )
				);
			}
		);
		$container->set(
			OrdersList::class,
			static function ( ContainerInterface $container ): OrdersList {
				return new OrdersList( $container->get( OrderProcessor::class ) );
			}
		);
		$container->set(
			HealthChecker::class,
			static function ( ContainerInterface $container ): HealthChecker {
				return new HealthChecker( $container->get( AfipSdk::class ) );
			}
		);
	}

	public function init_classes(): void {
		$container = Container::instance();

		// We init these classes so their hooks are registered
		$container->get( OrderProcessor::class );
		$container->get( OrdersList::class );
		new Routes(
			$container->get( InvoiceProcessor::class ),
			$container->get( CreditNoteProcessor::class ),
			$container->get( OrderProcessor::class ),
			$container->get( AfipSdk::class ),
			$container->get( HealthChecker::class )
		);
		new Metabox();
		new DocumentCleanerCron();
		new MainSettings();
		new CheckoutModifier();

		/** @psalm-suppress InvalidArgument */
		add_action( 'admin_notices', array( Helper::class, 'check_notices' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'wc-afip', false, basename( __DIR__ ) . '/i18n/languages' );
	}

	public function admin_register_scripts(): void {
		if ( ! $this->check_system( false ) ) {
			return;
		}

		wp_register_script( 'wc-afip-surreal', Helper::get_assets_folder_url() . '/js/surreal.js', array(), self::PLUGIN_VER, true );
		wp_register_script( 'wc-afip-helper-js', Helper::get_assets_folder_url() . '/js/helper.js', array( 'wc-afip-surreal' ), self::PLUGIN_VER, true );
		wp_localize_script(
			'wc-afip-helper-js',
			'wc_afip_helper_settings',
			array(
				'store_url' => get_site_url(),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
			)
		);
		wp_localize_script(
			'wc-afip-helper-js',
			'wc_afip_translation_texts',
			array(
				'generic_error_try_again' => __( 'There was an error, please try again', 'wc-afip' ),
				'loading'                 => __( 'Loading...', 'wc-afip' ),
			)
		);
		wp_register_script( 'wc-afip-settings-js', Helper::get_assets_folder_url() . '/js/settings.js', array(), self::PLUGIN_VER, true );
		wp_register_style( 'wc-afip-general-css', Helper::get_assets_folder_url() . '/css/general.css', array(), self::PLUGIN_VER );
		wp_register_script( 'wc-afip-orders-list-js', Helper::get_assets_folder_url() . '/js/orders-list.js', array( 'wc-afip-helper-js' ), self::PLUGIN_VER, true );
		wp_register_script( 'wc-afip-orders-js', Helper::get_assets_folder_url() . '/js/orders.js', array( 'wc-afip-helper-js' ), self::PLUGIN_VER, true );
		wp_register_style( 'wc-afip-orders-css', Helper::get_assets_folder_url() . '/css/orders.css', array(), self::PLUGIN_VER );
	}

	public function declare_wc_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', self::MAIN_FILE, true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', self::MAIN_FILE, true );
		}
	}

	public function create_settings_link( array $links ): array {
		$link = sprintf(
			'<a href="%s">%s</a>',
			esc_attr( esc_url( get_admin_url( null, 'admin.php?page=wc-afip-admin' ) ) ),
			esc_html__( 'Settings', 'wc-afip' )
		);
		array_unshift( $links, $link );
		return $links;
	}
}

new CRPlugins_Afip();
