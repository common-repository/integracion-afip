<?php

namespace CRPlugins\Afip\Helper;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use CRPlugins\Afip\Enums\DocumentType;
use CRPlugins\Afip\ValueObjects\Seller;
use WC_Customer;
use WC_Order;

class Helper {
	use NoticesTrait;
	use SettingsTrait;
	use WooCommerceTrait;
	use LoggerTrait;
	use AssetsTrait;

	const INVOICE_META_KEY            = 'afip_cae'; // keep for BW Compatibility
	const INVOICE_TYPE_META_KEY       = 'afip_invoice_type';
	const INVOICE_NUMBER_META_KEY     = 'afip_invoice_number';
	const CREDIT_NOTE_META_KEY        = 'afip_credit_note_cae';
	const CREDIT_NOTE_NUMBER_META_KEY = 'afip_credit_note_number';

	public static function log_debug( string $msg ): void {
		if ( self::is_enabled( 'debug' ) ) {
			self::log_for_debug( $msg );
		}
	}

	public static function is_order_processed( WC_Order $order ): bool {
		return ! empty( $order->get_meta( self::INVOICE_META_KEY ) );
	}

	public static function get_customer_document_number(): string {
		$doc_number = '';
		$customer   = WC()->customer;

		if ( class_exists( Package::class ) && class_exists( CheckoutFields::class ) && $customer ) {
			// get document from order first
			/** @disregard */
			$checkout_fields = Package::container()->get( CheckoutFields::class );
			$doc_number      = $checkout_fields->get_field_from_object( 'crplugins/afip_document_number', $customer, 'billing' );
		}

		if ( empty( $doc_number ) ) {
			$doc_number = get_user_meta( wp_get_current_user()->ID, 'wc_afip_document_number', true );
		}

		return $doc_number;
	}

	public static function get_order_document_number( WC_Order $order ): string {
		// Get document from order meta, first data source.
		$doc_number = (string) $order->get_meta( 'afip_document_number' );

		if ( class_exists( Package::class ) && class_exists( CheckoutFields::class ) ) {
			// get document from order if it was placed with blocks checkout
			/** @disregard */
			$checkout_fields   = Package::container()->get( CheckoutFields::class );
			$blocks_doc_number = $checkout_fields->get_field_from_object( 'crplugins/afip_document_number', $order, 'billing' );

			if ( ! empty( $blocks_doc_number ) ) {
				$doc_number = $blocks_doc_number;
			}

			// If everything else fails...
			if ( empty( $doc_number ) ) {
				// get document from customer, it should be the same data as in the order, but there is a bug where the data might not be present in the order
				$customer   = new WC_Customer( $order->get_customer_id() );
				$doc_number = $checkout_fields->get_field_from_object( 'crplugins/afip_document_number', $customer, 'billing' );
			}
		}

		return $doc_number;
	}

	/**
	 * @return array{condition: string, document_type: int, document_number: string}
	 */
	public static function guess_customer_details( WC_Order $order ): array {
		$doc_number = self::get_order_document_number( $order );

		if ( strlen( $doc_number ) >= 10 ) {
			$doc_type  = DocumentType::CUIT;
			$condition = 'IVA Responsable Inscripto';
		} else {
			$doc_type  = DocumentType::DNI;
			$condition = 'Consumidor Final';
		}

		return array(
			'condition'       => $condition,
			'document_type'   => $doc_type,
			'document_number' => $doc_number,
		);
	}

	public static function get_seller(): Seller {
		$settings = array(
			'name'          => self::get_option( 'name' ),
			'fantasy_name'  => self::get_option( 'fantasy_name' ),
			'cuit'          => self::get_option( 'cuit' ),
			'point_of_sale' => self::get_option( 'point_of_sale' ),
			'condition'     => self::get_option( 'condition' ),
			'tax_condition' => self::get_option( 'tax_condition' ),
			'start_date'    => self::get_option( 'start_date' ),
			'address'       => self::get_option( 'address' ),
		);

		/**
		 * @var array{
		 *  name: string,
		 *  fantasy_name: string,
		 *  cuit: string,
		 *  point_of_sale: string,
		 *  condition: string,
		 *  tax_condition: string,
		 *  start_date: string,
		 *  address: string,
		 * } $settings
		 */

		return new Seller(
			$settings['name'],
			$settings['fantasy_name'],
			$settings['cuit'],
			$settings['point_of_sale'],
			$settings['condition'],
			$settings['tax_condition'],
			$settings['start_date'],
			$settings['address']
		);
	}

