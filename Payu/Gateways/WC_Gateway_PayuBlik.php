<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuBlik extends WC_Payu_Gateways {
	protected string $paytype = 'blik';

	function __construct() {
		parent::__construct( 'payublik' );

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/blik.svg', PAYU_PLUGIN_FILE ) );
		}
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}

	public function payment_fields(): void {
		parent::payment_fields();
		$this->agreements_field();
	}
}
