<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway;

use Payu\PaymentGateway\Features\WC_Payu_Receive_Discard_Payment;
use Payu\PaymentGateway\Features\WC_Payu_Repay_In_Order_Actions;
use Payu\PaymentGateway\Features\WC_Payu_Waiting_Payu_Order_Status;

class WC_Payu {
	public static function init(): void {
		WC_Payu_Waiting_Payu_Order_Status::init();
		WC_Payu_Receive_Discard_Payment::init();
		WC_Payu_Repay_In_Order_Actions::init();
	}
}
