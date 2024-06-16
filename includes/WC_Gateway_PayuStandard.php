<?php

use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;

class WC_Gateway_PayuStandard extends WC_Payu_Gateways {

	function __construct() {
		parent::__construct( 'payustandard' );
	}

	public function is_available() {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}

	public function try_retrieve_banks(): bool {
		$response = $this->get_payu_response();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$payMethods = $response->getResponse();

			return ! empty( $payMethods->payByLinks );
		}

		return false;
	}

	public function payment_fields() {
		parent::payment_fields();
		$this->agreements_field();
	}
}
