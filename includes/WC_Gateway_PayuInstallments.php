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
                } else {
                    wp_enqueue_style('payu-installments-widget', plugins_url( '/assets/css/payu-installments-widget.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION);
                    add_filter('woocommerce_gateway_description', [$this, 'installments_filter_gateway_description'], 10, 2);
                }
            }
        }
    }

    public function installments_filter_gateway_description($description, $id)
    {
        if ($this->should_display_installments_widget($id)) {
            wp_enqueue_script('payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION);
            wp_enqueue_script('payu-installments-widget-checkout', plugins_url( '/assets/js/payu-installments-widget-checkout.js', PAYU_PLUGIN_FILE ), ['jquery', 'payu-installments-widget'], PAYU_PLUGIN_VERSION);
            $posId = $this->pos_id;
            $widgetKey = $this->pos_widget_key;
            $priceTotal = WC()->cart->get_total('');
            $transformedDescription =
                $description.
                '<script type="text/javascript">'.
                'var PayUInstallmentsWidgetCartData = {'.
                '	priceTotal: '.$priceTotal.','.
                '   posId: \''.$posId.'\','.
                '   widgetKey: \''.$widgetKey.'\''.
                '}'.
                '</script>';
            return $transformedDescription;
        }

        return $description;
    }

    function should_display_installments_widget($id)
    {
        return $id === 'payuinstallments' &&
            get_option('woocommerce_payuinstallments_settings')['credit_widget_on_checkout_page'] === 'yes' &&
            get_woocommerce_currency() === 'PLN';
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $this->agreements_field();
    }

    protected function get_additional_gateway_fields()
    {
        return [
            'credit_widget_on_listings' => [
                'title' => __('Installments widget', 'woo-payu-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enabled on product listings', 'woo-payu-payment-gateway'),
                'default' => 'yes'
            ],
            'credit_widget_on_product_page' => [
                'title' => __('Installments widget', 'woo-payu-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enabled on product page', 'woo-payu-payment-gateway'),
                'default' => 'yes'
            ],
            'credit_widget_on_cart_page' => [
                'title' => __('Installments widget', 'woo-payu-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enabled on cart page', 'woo-payu-payment-gateway'),
                'default' => 'yes'
            ],
            'credit_widget_on_checkout_page' => [
                'title' => __('Installments widget', 'woo-payu-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enabled on checkout page', 'woo-payu-payment-gateway'),
                'default' => 'yes'
            ],
        ];
    }
}