<?php

namespace CRPlugins\Afip\Documents;

use CRPlugins\Afip\Helper\Helper;
use WC_Order;

defined( 'ABSPATH' ) || exit;

class CreditNoteProcessor extends DocumentProcessor implements DocumentProcessorInterface {

	public function get_remote_content( WC_Order $order ): string {
		$cae = $order->get_meta( Helper::CREDIT_NOTE_META_KEY );
		if ( empty( $cae ) ) {
			return '';
		}

		$response = $this->sdk->get_invoice( $cae );
		return ! empty( $response['pdf'] ) ? $response['pdf'] : '';
	}

	public function get_file_path( WC_Order $order ): string {
		return $this->get_file_uri( $this->get_file_name( $order ) );
	}

	public function get_file_uri( string $file_name ): string {
		return sprintf( '%s/%s.pdf', Helper::get_credit_note_folder_path(), $file_name );
	}

	public function get_file_name( WC_Order $order ): string {
		return sprintf( __( 'order-credit-note-%d', 'wc-afip' ), $order->get_id() );
	}
}
