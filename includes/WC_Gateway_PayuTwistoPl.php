<?php

use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;

class WC_Gateway_PayuTwistoPl extends WC_Payu_Gateways {
	protected $paytype = 'dpt';

	function __construct() {
		parent::__construct( 'payutwistopl' );

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/twisto-pl.svg', PAYU_PLUGIN_FILE ) );

			if ( ! is_admin() ) {
				if ( ! $this->try_retrieve_banks() ) {
					add_filter( 'woocommerce_available_payment_gateways', [ $this, 'unset_gateway' ] );
				}
			}
		}
	}

	public function payment_fields() {
		parent::payment_fields();
		$this->agreements_field();
	}
}
