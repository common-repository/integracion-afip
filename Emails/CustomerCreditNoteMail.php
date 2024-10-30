<?php

namespace CRPlugins\Afip\Emails;

use CRPlugins\Afip\Documents\DocumentProcessorInterface;
use CRPlugins\Afip\Helper\Helper;
use WC_Email;
use WC_Order;

defined( 'ABSPATH' ) || exit;

class CustomerCreditNoteMail extends WC_Email {
	/**
	 * @var DocumentProcessorInterface
	 */
	protected $processor;

	public function __construct( DocumentProcessorInterface $processor ) {
		$this->id             = 'afip_order_credit_note';
		$this->customer_email = true;
		$this->title          = __( 'AFIP Customer credit note', 'wc-afip' );
		$this->description    = __( 'Notification to the customer mentioning that an AFIP credit note has been created (credit note attached)', 'wc-afip' );
		$this->template_html  = Helper::locate_template( 'credit-note-email.php' );
		$this->placeholders   = array();
		$this->manual         = true;
		$this->processor      = $processor;

		parent::__construct();
	}

	public function send_email( WC_Order $order ) {
		$this->setup_locale();

		$email_subject = Helper::get_option( 'credit_note_mail_subject', 'Tu nota de crédito de {{sitio}} - Orden #{{orden}}' );

		$this->send(
			$order->get_billing_email(),
			$this->replace_tags( $email_subject, $order ),
			$this->get_mail_content( $order, $email_subject ),
			$this->get_headers(),
			array( $this->processor->get_file_path( $order ) )
		);

		$this->restore_locale();
	}

	public function get_mail_content( WC_Order $order, string $email_subject ): string {

		$email_body = Helper::get_option( 'credit_note_mail_body', 'Aquí está tu nota de crédito de la orden {{orden}}' );

		$email_heading      = $this->replace_tags( $email_subject, $order );
		$email_body         = nl2br( $this->replace_tags( $email_body, $order ) );
		$additional_content = $this->get_additional_content();
		$sent_to_admin      = false;
		$plain_text         = false;
		$email              = $this;

		ob_start();
		require_once Helper::locate_template( 'credit-note-email.php' );

		return ob_get_clean();
	}

	protected function replace_tags( string $msg, WC_Order $order ): string {
		$msg = str_replace( '{{nombre}}', $order->get_billing_first_name(), $msg );
		$msg = str_replace( '{{nombre_completo}}', $order->get_formatted_billing_full_name(), $msg );
		$msg = str_replace( '{{sitio}}', get_bloginfo( 'name' ), $msg );
		$msg = str_replace( '{{orden}}', $order->get_id(), $msg );

		return $msg;
	}
}
