<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway;

use Payu\PaymentGateway\Features\WC_Payu_Receive_Discard_Payment;
use Payu\PaymentGateway\Features\WC_Payu_Repay_In_Order_Actions;
use Payu\PaymentGateway\Features\WC_Payu_Status_Retrieval_On_Thank_You;
use Payu\PaymentGateway\Features\WC_Payu_Waiting_Payu_Order_Status;

class WC_Payu {
	private const TEMPLATE_PATH = WC_PAYU_PLUGIN_PATH . 'templates/';

	public static function init(): void {
		WC_Payu_Waiting_Payu_Order_Status::init();
		WC_Payu_Receive_Discard_Payment::init();
		WC_Payu_Repay_In_Order_Actions::init();
		WC_Payu_Status_Retrieval_On_Thank_You::init();
	}

	public static function template(string $name, array $params = []): void {
		extract( $params );
		include self::TEMPLATE_PATH . $name . '.php';
	}
}
