<?php

namespace CRPlugins\Afip\Rest;

use CRPlugins\Afip\Documents\DocumentProcessorInterface;
use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Orders\OrderProcessor;
use CRPlugins\Afip\ShippingLabels\DocumentManager;
use DateTime;
use Exception;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class OrdersRest implements RestRouteInterface {

	/**
	 * @var DocumentProcessorInterface
	 */
	private $invoice_processor;

	/**
	 * @var DocumentProcessorInterface
	 */
	private $credit_note_processor;

	/**
	 * @var OrderProcessor
	 */
	private $order_processor;

	/**
	 * @var string
	 */
	private $routes_namespace;

	public function __construct(
		string $routes_namespace,
		DocumentProcessorInterface $invoice_processor,
		DocumentProcessorInterface $credit_note_processor,
		OrderProcessor $order_processor
	) {
		$this->routes_namespace      = $routes_namespace;
		$this->invoice_processor     = $invoice_processor;
		$this->credit_note_processor = $credit_note_processor;
		$this->order_processor       = $order_processor;
	}

	public function register_routes(): void {
		// Labels
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/invoice',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_invoice' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/credit-note',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_credit_note' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/orders/invoices/download',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_download_link_bulk_invoices' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		// Order
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/process',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process_order' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/credit-note/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_credit_note' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/mails/invoice/send',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_invoice_mail' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/orders/(?P<order_id>\d+)/mails/credit-note/send',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_credit_note_mail' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public function get_invoice( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}

		$content = $this->invoice_processor->get_local_content( $order );
		if ( empty( $content ) ) {
			$content = $this->invoice_processor->get_remote_content( $order );
			$this->invoice_processor->create_file_from_base64( $content, $order );
		}

		return new WP_REST_Response( array( 'content' => $content ) );
	}

	public function get_credit_note( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}

		$content = $this->credit_note_processor->get_local_content( $order );
		if ( empty( $content ) ) {
			$content = $this->credit_note_processor->get_remote_content( $order );
			$this->credit_note_processor->create_file_from_base64( $content, $order );
		}

		return new WP_REST_Response( array( 'content' => $content ) );
	}

	public function get_download_link_bulk_invoices( WP_REST_Request $request ): WP_REST_Response {
		$query = $request->get_query_params();
		try {
			Validator::key_exists( $query, 'orders' );
			Validator::not_empty( $query['orders'], 'No se seleccionaron órdenes' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$order_ids = explode( ',', $query['orders'] );
		$pdfs      = array();

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			if ( ! Helper::is_order_processed( $order ) ) {
				continue;
			}

			$pdfs[] = $this->invoice_processor->get_file_path( $order );
		}

		if ( empty( $pdfs ) ) {
			wp_send_json_error();
		}

		try {
			$now      = new DateTime();
			$zip_name = sprintf( __( 'pdf-invoices-%s', 'wc-afip' ), $now->format( 'd-m-Y' ) );
			DocumentManager::create_zip( $zip_name, $pdfs );
		} catch ( Exception $e ) {
			Helper::log_error( 'Could not create compressed ZIP: ' . $e->getMessage() );
			return new WP_REST_Response( array( 'error' => 'Could not create compressed ZIP' ), 400 );
		}

		$zip_url = DocumentManager::get_zip_url( $zip_name );
		if ( ! $zip_url ) {
			return new WP_REST_Response( array( 'error' => 'Zip file could not be created' ), 400 );
		}

		$url = $zip_url . '?version=' . time();
		return new WP_REST_Response( array( 'url' => $url ) );
	}

	public function process_order( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}
		/** @var WC_Order $order */

		$body = $request->get_json_params();
		try {
			Validator::key_exists( $body, 'options', 'No se seleccionaron las opciones para crear la factura' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$customer_details = $body['options'];

		$this->order_processor->process_order( $order, $customer_details );
		return new WP_REST_Response( null, 204 );
	}

	public function create_credit_note( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}
		/** @var WC_Order $order */

		$this->order_processor->create_credit_note( $order );
		return new WP_REST_Response( null, 204 );
	}

	public function send_invoice_mail( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}
		/** @var WC_Order $order */

		$this->order_processor->send_invoice_mail( $order );

		return new WP_REST_Response( null, 204 );
	}

	public function send_credit_note_mail( WP_REST_Request $request ): WP_REST_Response {
		/** @var array{order_id: string} */
		$url_params = $request->get_url_params();
		$order      = wc_get_order( (int) $url_params['order_id'] );
		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order id' ), 400 );
		}
		/** @var WC_Order $order */

		$this->order_processor->send_credit_note_mail( $order );

		return new WP_REST_Response( null, 204 );
	}
}
