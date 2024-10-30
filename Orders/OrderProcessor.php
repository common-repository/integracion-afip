<?php

namespace CRPlugins\Afip\Orders;

use CRPlugins\Afip\Documents\DocumentProcessorInterface;
use CRPlugins\Afip\Emails\CustomerCreditNoteMail;
use CRPlugins\Afip\Emails\CustomerInvoiceMail;
use CRPlugins\Afip\Enums\DocumentType;
use CRPlugins\Afip\Enums\InvoiceType;
use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Sdk\AfipSdk;
use CRPlugins\Afip\ValueObjects\Customer;
use CRPlugins\Afip\ValueObjects\Document;
use CRPlugins\Afip\ValueObjects\Invoice;
use CRPlugins\Afip\ValueObjects\Item;
use CRPlugins\Afip\ValueObjects\Items;
use WC_Order;

class OrderProcessor {

	/**
	 * @var AfipSdk
	 */
	private $sdk;

	/**
	 * @var DocumentProcessorInterface
	 */
	private $invoice_processor;

	/**
	 * @var DocumentProcessorInterface
	 */
	private $credit_note_processor;

	public function __construct(
		AfipSdk $sdk,
		DocumentProcessorInterface $invoice_processor,
		DocumentProcessorInterface $credit_note_processor
	) {
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status' ), 10, 4 );

		$this->sdk                   = $sdk;
		$this->invoice_processor     = $invoice_processor;
		$this->credit_note_processor = $credit_note_processor;
	}

	public function handle_order_status( int $order_id, string $status_from, string $status_to, WC_Order $order ) {
		$config_status = Helper::get_option( 'status_processing' );
		$config_status = str_replace( 'wc-', '', $config_status );

		if ( $order->has_status( $config_status ) && empty( $order->get_meta( Helper::INVOICE_META_KEY ) ) ) {
			$this->process_order( $order );
		}
	}

	/**
	 * @param array{customer_name?: string,
	 *  name?: string,
	 *  address?: string,
	 *  condition?: string,
	 *  document_type?: string
	 *  document_number?: string
	 *  invoice_type?: string
	 * } $customer_details
	 */
	public function process_order( WC_Order $order, array $customer_details = array() ): void {

		// From
		$seller = Helper::get_seller();
		if ( empty( $seller->get_name() ) ) {
			Helper::add_error( __( 'Fill your settings first before creating an invoice', 'wc-afip' ) );
			Helper::log_error( __( 'Fill your settings first before creating an invoice', 'wc-afip' ) );
			return;
		}

		if ( empty( $customer_details ) ) {
			$customer_details = Helper::guess_customer_details( $order );
		}

		$invoice = new Invoice(
			Helper::get_option( 'invoice_type' ),
			Helper::get_option( 'sale_term' ),
			Helper::get_option( 'unit' ),
			Helper::get_option( 'product_type' ),
			Helper::get_option( 'tax_percentage' )
		);

		if ( InvoiceType::AUTOMATIC === $invoice->get_type() ) {
			$invoice->set_type( DocumentType::DNI === $customer_details['document_type'] ? InvoiceType::B : InvoiceType::A );
		}

		if ( isset( $customer_details['invoice_type'] ) ) {
			$invoice->set_type( $customer_details['invoice_type'] );
		}

		// To
		$customer = Helper::get_order_customer( $order );

		$customer->set_condition( $customer_details['condition'] );
		$customer->set_document( new Document( $customer_details['document_type'], $customer_details['document_number'] ) );

		if ( isset( $customer_details['name'] ) ) {
			$customer->set_first_name( $customer_details['name'] );
			$customer->set_last_name( '' );
		}

		if ( isset( $customer_details['address'] ) ) {
			$customer->get_address()->set_full_address( $customer_details['address'] );
		}

		// Items
		$total    = $order->get_total();
		$discount = $this->get_discount( $order );
		$items    = Helper::get_items_from_order( $order );
		if ( empty( $items->get() ) ) {
			Helper::add_error( __( 'Cannot create an invoice for an order with no items', 'wc-afip' ) );
			Helper::log_error( __( 'Cannot create an invoice for an order with no items', 'wc-afip' ) );
			return;
		}

		if ( empty( Helper::get_option( 'invoice_one_item_name' ) ) ) {
			$this->add_shipping_as_item( $order, $items );
			$this->add_extra_fees( $order, $items );
		} else {
			$item_price = ( $total - $discount ) * Helper::get_option( 'invoice_one_item_percentage', 1 );
			$item_name  = Helper::get_option( 'invoice_one_item_name' );

			if ( Helper::str_contains( $item_name, '{{primer_producto}}' ) ) {
				$first_item = $items->get_first();
				$item_name  = str_replace( '{{primer_producto}}', $first_item->get_name(), $item_name );
			}

			if ( Helper::str_contains( $item_name, '{{productos}}' ) ) {
				$calculated_name = '';
				foreach ( $items->get() as $item ) {
					$calculated_name .= sprintf( '%sx %s. ', $item->get_quantity(), $item->get_name() );
				}
				$item_name = str_replace( '{{productos}}', rtrim( $calculated_name, '. ' ), $item_name );
			}

			$items    = new Items( array( new Item( $item_price, $item_name, 1, 9999, '', 0, '' ) ) );
			$discount = 0;

			$total = $item_price;
		}
		$this->add_tax_type_to_items( $items );

		/** @var Items $items */
		$items = apply_filters( 'wc_afip_items_before_process', $items, $customer, $order );
		/** @var Customer $customer */
		$customer = apply_filters( 'wc_afip_customer_before_process', $customer, $items, $order );

		do_action( 'wc_afip_before_order_process', $order );

		$response = $this->sdk->process_order(
			$seller,
			$invoice,
			$customer,
			$items,
			$order,
			$total,
			$discount,
			Helper::get_option( 'invoice_legend', '' )
		);

		if ( empty( $response ) ) {
			Helper::add_error( __( 'There was an error creating the invoice for the order with AFIP, please try again', 'wc-afip' ) );
			return;
		}

		if ( isset( $response['error'] ) ) {
			Helper::add_error( $response['error'] );
			return;
		}

		$response = apply_filters( 'wc_afip_response_after_order_process', $response, $order );

		$order->update_meta_data( Helper::INVOICE_META_KEY, $response['externalId'] );
		$order->update_meta_data( Helper::INVOICE_TYPE_META_KEY, $invoice->get_type() );
		$order->update_meta_data( Helper::INVOICE_NUMBER_META_KEY, $response['number'] );
		$order->save();

		$order->add_order_note( sprintf( __( 'Order invoice created with AFIP, CAE: %s', 'wc-afip' ), $response['externalId'] ), 0 );
		Helper::add_success( sprintf( __( 'Invoice for order %1$s created with AFIP succesfully, CAE: %2$s', 'wc-afip' ), $order->get_id(), $response['externalId'] ) );

		$this->invoice_processor->create_file_from_base64( $response['pdf'], $order );

		if ( Helper::is_enabled( 'send_invoice_mail', true ) ) {
			$this->send_invoice_mail( $order );
		}

		do_action( 'wc_afip_after_order_process', $order );
	}

