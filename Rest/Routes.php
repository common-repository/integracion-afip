<?php

namespace CRPlugins\Afip\Rest;

use CRPlugins\Afip\Documents\DocumentProcessorInterface;
use CRPlugins\Afip\Orders\OrderProcessor;
use CRPlugins\Afip\Sdk\AfipSdk;
use CRPlugins\Afip\Settings\HealthChecker;

defined( 'ABSPATH' ) || exit;

class Routes {
	private const NAMESPACE_V1 = 'afip-for-woocommerce/v1';

	public function __construct(
		DocumentProcessorInterface $invoice_processor,
		DocumentProcessorInterface $credit_note_processor,
		OrderProcessor $order_processor,
		AfipSdk $sdk,
		HealthChecker $health_checker
	) {
		add_filter( 'woocommerce_is_rest_api_request', array( $this, 'rest_modifier' ) );

		$routers = array(
			new OrdersRest(
				self::NAMESPACE_V1,
				$invoice_processor,
				$credit_note_processor,
				$order_processor
			),
			new SettingsRest( self::NAMESPACE_V1, $sdk, $health_checker ),
		);

		foreach ( $routers as $router ) {
			add_action( 'rest_api_init', array( $router, 'register_routes' ) );
		}
	}

	/**
	 * @psalm-suppress PossiblyUndefinedArrayOffset
	 */
	public function rest_modifier( bool $is_rest_api_request ): bool {
		if ( false === strpos( wp_unslash( $_SERVER['REQUEST_URI'] ), self::NAMESPACE_V1 ) ) { // phpcs:ignore
			return $is_rest_api_request;
		}

		return false;
	}
}
