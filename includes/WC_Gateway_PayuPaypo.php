<?php

class WC_Gateway_PayuPaypo extends WC_PayUGateways
{
    protected $paytype = 'dpp';

    function __construct()
    {
        parent::__construct('payupaypo');

        if ($this->is_enabled()) {
            $this->show_terms_info = false;
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/paypo.svg', PAYU_PLUGIN_FILE ));

            if (!is_admin()) {
                if (!$this->try_retrieve_banks()) {
                    add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
                }
            }
        }
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $this->agreements_field();
    }
}
