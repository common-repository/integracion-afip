<?php

namespace CRPlugins\Afip\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use CRPlugins\Afip\Enums\DocumentType;
use CRPlugins\Afip\Enums\InvoiceType;
use CRPlugins\Afip\Helper\Helper;
use WC_Order;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Metabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'create' ) );
	}

	public function create(): void {
		if ( class_exists( CustomOrdersTableController::class ) ) {
			$screen = wc_get_container()
				->get( CustomOrdersTableController::class )
				->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

			add_meta_box(
				'afip_metabox',
				'AFIP',
				array( $this, 'content' ),
				$screen,
				'side',
				'high'
			);
		} else {
			$order_types = wc_get_order_types( 'order-meta-boxes' );
			foreach ( $order_types as $order_type ) {
				add_meta_box(
					'afip_metabox',
					'AFIP',
					array( $this, 'content' ),
					$order_type,
					'side',
					'default'
				);
			}
		}
	}

	/**
	 * @param WP_Post|WC_Order $post_or_order_object
	 */
	public function content( $post_or_order_object ): void {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( ! $order ) {
			return;
		}

		wp_enqueue_style( 'wc-afip-general-css' );
		wp_enqueue_style( 'wc-afip-orders-css' );
		wp_enqueue_script( 'wc-afip-orders-js' );
		wp_localize_script(
			'wc-afip-orders-js',
			'wc_afip_settings',
			array(
				'order_id'        => $order->get_id(),
				'invoice_cae'     => $order->get_meta( Helper::INVOICE_META_KEY ),
				'credit_note_cae' => $order->get_meta( Helper::CREDIT_NOTE_META_KEY ),
			)
		);
		wp_localize_script(
			'wc-afip-orders-js',
			'wc_afip_translation_texts',
			array(
				'generic_error_try_again'                 => esc_html__( 'There was an error, please try again', 'wc-afip' ),
				'loading'                                 => esc_html__( 'Loading...', 'wc-afip' ),
				'mail_sent'                               => esc_html__( 'Mail sent', 'wc-afip' ),
				'afip_invoice'                            => esc_html__( 'AFIP Invoice', 'wc-afip' ),
				'afip_credit_note'                        => esc_html__( 'AFIP Credit Note', 'wc-afip' ),
				'credite_note_creation_confirmation_text' => esc_html__( 'This action will create a credit note to null the invoice, do you want to continue?', 'wc-afip' ),
			)
		);

		$status = 'unprocessed';
		if ( ! empty( $order->get_meta( Helper::CREDIT_NOTE_META_KEY ) ) ) {
			$status = 'canceled';
		} elseif ( ! empty( $order->get_meta( Helper::INVOICE_META_KEY ) ) ) {
			$status = 'processed';
		}

		switch ( $status ) {
			case 'unprocessed':
			default:
				$this->show_order_not_processed( $order );
				$this->show_process_button();
				break;

			case 'canceled':
				$this->show_order_canceled( $order );
				break;

			case 'processed':
				$this->show_order_processed( $order );
				$this->show_cancel_button();
				break;
		}
	}

	protected function show_order_canceled( WC_Order $order ): void {
		$invoice_cae     = $order->get_meta( Helper::INVOICE_META_KEY );
		$credit_note_cae = $order->get_meta( Helper::CREDIT_NOTE_META_KEY );

		printf(
			wp_kses(
				__( 'Credit note CAE number: <strong>%s</strong>', 'wc-afip' ),
				array(
					'strong' => array(),
				)
			),
			esc_html( $credit_note_cae )
		);
		echo '<br>';
		printf(
			wp_kses(
				__( 'Invoice CAE number: <strong>%s</strong>', 'wc-afip' ),
				array(
					'strong' => array(),
				)
			),
			esc_html( $invoice_cae )
		);
		echo '<a class="afip-button block" id="afip-view-invoice">' . esc_html__( 'View invoice', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-view-credit-note">' . esc_html__( 'View Credit Note', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-download-invoice">' . esc_html__( 'Download invoice', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-download-credit-note">' . esc_html__( 'Download Credit Note', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-send-invoice-mail">' . esc_html__( 'Resend invoice mail', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-send-credit-note-mail">' . esc_html__( 'Resend credit note mail', 'wc-afip' ) . '</a>';
	}

	protected function show_order_processed( WC_Order $order ): void {
		$invoice_cae = $order->get_meta( Helper::INVOICE_META_KEY );

		printf(
			wp_kses(
				__( 'The order invoice has been created, CAE number: <strong>%s</strong>', 'wc-afip' ),
				array(
					'strong' => array(),
				)
			),
			esc_html( $invoice_cae )
		);
		echo '<a class="afip-button block" id="afip-view-invoice">' . esc_html__( 'View invoice', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-download-invoice">' . esc_html__( 'Download invoice', 'wc-afip' ) . '</a>';
		echo '<a class="afip-button block" id="afip-send-invoice-mail">' . esc_html__( 'Resend invoice mail', 'wc-afip' ) . '</a>';
	}

	protected function show_order_not_processed( WC_Order $order ): void {
		$this->show_data_form( $order );

		echo '<p>';
		echo esc_html__( 'This order\'s invoice has not been created yet', 'wc-afip' );
		echo '</p>';

		// Status config.
		$config_status = Helper::get_option( 'status_processing' );
		if ( ! empty( $config_status ) ) {
			$statuses = wc_get_order_statuses();
			printf(
				wp_kses(
					__( 'The invoice will be created when the order status is <strong>%s</strong>', 'wc-afip' ),
					array( 'strong' => array() )
				),
				esc_html( $statuses[ $config_status ] )
			);
		}
	}

	protected function show_data_form( WC_Order $order ): void {
		$customer = Helper::get_order_customer( $order );

		$name = $customer->get_full_name();
		if ( Helper::is_enabled( 'company_over_name' ) ) {
			$name = ! empty( $customer->get_company_name() ) ? $customer->get_company_name() : $customer->get_full_name();
		}

		$address          = $customer->get_address()->get_full_address();
		$customer_details = Helper::guess_customer_details( $order );
		$invoice_type     = (int) Helper::get_option( 'invoice_type' );

		$conditions = array(
			'IVA Responsable Inscripto',
			'IVA Sujeto Exento',
			'Consumidor Final',
			'Responsable Monotributo',
			'Sujeto No Categorizado',
			'Proveedor del Exterior',
			'Cliente del Exterior',
			'IVA Liberado - Ley Nº 19.640',
			'Monotributista Social',
			'IVA No Alcanzado',
			'Monotributista Trabajador Independiente Promovido',
		);

		// If invoice type = automatic, use type B if dni, else A
		if ( InvoiceType::AUTOMATIC === $invoice_type ) {
			$invoice_type = DocumentType::DNI === $customer_details['document_type'] ? InvoiceType::B : InvoiceType::A;
		}

		if ( in_array( $invoice_type, array( InvoiceType::A, InvoiceType::B ), true ) ) {
			$invoice_types = array(
				InvoiceType::A => __( 'Factura A', 'wc-afip' ),
				InvoiceType::B => __( 'Factura B', 'wc-afip' ),
			);

			if ( DocumentType::DNI === $customer_details['document_type'] ) {
				$invoice_type = InvoiceType::B;
			}
		} else {
			$invoice_types = array(
				InvoiceType::C => __( 'Factura C', 'wc-afip' ),
			);
		}

		$document_types = array(
			80 => 'CUIT',
			96 => 'DNI',
			86 => 'CUIL',
			87 => 'CDI',
			89 => 'LE',
			90 => 'LC',
			91 => 'CI Extranjera',
			92 => 'En trámite',
			93 => 'Acta Nacimiento',
			95 => 'CI Bs. As. RNP',
			94 => 'Pasaporte',
			0  => 'CI Policía Federal',
			1  => 'CI Buenos Aires',
			2  => 'CI Catamarca',
			3  => 'CI Córdoba',
			4  => 'CI Corrientes',
			5  => 'CI Entre Ríos',
			6  => 'CI Jujuy',
			7  => 'CI Mendoza',
			8  => 'CI La Rioja',
			9  => 'CI Salta',
			10 => 'CI San Juan',
			11 => 'CI San Luis',
			12 => 'CI Santa Fe',
			13 => 'CI Santiago del Estero',
			14 => 'CI Tucumán',
			16 => 'CI Chaco',
			17 => 'CI Chubut',
			18 => 'CI Formosa',
			19 => 'CI Misiones',
			20 => 'CI Neuquén',
			21 => 'CI La Pampa',
			22 => 'CI Río Negro',
			23 => 'CI Santa Cruz',
			24 => 'CI Tierra del Fuego',
			99 => 'Doc. (Otro)',
		);

		printf( '<p>%s</p>', esc_html__( 'This data will be used for generating the invoice:', 'wc-afip' ) );
		echo '<div id="afip-override-data">';
		printf( '<label>%s</label>', esc_html__( 'Name', 'wc-afip' ) );
		printf( '<input type="text" name="afip-customer-name" value="%s" />', esc_html( $name ) );
		printf( '<label>%s</label>', esc_html__( 'Address', 'wc-afip' ) );
		printf( '<input type="text" name="afip-customer-adress" value="%s" />', esc_html( $address ) );

		printf( '<label>%s</label>', esc_html__( 'Condition', 'wc-afip' ) );
		printf( '<select name="afip-customer-condition">' );
		foreach ( $conditions as $condition ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_html( $condition ),
				$condition === $customer_details['condition'] ? ' selected' : '',
				esc_html( $condition )
			);
		}
		printf( '</select>' );

		printf( '<label>%s</label>', esc_html__( 'Document Type', 'wc-afip' ) );
		printf( '<select name="afip-customer-document-type">' );
		foreach ( $document_types as $type => $document_type ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_html( $type ),
				$type === $customer_details['document_type'] ? ' selected' : '',
				esc_html( $document_type )
			);
		}
		printf( '</select>' );

		printf( '<label>%s</label>', esc_html__( 'Document Number', 'wc-afip' ) );
		printf( '<input type="text" name="afip-customer-document-number" value="%s" />', esc_html( $customer_details['document_number'] ) );

		printf( '<label>%s</label>', esc_html__( 'Invoice type', 'wc-afip' ) );
		printf( '<select name="afip-customer-invoice-type">' );
		foreach ( $invoice_types as $type_key => $type_text ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_html( $type_key ),
				$type_key === $invoice_type ? ' selected' : '',
				esc_html( $type_text )
			);
		}
		printf( '</select>' );

		echo '</div>';
	}

	protected function show_cancel_button(): void {
		echo '<a class="afip-red-button block" id="afip-create-credit-note">' . esc_html__( 'Create credit note', 'wc-afip' ) . '</a>';
	}

	protected function show_process_button(): void {
		echo '<a class="afip-button block" target="_blank" id="afip_process_order">' . esc_html__( 'Create invoice', 'wc-afip' ) . '</a>';
	}
}
