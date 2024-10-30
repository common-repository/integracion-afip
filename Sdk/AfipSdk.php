<?php

namespace CRPlugins\Afip\Sdk;

use CRPlugins\Afip\Api\AfipApi;
use CRPlugins\Afip\Api\AfipFilesApi;
use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\ValueObjects\Customer;
use CRPlugins\Afip\ValueObjects\Invoice;
use CRPlugins\Afip\ValueObjects\Items;
use CRPlugins\Afip\ValueObjects\Seller;
use Exception;
use WC_Order;

/**
 * @psalm-type Request = array{method: string, headers: array<string,string>, body?: string}
 */
class AfipSdk {

	/**
	 * @var AfipApi
	 */
	private $api;

	public function __construct( AfipApi $api ) {
		$this->api = $api;
	}

	protected function log_filtered(
		string $message,
		string $function_name,
		$data,
		string $key_to_filter,
		string $level
	): void {
		if ( is_array( $data ) ) {
			if ( ! isset( $data[ $key_to_filter ] ) ) {
				return;
			}

			$data[ $key_to_filter ] = '!! Filtered out !!';
		}
		if ( 'info' === $level ) {
			Helper::log_info( sprintf( $message, $function_name, wc_print_r( $data, true ) ) );
		} elseif ( 'error' === $level ) {
			Helper::log_error( sprintf( $message, $function_name, wc_print_r( $data, true ) ) );
		} elseif ( 'debug' === $level ) {
			Helper::log_debug( sprintf( $message, $function_name, wc_print_r( $data, true ) ) );
		}
	}

	/**
	 * @param array{request: mixed, response: array<string,mixed>|never[]} $response
	 * @return array{error: string}|array<string,mixed>
	 */
	protected function handle_response( array $response, string $function_name ): array {
		if ( 'application/json' !== $response['request']['headers']['Content-Type'] ) {
			$this->log_filtered( '%s - Data sent to Afip: %s', $function_name, $response['request'], 'body', 'debug' );
			Helper::log_debug( sprintf( '%s - Data received from Afip: %s', $function_name, wc_print_r( $response['response'], true ) ) );
		} elseif ( in_array( $function_name, array( 'process_order', 'create_credit_note' ), true ) ) {
			Helper::log_debug( sprintf( '%s - Data sent to Afip: %s', $function_name, wc_print_r( $response['request'], true ) ) );
			$this->log_filtered( '%s - Data received from Afip: %s', $function_name, $response['response'], 'pdf', 'debug' );
		} else {
			Helper::log_debug( sprintf( '%s - Data sent to Afip: %s', $function_name, wc_print_r( $response['request'], true ) ) );
			Helper::log_debug( sprintf( '%s - Data received from Afip: %s', $function_name, wc_print_r( $response['response'], true ) ) );
		}

		if ( 'process_order' === $function_name ) {
			Helper::log_info( sprintf( __( '%1$s - Data sent to Afip: %2$s', 'wc-afip' ), $function_name, wc_print_r( $response['request'], true ) ) );
			$this->log_filtered( __( '%1$s - Data received from Afip: %2$s', 'wc-afip' ), $function_name, $response['response'], 'pdf', 'info' );
		}

		if ( empty( $response['response'] ) ) {
			Helper::log_warning( $function_name . ': ' . __( 'No response from AFIP server', 'wc-afip' ) );
			return array( 'error' => __( 'No response from AFIP server', 'wc-afip' ) );
		}

		if ( ! empty( $response['response']['error'] ) ) {
			Helper::log_error( $function_name . ': ' . $response['response']['error'] );
			if ( 'application/json' !== $response['request']['headers']['Content-Type'] ) {
				$this->log_filtered( '%s - Data sent: %s', $function_name, $response['request'], 'body', 'error' );
			} else {
				Helper::log_error( sprintf( '%s - Data sent: %s', $function_name, wc_print_r( $response['request'], true ) ) );
			}
		}

		return $this->translate_errors( $response['response'] );
	}

