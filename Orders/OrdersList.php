<?php

namespace CRPlugins\Afip\Orders;

use CRPlugins\Afip\Helper\Helper;
use CRPlugins\Afip\Orders\OrderProcessor;
use WC_Order;

defined( 'ABSPATH' ) || exit;

class OrdersList {
	/**
	 * @var OrderProcessor
	 */
	private $order_processor;

	public function __construct( OrderProcessor $order_processor ) {
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_cae_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'cae_column_content' ), 10, 1 );

		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_cae_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'cae_column_content' ), 10, 2 );

		$this->order_processor = $order_processor;
	}

	/**
	 * @param array<string,string> $actions
	 * @return array<string,string>
	 */
	public function add_bulk_actions( array $actions ): array {

		wp_enqueue_style( 'wc-afip-general-css' );
		wp_enqueue_script( 'wc-afip-orders-list-js' );
		wp_localize_script(
			'wc-afip-orders-list-js',
			'wc_afip_settings',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'wc-afip' ),
			)
		);
		wp_localize_script(
			'wc-afip-orders-list-js',
			'wc_afip_translation_texts',
			array(
				'generic_error_try_again' => __( 'There was an error, please try again', 'wc-afip' ),
				'loading'                 => __( 'Loading...', 'wc-afip' ),
			)
		);

		$actions['wc_afip_bulk_download']   = __( 'AFIP - Download invoices', 'wc-afip' );
		$actions['wc_afip_bulk_process_cf'] = __( 'AFIP - Generate invoice', 'wc-afip' );

		return $actions;
	}

	/**
	 * @param string[] $ids
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $ids ): string {
		if ( 'wc_afip_bulk_process_cf' === $action ) {
			$this->handle_bulk_process( $ids );
		}

		return esc_url_raw( $redirect_to );
	}

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	public function add_cae_column( array $columns ): array {
		$columns['afip_cae_column'] = __( 'AFIP CAE', 'wc-afip' );
		return $columns;
	}

	public function cae_column_content( string $column, ?WC_Order $order = null ): void {
		global $post;

		if ( 'afip_cae_column' !== $column ) {
			return;
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			if ( ! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$order = wc_get_order( $post->ID );
			}
		} else {
			$order = wc_get_order( $post->ID );
		}
		if ( ! $order ) {
			return;
		}

		echo esc_html( $order->get_meta( Helper::INVOICE_META_KEY ) );
	}

	/**
	 * @param string[] $order_ids
	 */
	public function handle_bulk_process( array $order_ids ): void {
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! empty( $order->get_meta( Helper::INVOICE_META_KEY ) ) ) {
				Helper::add_success( sprintf( __( 'Invoice for order %s was already created with AFIP', 'wc-afip' ), $order_id ) );
				continue;
			}

			$this->order_processor->process_order( $order );
		}
	}
}
