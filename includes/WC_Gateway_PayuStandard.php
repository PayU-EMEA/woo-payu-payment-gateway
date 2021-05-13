<?php

class WC_Gateway_PayuStandard extends WC_PayUGateways
{

    function __construct()
    {
        parent::__construct('payustandard');

        if ($this->is_enabled()) {
            $this->has_terms_checkbox = false;

            if (!is_admin()) {
                if (!$this->try_retrieve_banks()) {
                    add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function try_retrieve_banks()
    {
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $payMethods = $response->getResponse();

            return isset($payMethods->payByLinks);
        }

        return false;
    }

}
