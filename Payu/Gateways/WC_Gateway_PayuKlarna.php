<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuKlarna extends WC_Payu_Gateways {
	protected string $paytype = 'dpkl';

	function __construct() {
		parent::__construct( 'payuklarna' );

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/klarna.svg', PAYU_PLUGIN_FILE ) );
		}
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}
}
