<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuInstallments extends WC_Payu_Gateways implements WC_PayuCreditGateway {
	protected string $paytype = 'ai';

	function __construct() {
		parent::__construct( 'payuinstallments' );
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() || ! parent::is_available() ) {
			return false;
		}
		return true;
	}

	public function get_available_paymethods() {
		$response   = $this->payu_get_paymethods();
		$payMethods = [];
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' && $response->getResponse()->payByLinks ) {
			$pay_by_links = $response->getResponse()->payByLinks;
			$payMethods   = array_map( fn( $paymethod ) => $paymethod->value, $pay_by_links );
		}

		return $payMethods;
	}

	public function get_related_paytypes(): array {
		return [ $this->paytype ];
	}

}
