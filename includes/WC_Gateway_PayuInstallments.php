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
                    add_filter( 'woocommerce_gateway_title', [ $this, 'installments_filter_gateway_title' ], 10, 2);
                }
            }
        }
    }

    public function installments_filter_gateway_title( $title, $id ) {
        if ($id === 'payuinstallments') {
            $posId = $this->pos_id;
            $widgetKey = $this->pos_widget_key;
            $priceTotal = WC()->cart->total;
            $transformedTitle =
                '<div class="wc-payu-installments-widget-cart">'.$title.
                '<div id="installment-mini-cart"></div>'.
                '<script type="text/javascript">'.
                '    var value = '.$priceTotal.';'.
                '    if (value >= 300 && value <= 50000) {'.
                '        var options = {'.
                '            creditAmount: value,'.
                '            posId: \''.$posId.'\','.
                '            key: \''.$widgetKey.'\','.
                '            showLongDescription: true'.
                '       };'.
                '        OpenPayU.Installments.miniInstallment(\'#installment-mini-cart\', options);'.
                '    }'.
                '</script>'.
                '</div>';
            return $transformedTitle;
        }

        return $title;
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $this->agreements_field();
    }
}