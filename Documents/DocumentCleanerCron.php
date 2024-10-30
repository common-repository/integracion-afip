<?php

namespace CRPlugins\Afip\Documents;

use CRPlugins\Afip\Helper\Helper;

defined( 'ABSPATH' ) || exit;

class DocumentCleanerCron {

	private const CRON_NAME = 'afip_remove_old_documents_cron';

	public function __construct() {
		add_action( self::CRON_NAME, array( $this, 'remove_old_documents_cron_func' ) );

		$this->create_cron();
	}

	public function remove_old_documents_cron_func(): void {

		$time = (int) Helper::get_option( 'invoice_delete_cron_time', 7890000 ); // 3 months in secs

		$total_deleted  = 0;
		$total_deleted += $this->delete_in_invoices_folder( Helper::get_invoice_folder_path() . '/*.pdf', $time );
		$total_deleted += $this->delete_in_invoices_folder( Helper::get_credit_note_folder_path() . '/*.pdf', $time );
		$total_deleted += $this->delete_in_invoices_folder( Helper::get_invoice_folder_path() . '/*.zip', 1 ); // Don't preserve zips

		Helper::log_info( sprintf( 'Cron ran and deleted %d invoices older than %s secs', $total_deleted, $time ) );
	}

	protected function delete_in_invoices_folder( string $pattern, int $older_than ): int {
		$now   = time();
		$i     = 0;
		$files = glob( $pattern );

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( $now - filemtime( $file ) >= $older_than ) {
					unlink( $file ); // phpcs:ignore
					++$i;
				}
			}
		}

		return $i;
	}

	public function create_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_NAME ) ) {
			wp_schedule_event( ( new \DateTime() )->modify( '+24 hours' )->getTimestamp(), 'daily', self::CRON_NAME );
		}
	}
}
