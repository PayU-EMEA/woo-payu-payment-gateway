<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuStandard extends WC_Payu_Gateways {

	function __construct() {
		parent::__construct( 'payustandard' );
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}

	public function try_retrieve_banks(): bool {
		$response = $this->payu_get_paymethods();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$payMethods = $response->getResponse();

			return ! empty( $payMethods->payByLinks );
		}

		return false;
	}
}
