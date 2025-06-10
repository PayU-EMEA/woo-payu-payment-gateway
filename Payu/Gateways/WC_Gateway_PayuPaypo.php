<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuPaypo extends WC_Payu_Gateways implements WC_PayuCreditGateway {
    private $available_paypo_paytypes;
	private array $related_paytypes = ['dpp', 'dppron'];

	function __construct() {
        parent::__construct( 'payupaypo' );

        $this->get_available_paypo_paytypes();
        $this->paytype = $this->available_paypo_paytypes[0] ?? '';

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/paypo.svg', PAYU_PLUGIN_FILE ) );
		}
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

        if ( ! $this->contains_only_one_related_paytype()) {
            return false;
        }

		return parent::is_available();
	}

    private function get_available_paypo_paytypes(): void {
        $this->available_paypo_paytypes = $this->filter_available_paytypes($this->related_paytypes);
    }

    private function contains_only_one_related_paytype(): bool {
        return count($this->available_paypo_paytypes) === 1;
    }

	public function get_related_paytypes(): array {
		return $this->related_paytypes;
	}

}
