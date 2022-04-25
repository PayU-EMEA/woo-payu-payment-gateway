<?php

class WC_Gateway_PayuInstallments extends WC_PayUGateways
{
    protected $paytype = 'ai';

    function __construct()
    {
        parent::__construct('payuinstallments');

        if ($this->is_enabled()) {
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
