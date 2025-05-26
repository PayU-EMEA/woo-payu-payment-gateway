<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuKlarna extends WC_Payu_Gateways {
    protected string $paytype = '';

    private $available_klarna_paytypes;

    function __construct() {
        $this->get_available_klarna_paytypes();
        $this->paytype = $this->available_klarna_paytypes[0] ?? '';

        parent::__construct( 'payuklarna' );

        if ( $this->is_enabled() ) {
            $this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/klarna.svg', PAYU_PLUGIN_FILE ) );
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

    private function get_available_klarna_paytypes(): void {
        $related_paytypes = ['dpkl', 'dpklczk', 'dpklron', 'dpkleur', 'dpklhuf'];
        $this->available_klarna_paytypes = $this->get_related_paytypes($related_paytypes);
    }

    private function contains_only_one_related_paytype(): bool {
        return count($this->available_klarna_paytypes) === 1;
    }
}