	/**
	 * @return array{
	 *  apikey: string,
	 *  name: string,
	 *  fantasy_name: string,
	 *  point_of_sale: string,
	 *  cuit: string,
	 *  address: string,
	 *  condition: string,
	 *  tax_condition: string,
	 *  start_date: string,
	 *  invoice_type: string,
	 *  sale_term: string,
	 *  unit: string,
	 *  product_type: string,
	 *  tax_percentage: string,
	 *  tax_classes_mapping: string[],
	 *  status_processing: string,
	 *  company_over_name: string,
	 *  send_invoice_mail: string,
	 *  invoice_mail_subject: string,
	 *  invoice_mail_body: string,
	 *  send_credit_note_mail: string,
	 *  credit_note_mail_subject: string,
	 *  credit_note_mail_body: string,
	 *  document_number_selector: string,
	 *  document_number_field_name: string,
	 *  environment: string,
	 *  label_delete_cron_time: string,
	 *  debug: string,
	 * }
	 */
	public static function get_all_settings(): array {
		return array(
			'apikey'                      => self::get_option( 'apikey', '' ),
			'name'                        => self::get_option( 'name', '' ),
			'fantasy_name'                => self::get_option( 'fantasy_name', '' ),
			'point_of_sale'               => self::get_option( 'point_of_sale', '' ),
			'cuit'                        => self::get_option( 'cuit', '' ),
			'address'                     => self::get_option( 'address', '' ),
			'condition'                   => self::get_option( 'condition', '' ),
			'tax_condition'               => self::get_option( 'tax_condition', '' ),
			'start_date'                  => self::get_option( 'start_date', '' ),
			'invoice_legend'              => self::get_option( 'invoice_legend', '' ),
			'invoice_type'                => self::get_option( 'invoice_type', '0' ),
			'sale_term'                   => self::get_option( 'sale_term', 'Contado' ),
			'unit'                        => self::get_option( 'unit', '' ),
			'product_type'                => self::get_option( 'product_type', '1' ),
			'tax_percentage'              => self::get_option( 'tax_percentage', '5' ),
			'tax_classes_mapping'         => self::get_option( 'tax_classes_mapping', array() ),
			'status_processing'           => self::get_option( 'status_processing', '0' ),
			'company_over_name'           => self::get_option( 'company_over_name', 'false' ),
			'send_invoice_mail'           => self::get_option( 'send_invoice_mail', 'true' ),
			'invoice_mail_subject'        => self::get_option( 'invoice_mail_subject', 'Tu factura de {{sitio}} - Orden #{{orden}}' ),
			'invoice_mail_body'           => self::get_option( 'invoice_mail_body', 'Aquí está tu factura de la orden {{orden}}' ),
			'send_credit_note_mail'       => self::get_option( 'send_credit_note_mail', 'true' ),
			'credit_note_mail_subject'    => self::get_option( 'credit_note_mail_subject', 'Tu nota de crédito de {{sitio}} - Orden #{{orden}}' ),
			'credit_note_mail_body'       => self::get_option( 'credit_note_mail_body', 'Aquí está tu nota de crédito de la orden {{orden}}' ),
			'document_number_selector'    => self::get_option( 'document_number_selector', '' ),
			'document_number_field_name'  => self::get_option( 'document_number_field_name', 'Número de documento' ),
			'invoice_one_item_name'       => self::get_option( 'invoice_one_item_name', '' ),
			'invoice_one_item_percentage' => self::get_option( 'invoice_one_item_percentage', 1 ),
			'environment'                 => self::get_option( 'environment', 'testing' ),
			'label_delete_cron_time'      => self::get_option( 'label_delete_cron_time', '7890000' ),
			'debug'                       => self::get_option( 'debug', '' ),
		);
	}

	public static function str_contains( string $haystack, string $needle ): bool {
		return ( '' === $needle || false !== strpos( $haystack, $needle ) );
	}

	public static function str_starts_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( $haystack, $needle );
	}

	public static function str_ends_with( string $haystack, string $needle ): bool {
		if ( '' === $haystack && '' !== $needle ) {
			return false;
		}

		$len = strlen( $needle );

		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}
}
