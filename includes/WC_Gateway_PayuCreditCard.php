<?php

class WC_Gateway_PayuCreditCard extends WC_PayUGateways
{
    protected $paytype = 'c';

    function __construct()
    {
        parent::__construct('payucreditcard');

        if ($this->is_enabled()) {
            $this->show_terms_info = false;
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/card-visa-mc.svg', PAYU_PLUGIN_FILE ));

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

            return $payMethods->payByLinks && $this->process_pay_methods($payMethods->payByLinks);
        }

        return false;
    }

    /**
     * @param OpenPayU_Result $response
     *
     * @return bool
     */
    function retrieve_methods($response)
    {
        $payMethods = $response->getResponse();

        return $payMethods->payByLinks && $this->process_pay_methods($payMethods->payByLinks);
    }
}
