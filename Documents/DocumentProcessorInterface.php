<?php

namespace CRPlugins\Afip\Documents;

use WC_Order;

defined( 'ABSPATH' ) || exit;

interface DocumentProcessorInterface {
	public function get_file_path( WC_Order $order );
	public function get_file_uri( string $file_name );
	public function get_file_name( WC_Order $order );
	public function get_remote_content( WC_Order $order );
	public function get_local_content( WC_Order $order ): string;
	public function file_exists( WC_Order $order ): bool;
	public function create_file_from_base64( string $base64_data, WC_Order $order ): void;
}
