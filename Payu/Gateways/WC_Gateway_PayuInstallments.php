<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuInstallments extends WC_Payu_Gateways {
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
}
