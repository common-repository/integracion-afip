<?php

namespace CRPlugins\Afip\Checkout;

use CRPlugins\Afip\Helper\Helper;

defined( 'ABSPATH' ) || exit;

class CheckoutModifier {
	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'checkout_block_fields' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'on_order_checkout' ) );
	}

	public function checkout_block_fields(): void {
		if ( ! empty( Helper::get_option( 'document_number_selector' ) ) ) {
			return;
		}

		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'         => 'crplugins/afip_document_number',
				'label'      => Helper::get_option( 'document_number_field_name', 'Número de documento' ),
				'location'   => 'address',
				'required'   => true,
				'type'       => 'text',
				'attributes' => array(
					'pattern' => '[0-9]+', // Only numbers
				),
			)
		);

		do_action( 'wc_afip_blocks_checkout_modified' );
	}

	public function checkout_fields( array $fields ): array {
		if ( ! empty( Helper::get_option( 'document_number_selector' ) ) ) {
			return $fields;
		}

		$fields['billing']['crp_afip_document_number'] = array(
			'type'     => 'number',
			'label'    => Helper::get_option( 'document_number_field_name', 'Número de documento' ),
			'required' => true,
			'priority' => 35,
			'class'    => array( 'form-row-wide' ),
			'clear'    => true,
			'default'  => Helper::get_customer_document_number(),
		);

		do_action( 'wc_afip_legacy_checkout_modified', $fields );

		return $fields;
	}

	public function on_order_checkout( int $order_id ): void {
		$selector = Helper::get_option( 'document_number_selector' );
		if ( empty( $selector ) ) {
			$selector = 'crp_afip_document_number';
		}

		if ( ! isset( $_POST[ $selector ] ) ) {
			return;
		}

		$order    = wc_get_order( $order_id );
		$document = sanitize_text_field( wp_unslash( $_POST[ $selector ] ) );

		$order->update_meta_data( 'afip_document_number', $document );

		$user = $order->get_user();
		if ( $user ) {
			update_user_meta( $user->ID, 'wc_afip_document_number', $document );
		}

		$order->save();
	}
}