	/**
	 * @return never[]|array{error: string}|array{apiKey: string, domain: string, type: string, status: string, expiration_date: string}
	 */
	public function get_store(): array {
		try {
			$res = $this->api->get( '/stores/mine' );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{id: string, externalId: string, pdf: string}|never[]
	 */
	public function process_order(
		Seller $seller,
		Invoice $invoice,
		Customer $customer,
		Items $items,
		WC_Order $order,
		float $order_total,
		float $discount,
		string $legend
	): array {
		$data_to_send = array(
			'seller'      => array(
				'pointOfSale'  => $seller->get_point_of_sale(),
				'cuit'         => $seller->get_cuit(),
				'name'         => $seller->get_name(),
				'fantasyName'  => $seller->get_fantasy_name(),
				'address'      => $seller->get_address(),
				'condition'    => $seller->get_condition(),
				'taxCondition' => $seller->get_tax_condition(),
				'startDate'    => $seller->get_start_date(),
			),
			'type'        => $invoice->get_type(),
			'term'        => $invoice->get_term(),
			'unit'        => $invoice->get_unit(),
			'productType' => $invoice->get_product_type(),
			'taxType'     => $invoice->get_tax_type(),
			'customer'    => array(
				'name'      => $customer->get_full_name(),
				'address'   => $customer->get_address()->get_full_address(),
				'condition' => $customer->get_condition(),
				'document'  => array(
					'type'   => $customer->get_document()->get_type(),
					'number' => $customer->get_document()->get_number(),
				),
			),
			'discount'    => $discount,
			'orderTotal'  => $order_total,
			'orderId'     => $order->get_id(),
			'legend'      => $legend,
			'items'       => array(),
			'taxes'       => array(),
		);

		foreach ( $items->get() as $item ) {
			$data_to_send['items'][] = array(
				'price'    => $item->get_price(),
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'id'       => $item->get_id(),
				'sku'      => $item->get_sku(),
				'discount' => $item->get_discount(),
				'taxType'  => $item->get_tax_type(),
			);
		}

		try {
			$res = $this->api->post( '/afip/invoices', $data_to_send );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . wp_json_encode( $data_to_send ) );
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{id: string, externalId: string, pdf: string}|never[]
	 */
	public function create_credit_note( string $invoice_cae ): array {
		try {
			$data_to_send = array( 'cae' => $invoice_cae );
			$res          = $this->api->post( '/afip/credit-notes', $data_to_send );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{id: string, externalId: string, pdf: string}
	 */
	public function get_invoice( string $invoice_cae ): array {
		try {
			$res = $this->api->get( sprintf( '/afip/invoices/%s', $invoice_cae ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{id: string, externalId: string, pdf: string}
	 */
	public function get_credit_note( string $credit_note_cae ): array {
		try {
			$res = $this->api->get( sprintf( '/afip/credit-notes/%s', $credit_note_cae ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{csr: string}
	 */
	public function get_csr( string $cuit ): array {
		try {
			$res = $this->api->get( '/afip/csr', array( 'cuit' => $cuit ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|never[]
	 */
	public function delete_cert(): array {
		try {
			$res = $this->api->delete( '/afip/cert' );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|never[]
	 */
	public function upload_cert( string $cert_uri ): array {
		try {
			$api = new AfipFilesApi( $this->api->get_api_key(), $this->api->get_environment() );
			$res = $api->post( '/afip/cert', array( 'cert' => $cert_uri ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|never[]
	 */
	public function upload_logo( string $logo_uri, string $extension ): array {
		try {
			$api = new AfipFilesApi( $this->api->get_api_key(), $this->api->get_environment() );
			$res = $api->post(
				sprintf( '/afip/logo?extension=%s', $extension ),
				array(
					'logo' => $logo_uri,
				)
			);
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|never[]
	 */
	public function delete_logo(): array {
		try {
			$res = $this->api->delete( '/afip/logo' );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @param string[] $report_uris
	 * @return array{error: string}|never[]
	 */
	public function send_reports( array $report_uris ): array {
		try {
			$api = new AfipFilesApi( $this->api->get_api_key(), $this->api->get_environment() );
			$res = $api->post( '/afip/reports', array( 'reports' => $report_uris ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @return array{error: string}|array{cert: bool, api: bool}
	 */
	public function get_api_status(): array {
		try {
			$res = $this->api->get( sprintf( '/health' ) );
		} catch ( Exception $e ) {
			Helper::log_error( __FUNCTION__ . ': ' . $e->getMessage() );
			return array();
		}
		return $this->handle_response( $res, __FUNCTION__ );
	}

	/**
	 * @param array{response?: string} $response
	 * @return array{response?: string}
	 */
	private function translate_errors( $response ): array {
		if ( empty( $response['error'] ) ) {
			return $response;
		}

		if ( 'Could not replicate the order totals' === $response['error'] ) {
			$response['error'] = __( 'Could not replicate the order totals', 'wc-afip' );
			return $response;
		}

		if ( 'Invoices of type A must provide a tax type different than 3' === $response['error'] ) {
			$response['error'] = __( 'Invoices of type A must provide a tax type different than 3', 'wc-afip' );
			return $response;
		}

		if ( 'Invoices of type C must provide tax type 3' === $response['error'] ) {
			$response['error'] = __( 'Invoices of type C must provide tax type 3', 'wc-afip' );
			return $response;
		}

		if ( '(10013) Campo  DocTipo  Para comprobantes clase A y M el campo  DocTipo debe ser igual a 80 (CUIT)' === $response['error'] ) {
			$response['error'] = __( 'You must provide a CUIT number for invoices of type A', 'wc-afip' );
			return $response;
		}

		if ( 'Mismatch between afip data and local customer data' === $response['error'] ) {
			$response['error'] = __( 'Mismatch between afip data and local customer data', 'wc-afip' );
			return $response;
		}

		if ( 'File is empty' === $response['error'] ) {
			$response['error'] = __( 'File is empty', 'wc-afip' );
			return $response;
		}

		if ( 'File size is too big' === $response['error'] ) {
			$response['error'] = __( 'File size is too big', 'wc-afip' );
			return $response;
		}

		if ( 'Invalid logo format' === $response['error'] ) {
			$response['error'] = __( 'Invalid logo format', 'wc-afip' );
			return $response;
		}

		if ( 'No cert subject found' === $response['error'] ) {
			$response['error'] = __( 'No cert subject found', 'wc-afip' );
			return $response;
		}

		if ( 'Invalid cert subject' === $response['error'] ) {
			$response['error'] = __( 'Invalid cert subject', 'wc-afip' );
			return $response;
		}

		if ( 'Invalid cert cuit' === $response['error'] ) {
			$response['error'] = __( 'Invalid cert cuit', 'wc-afip' );
			return $response;
		}

		if ( 'Invalid cert for production environment' === $response['error'] ) {
			$response['error'] = __( 'Invalid cert for production environment', 'wc-afip' );
			return $response;
		}

		if ( 'Invalid cert for testing environment' === $response['error'] ) {
			$response['error'] = __( 'Invalid cert for testing environment', 'wc-afip' );
			return $response;
		}

		return $response;
	}
}
