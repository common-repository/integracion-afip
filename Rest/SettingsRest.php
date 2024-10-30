<?php

namespace CRPlugins\Afip\Rest;

use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Sdk\AfipSdk;
use CRPlugins\Afip\Settings\HealthChecker;
use WC_Tax;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;


class SettingsRest implements RestRouteInterface {

	/**
	 * @var string
	 */
	private $routes_namespace;

	/**
	 * @var AfipSdk
	 */
	private $sdk;

	/**
	 * @var HealthChecker
	 */
	private $health_checker;

	public function __construct(
		string $routes_namespace,
		AfipSdk $sdk,
		HealthChecker $health_checker
	) {
		$this->routes_namespace = $routes_namespace;
		$this->sdk              = $sdk;
		$this->health_checker   = $health_checker;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->routes_namespace,
			'/stores/mine',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_store' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/settings',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/settings',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_health_status' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/reports',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_reports' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/csr',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_csr' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/cert',
			array(
				'methods'             => 'POST', // WP doesn't recognize PUT + files. Use POST instead.
				'callback'            => array( $this, 'update_cert' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/logo',
			array(
				'methods'             => 'POST', // WP doesn't recognize PUT + files. Use POST instead.
				'callback'            => array( $this, 'update_logo' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/cert',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_cert' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			$this->routes_namespace,
			'/logo',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_logo' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	public function get_store( WP_REST_Request $request ): WP_REST_Response {
		$store = $this->sdk->get_store();
		if ( isset( $store['error'] ) ) {
			Helper::log_error( 'Error retrieving store from api: ' . wc_print_r( $store, true ) );
			return new WP_REST_Response( $store, 400 );
		}

		return new WP_REST_Response( $store );
	}

	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = Helper::get_all_settings();

		$settings['availableStatuses'] = array(
			array(
				'label' => 'Seleccionar',
				'value' => '0',
			),
		);
		$statuses                      = wc_get_order_statuses();
		foreach ( $statuses as $key => $status ) {
			$settings['availableStatuses'][] = array(
				'label' => $status,
				'value' => $key,
			);
		}

		$taxes = WC_Tax::get_tax_rate_classes();
		if ( ! in_array( '', $taxes, true ) ) { // Make sure "Standard rate" (empty class name) is present.
			array_unshift(
				$taxes,
				(object) array(
					'name'              => __( 'Standard tax', 'wc-afip' ),
					'slug'              => 'default',
					'tax_rate_class_id' => '',
				)
			);
		}

		$settings['availableTaxClasses'] = $taxes;
		$settings['logoUrl']             = Helper::get_logo_url();

		return new WP_REST_Response( $settings );
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		try {
			Validator::key_exists( $body, 'apikey' );
			Validator::key_exists( $body, 'name' );
			Validator::key_exists( $body, 'fantasy_name' );
			Validator::key_exists( $body, 'point_of_sale' );
			Validator::key_exists( $body, 'cuit' );
			Validator::key_exists( $body, 'address' );
			Validator::key_exists( $body, 'condition' );
			Validator::key_exists( $body, 'tax_condition' );
			Validator::key_exists( $body, 'start_date' );
			Validator::key_exists( $body, 'invoice_legend' );
			Validator::key_exists( $body, 'invoice_type' );
			Validator::key_exists( $body, 'sale_term' );
			Validator::key_exists( $body, 'unit' );
			Validator::key_exists( $body, 'product_type' );
			Validator::key_exists( $body, 'tax_percentage' );
			Validator::key_exists( $body, 'tax_classes_mapping' );
			Validator::key_exists( $body, 'status_processing' );
			Validator::key_exists( $body, 'company_over_name' );
			Validator::key_exists( $body, 'send_invoice_mail' );
			Validator::key_exists( $body, 'invoice_mail_subject' );
			Validator::key_exists( $body, 'invoice_mail_body' );
			Validator::key_exists( $body, 'send_credit_note_mail' );
			Validator::key_exists( $body, 'credit_note_mail_subject' );
			Validator::key_exists( $body, 'credit_note_mail_body' );
			Validator::key_exists( $body, 'document_number_selector' );
			Validator::key_exists( $body, 'document_number_field_name' );
			Validator::key_exists( $body, 'invoice_one_item_name' );
			Validator::key_exists( $body, 'invoice_one_item_percentage' );
			Validator::key_exists( $body, 'environment' );
			Validator::key_exists( $body, 'label_delete_cron_time' );
			Validator::key_exists( $body, 'debug' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$body['point_of_sale'] = ltrim( $body['point_of_sale'], '0' );

		$valid_options = array(
			'apikey',
			'name',
			'fantasy_name',
			'point_of_sale',
			'cuit',
			'address',
			'condition',
			'tax_condition',
			'start_date',
			'invoice_legend',
			'invoice_type',
			'sale_term',
			'unit',
			'product_type',
			'tax_percentage',
			'tax_classes_mapping',
			'status_processing',
			'company_over_name',
			'send_invoice_mail',
			'invoice_mail_subject',
			'invoice_mail_body',
			'send_credit_note_mail',
			'credit_note_mail_subject',
			'credit_note_mail_body',
			'document_number_selector',
			'document_number_field_name',
			'invoice_one_item_name',
			'invoice_one_item_percentage',
			'environment',
			'label_delete_cron_time',
			'debug',
		);

		foreach ( $valid_options as $option_key ) {
			Helper::save_option( $option_key, $body[ $option_key ] );
		}

		return new WP_REST_Response( null, 204 );
	}

	public function delete_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = Helper::get_all_settings();

		foreach ( array_keys( $settings ) as $key ) {
			Helper::delete_option( $key );
		}

		return new WP_REST_Response( null, 204 );
	}

	public function get_health_status( WP_REST_Request $request ): WP_REST_Response {
		$api              = $this->health_checker->get_api_status();
		$file_permissions = $this->health_checker->are_file_permissions_valid();

		return new WP_REST_Response(
			array(
				'api_status'       => $api,
				'file_permissions' => $file_permissions,
			)
		);
	}

	public function send_reports( WP_REST_Request $request ): WP_REST_Response {
		$uploads_dir = wp_get_upload_dir()['basedir'];

		// Logs.
		$logs_dir       = $uploads_dir . '/wc-logs';
		$all_logs       = scandir( $logs_dir );
		$available_logs = array_filter(
			$all_logs,
			function ( string $log ): bool {
				return 'woocommerce-afip' === strtolower( substr( $log, 0, 16 ) );
			}
		);

		if ( ! $available_logs ) {
			return new WP_REST_Response( array( 'error' => 'No se encontraron logs para enviar' ), 400 );
		}

		$files_to_send   = array();
		$last_report     = array_pop( $available_logs );
		$files_to_send[] = $logs_dir . '/' . $last_report;

		$previous_last_report = null;
		if ( $available_logs ) {
			$previous_last_report = array_pop( $available_logs );
			$files_to_send[]      = $logs_dir . '/' . $previous_last_report;
		}

		// Settings.
		$settings    = Helper::get_all_settings();
		$uploads_dir = Helper::get_uploads_dir();
		if ( ! file_exists( $uploads_dir ) ) {
			mkdir( $uploads_dir, 0755 ); // phpcs:ignore
		}
		$settings_file = $uploads_dir . '/settings.txt';
		file_put_contents( $settings_file, json_encode( $settings ) ); // phpcs:ignore
		$files_to_send[] = $settings_file;

		// PHP errors.
		$errors     = array_filter(
			$all_logs,
			function ( string $log ): bool {
				return 'fatal-errors-' === strtolower( substr( $log, 0, 13 ) );
			}
		);
		$last_error = null;
		if ( $errors ) {
			$last_error      = array_pop( $errors );
			$files_to_send[] = $logs_dir . '/' . $last_error;
		}

		$response = $this->sdk->send_reports( $files_to_send );
		if ( empty( $response ) || isset( $response['error'] ) ) {
			return new WP_REST_Response( array( 'error' => 'No se pudo enviar el reporte' ), 400 );
		}

		Helper::log_info( 'Logging report has been sent' );

		try {
			unlink( $settings_file ); // phpcs:ignore
		} catch ( \Throwable $th ) {
			Helper::log_warning( 'Could not delete settings file from report' );
		}

		return new WP_REST_Response( null, 204 );
	}

	public function get_csr( WP_REST_Request $request ): WP_REST_Response {
		$query = $request->get_query_params();
		try {
			Validator::key_exists( $query, 'cuit' );
			Validator::not_empty( $query['cuit'], 'Se debe proveer un CUIT' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$csr = $this->sdk->get_csr( $query['cuit'] );
		if ( ! $csr || isset( $csr['error'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Could not retrieve csr' ), 400 );
		}

		return new WP_REST_Response( $csr );
	}

	public function update_cert( WP_REST_Request $request ): WP_REST_Response {
		$files = $request->get_file_params();
		try {
			Validator::key_exists( $files, 'cert' );
			Validator::not_empty( $files['cert'], 'Se debe proveer un certificado' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$cert = $this->sdk->upload_cert( $files['cert']['tmp_name'] );
		if ( ! $cert || isset( $cert['error'] ) ) {
			return new WP_REST_Response( array( 'error' => isset( $cert['error'] ) ? $cert['error'] : 'Could not upload cert' ), 400 );
		}

		return new WP_REST_Response( null, 204 );
	}

	public function update_logo( WP_REST_Request $request ): WP_REST_Response {
		$files = $request->get_file_params();
		try {
			Validator::key_exists( $files, 'logo' );
			Validator::not_empty( $files['logo'], 'Se debe proveer un logo' );
		} catch ( \Throwable $th ) {
			return new WP_REST_Response( array( 'error' => $th->getMessage() ), 400 );
		}

		$logo = $this->sdk->upload_logo( $files['logo']['tmp_name'], pathinfo( $files['logo']['name'], PATHINFO_EXTENSION ) );
		if ( ! $logo || isset( $logo['error'] ) ) {
			return new WP_REST_Response( array( 'error' => isset( $logo['error'] ) ? $logo['error'] : 'Could not upload logo' ), 400 );
		}

		$uploads_dir = Helper::get_uploads_dir();
		if ( ! file_exists( $uploads_dir ) ) {
			mkdir( $uploads_dir, 0755 ); // phpcs:ignore
		}

		Helper::delete_logo();

		$local_logo = sprintf( '%s/logo.%s', $uploads_dir, pathinfo( $files['logo']['name'], PATHINFO_EXTENSION ) );
		copy( $files['logo']['tmp_name'], $local_logo );

		Helper::log_info( 'Logo uploaded: ' . $local_logo );

		return new WP_REST_Response( null, 204 );
	}

	public function delete_cert( WP_REST_Request $request ): WP_REST_Response {
		$response = $this->sdk->delete_cert();
		if ( ! $response || isset( $response['error'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Could not delete cert' ), 400 );
		}

		return new WP_REST_Response( null, 204 );
	}

	public function delete_logo( WP_REST_Request $request ): WP_REST_Response {
		$response = $this->sdk->delete_logo();
		if ( ! $response || isset( $response['error'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Could not delete logo' ), 400 );
		}

		Helper::delete_logo();

		return new WP_REST_Response( null, 204 );
	}
}
