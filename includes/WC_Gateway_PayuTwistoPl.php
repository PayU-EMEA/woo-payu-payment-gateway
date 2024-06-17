<?php

use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;

class WC_Gateway_PayuTwistoPl extends WC_Payu_Gateways {
	protected $paytype = 'dpt';

	function __construct() {
		parent::__construct( 'payutwistopl' );

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/twisto-pl.svg', PAYU_PLUGIN_FILE ) );
		}
	}

	public function is_available() {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}
	public function payment_fields() {
		parent::payment_fields();
		$this->agreements_field();
	}
}
