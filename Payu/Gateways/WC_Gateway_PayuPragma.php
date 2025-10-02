<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuPragma extends WC_Payu_Gateways implements WC_PayuCreditGateway {

    protected string $paytype = 'ppf';

    function __construct() {
        parent::__construct( 'payupragma' );

        if ( $this->is_enabled() ) {
            $this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/pragmapay.svg', PAYU_PLUGIN_FILE ) );
        }
    }

    public function is_available(): bool {
        if ( ! $this->try_retrieve_banks() ) {
            return false;
        }

        return parent::is_available();
    }

    public function get_related_paytypes(): array {
        return [ $this->paytype ];
    }
}
