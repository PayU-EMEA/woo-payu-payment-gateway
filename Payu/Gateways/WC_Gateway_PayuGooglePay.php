<?php

namespace Payu\PaymentGateway\Gateways;

use OpenPayU_Configuration;

class WC_Gateway_PayuGooglePay extends WC_Payu_Gateways
{
    protected string $paytype = 'ap';

    function __construct()
    {
        parent::__construct('payugooglepay');

        if ($this->is_enabled()) {
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url('/assets/images/ap.svg', PAYU_PLUGIN_FILE));
            add_action('wp_enqueue_scripts', [$this, 'include_scripts']);
        }
    }

    public function is_available(): bool
    {
        if (! $this->try_retrieve_banks()) {
            return false;
        }

        return parent::is_available();
    }

    public function payment_fields(): void
    {
        ?>
        <ul class="payu-google-pay-error woocommerce-error" role="alert">
            <li><?php esc_html_e('This payment method is not available.', 'woo-payu-payment-gateway') ?></li>
        </ul>
        <?php
        parent::payment_fields();
        ?>
        <script>
            var payuGooglePayConfig = {
                currency: "<?php echo esc_attr(get_woocommerce_currency()) ?>",
                posId: "<?php echo esc_attr($this->pos_id) ?>",
                totalPrice: "<?php echo esc_attr(WC()->cart->get_total('')) ?>",
                env: "<?php echo $this->sandbox ? 'TEST' : 'PROD' ?>",
                merchantName: "<?php echo esc_attr(get_bloginfo( 'name' )) ?>"
            }
        </script>
        <input type="hidden" name="payu-google-token" id="payu-google-token" value="" />
        <?php
        $this->agreements_field();
    }
    public function get_additional_data(): array {
        $currency = get_woocommerce_currency();
        $totalPrice = WC()->cart->get_total('');
        $merchantName = get_bloginfo( 'name' );
		return [
			'posId'        => $this->pos_id,
            'currency'     => $currency,
            'totalPrice'   => $totalPrice,
            'env'          => $this->sandbox ? 'TEST' : 'PROD',
            'merchantName' => $merchantName,
		];
	}
    public function include_scripts(): void
    {
        wp_enqueue_script('google-pay', "https://pay.google.com/gp/p/js/pay.js", [], null);
    }

    protected function get_payu_pay_method(): array
    {
        $token = sanitize_text_field($_POST['payu-google-token']);

        return [
            'payMethod' => [
                'type'  => 'PBL',
                'value' => 'ap',
                'authorizationCode' => $token
            ]
        ];

        return $this->get_payu_pay_method_array('CARD_TOKEN', $token);
    }
}