	public function create_credit_note( WC_Order $order ): void {
		$invoice_cae = $order->get_meta( Helper::INVOICE_META_KEY );
		if ( ! $invoice_cae ) {
			Helper::add_error( __( 'Could not create order\'s credit note because no invoice number was found', 'wc-afip' ) );
			return;
		}

		do_action( 'wc_afip_before_credit_note_creation', $order );

		$response = $this->sdk->create_credit_note( $invoice_cae );

		if ( empty( $response ) ) {
			Helper::add_error( __( 'There was an error creating the credit note for the order with AFIP, please try again', 'wc-afip' ) );
			return;
		}

		if ( isset( $response['error'] ) ) {
			Helper::add_error( $response['error'] );
			return;
		}

		$response = apply_filters( 'wc_afip_response_after_order_note_create', $response, $order );

		$order->update_meta_data( Helper::CREDIT_NOTE_META_KEY, $response['externalId'] );
		$order->update_meta_data( Helper::CREDIT_NOTE_NUMBER_META_KEY, $response['number'] );
		$order->save();

		$order->add_order_note( sprintf( __( 'Order credit note created with AFIP, CAE: %s', 'wc-afip' ), $response['externalId'] ), 0 );
		Helper::add_success( sprintf( __( 'Order %1$s credit note created with AFIP succesfully, CAE: %2$s', 'wc-afip' ), $order->get_id(), $response['externalId'] ) );

		$this->credit_note_processor->create_file_from_base64( $response['pdf'], $order );

		if ( Helper::is_enabled( 'send_credit_note_mail', true ) ) {
			$this->send_credit_note_mail( $order );
		}

		do_action( 'wc_afip_after_credit_note_creation', $order );
	}

	public function send_invoice_mail( WC_Order $order ): void {
		WC()->mailer(); // init mailer
		$mail = new CustomerInvoiceMail( $this->invoice_processor );
		$mail->send_email( $order );
	}

	public function send_credit_note_mail( WC_Order $order ): void {
		WC()->mailer(); // init mailer
		$mail = new CustomerCreditNoteMail( $this->credit_note_processor );
		$mail->send_email( $order );
	}

	protected function add_tax_type_to_items( Items $items ): void {
		$taxes_mapping = Helper::get_option( 'tax_classes_mapping', null );
		array_map(
			function ( Item $item ) use ( $taxes_mapping ) {
				$item->set_tax_type( $taxes_mapping ? $this->find_tax_type( $taxes_mapping, $item->get_tax_class() ) : null );
				return $item;
			},
			$items->get()
		);
	}

	protected function find_tax_type( array $taxes_mapping, $tax_class ): ?int {
		foreach ( $taxes_mapping as $mapping ) {
			if ( $mapping['class'] === $tax_class ) {
				return (int) $mapping['type'];
			}
		}

		return null;
	}

	protected function get_discount( WC_Order $order ): float {
		$discount = (float) $order->get_discount_total();

		foreach ( $order->get_fees() as $fee ) {
			$fee_amount = (float) $fee->get_total();

			// Negative fees are discounts, so only count those
			if ( $fee_amount > 0 ) {
				continue;
			}

			$discount += abs( $fee_amount ) + abs( (float) $fee->get_total_tax() );
		}

		return $discount;
	}

	protected function add_shipping_as_item( WC_Order $order, Items $items ): void {
		$shipping_methods = $order->get_shipping_methods();

		if ( empty( $shipping_methods ) ) {
			return;
		}

		$new_item = new Item( (float) current( $shipping_methods )->get_total(), 'Envío', 1, 9999, '', 0, '' );
		$items->add( $new_item );
	}

	protected function add_extra_fees( WC_Order $order, Items $items ): void {
		foreach ( $order->get_fees() as $fee ) {
			$fee_amount = (float) $fee->get_total();
			if ( $fee_amount < 0 ) {
				continue;
			}

			$fee_amount += (float) $fee->get_total_tax();
			$item        = new Item( $fee_amount, $fee->get_name(), 1, $fee->get_id(), '', 0.0, '' );
			$items->add( $item );
		}
	}
}
