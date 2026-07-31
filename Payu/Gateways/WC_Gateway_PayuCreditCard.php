<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuCreditCard extends WC_Payu_Gateways {
	protected string $paytype = 'c';

	public function __construct() {
		parent::__construct( 'payucreditcard' );

		$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/card-visa-mc.svg', PAYU_PLUGIN_FILE ) );
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}
}
