<?php

class WC_Gateway_PayuBlik extends WC_PayUGateways
{
    protected $paytype = 'blik';

    function __construct()
    {
        parent::__construct('payublik');

        if ($this->is_enabled()) {
            $this->show_terms_info = true;
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/blik.svg', PAYU_PLUGIN_FILE ));

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
