<?php

require_once 'lib/openpayu.php';
require_once 'OauthCacheWP.php';

class WC_Gateway_PayU extends WC_Payment_Gateway
{
    private $pluginVersion = '1.2.9';

    private $payu_feedback;
    private $sandbox;

    function __construct()
    {
        $this->id = 'payu';
        $this->has_fields = false;
        $this->method_title = __('PayU', 'payu');
        $this->method_description = __('Official PayU payment gateway for WooCommerce.', 'payu');
        $this->icon = apply_filters('woocommerce_payu_icon', 'https://static.payu.com/plugins/woocommerce_payu_logo.png');
        $this->supports = array(
            'products',
            'refunds'
        );

        $this->init_settings();
        $this->init_form_fields();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->payu_feedback = $this->get_option('payu_feedback');
        $this->sandbox = $this->get_option('sandbox');

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'gateway_ipn'));

        // Status change hook
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);

        $this->init_OpenPayU();
    }

    protected function init_OpenPayU($currency = null)
    {
        $isSandbox = 'yes' === $this->get_option('sandbox');

        if ($this->isWpmlActiveAndConfigure())
        {
            $optionSuffix = '_' . (null !== $currency ? $currency : get_woocommerce_currency());
        } else {
            $optionSuffix = '';
        }

        $optionPrefix = $isSandbox ? 'sandbox_' : '';

        OpenPayU_Configuration::setEnvironment($isSandbox ? 'sandbox' : 'secure');
        OpenPayU_Configuration::setMerchantPosId($this->get_option($optionPrefix . 'pos_id' . $optionSuffix));
        OpenPayU_Configuration::setSignatureKey($this->get_option($optionPrefix . 'md5' . $optionSuffix));
        OpenPayU_Configuration::setOauthClientId($this->get_option($optionPrefix . 'client_id' . $optionSuffix));
        OpenPayU_Configuration::setOauthClientSecret($this->get_option($optionPrefix . 'client_secret' . $optionSuffix));

        OpenPayU_Configuration::setOauthTokenCache(new OauthCacheWP());
        OpenPayU_Configuration::setSender('Wordpress ver ' . get_bloginfo('version') . ' / WooCommerce ver ' . WC()->version . ' / Plugin ver ' . $this->pluginVersion);
    }

    public function admin_options()
    {
        ?>

        <h3><?php echo $this->method_title; ?></h3>
        <p><?php echo $this->method_description; ?></p>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>

        <?php
    }

    function init_form_fields()
    {
        global $woocommerce_wpml;

        $currencies = [];

        if ($this->isWpmlActiveAndConfigure())
        {
            $currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
        }

        $this->form_fields = array_merge($this->getFormFieldsBasic(), $this->getFormFieldConfig($currencies), $this->getFormFieldInfo());
    }

    function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $billingData = $order->get_address();

        $orderData = array(
            'continueUrl' => $this->get_return_url($order),
            'notifyUrl' => add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/')),
            'customerIp' => $this->getIP(),
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => get_bloginfo('name') . ' #' . $order->get_order_number(),
            'currencyCode' => get_woocommerce_currency(),
            'totalAmount' => $this->toAmount($order->get_total()),
            'extOrderId' => uniqid($order_id . '_', true),
            'products' => array(
                array(
                    'name' => get_bloginfo('name') . ' #' . $order->get_order_number(),
                    'unitPrice' => $this->toAmount($order->get_total()),
                    'quantity' => 1
                )
            ),
            'buyer' => array(
                'email' => $billingData['email'],
                'phone' => $billingData['phone'],
                'firstName' => $billingData['first_name'],
                'lastName' => $billingData['last_name'],
                'language' => $this->getLanguage()
            )
        );

        try {
            $response = OpenPayU_Order::create($orderData);

            if ($response->getStatus() === OpenPayU_Order::SUCCESS) {

                $this->reduceStock($order);
                WC()->cart->empty_cart();

                $order->update_status( 'on-hold', __( 'Awaiting PayU payment.', 'payu' ) );

                add_post_meta($order_id, '_transaction_id', $response->getResponse()->orderId, true);

                return array(
                    'result' => 'success',
                    'redirect' => $response->getResponse()->redirectUri
                );
            } else {
                wc_add_notice(__('Payment error. Status code: ', 'payu') . $response->getStatus(), 'error');

                return false;
            }
        } catch (OpenPayU_Exception $e) {
            wc_add_notice(__('Payment error: ', 'payu') . $e->getMessage() . ' (' . $e->getCode() . ')', 'error');

            return false;
        }
    }

    function gateway_ipn()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = trim($body);

            $currency = $this->extractCurrencyFromNotification($data);

            if (null !== $currency) {
                $this->init_OpenPayU($currency);
            }

            try {
                $response = OpenPayU_Order::consumeNotification($data);
            } catch (Exception $e) {
                header('X-PHP-Response-Code: 500', true, 500);
                die($e->getMessage());
            }


            if (property_exists($response->getResponse(),'refund')) {
                $reportOutput = 'Refund notification - ignore|';
            } else {

                $order_id = (int)preg_replace('/_.*$/', '', $response->getResponse()->order->extOrderId);
                $status = $response->getResponse()->order->status;
                $transaction_id = $response->getResponse()->order->orderId;

                $reportOutput = 'OID: ' . $order_id . '|PS: ' . $status . '|TID: ' . $transaction_id . '|';

                $order = wc_get_order($order_id);

                $reportOutput .= 'WC AS: ' . $order->get_status() . '|';

                if ($order->get_status() !== 'completed' && $order->get_status() !== 'processing') {
                    switch ($status) {
                        case OpenPayuOrderStatus::STATUS_CANCELED:
                            $order->update_status('cancelled', __('Payment has been cancelled.', 'payu'));
                            break;

                        case OpenPayuOrderStatus::STATUS_REJECTED:
                            $order->update_status('failed', __('Payment has been rejected.', 'payu'));
                            break;

                        case OpenPayuOrderStatus::STATUS_COMPLETED:
                            $order->payment_complete($transaction_id);
                            break;

                        case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
                            $order->update_status(
                                'on-hold',
                                __('Payment has been put on hold - merchant must approve this payment manually.', 'payu')
                            );
                            break;
                    }
                }
                $reportOutput .= 'WC BS: ' . $order->get_status() . '|';
            }

            header("HTTP/1.1 200 OK");

            echo $reportOutput;
        }

        ob_flush();
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        $orderId = $order->get_transaction_id();

        if (empty($orderId)) {
            return false;
        }

        $this->init_OpenPayU($order->get_currency());

        $refund = OpenPayU_Refund::create(
            $orderId,
            __('Refund of: ', 'payu') . ' ' . $amount . $this->getOrderCurrency($order) . __(' for order: ', 'payu') . $order_id,
            $this->toAmount($amount)
        );

        return ($refund->getStatus() == 'SUCCESS');
    }

    public function change_status_action($order_id, $old_status, $new_status)
    {
        if ($this->payu_feedback == 'yes' && isset($_REQUEST['_wpnonce'])) {
            $order = wc_get_order($order_id);
            $orderId = $order->get_transaction_id();

            $this->init_OpenPayU($order->get_currency());

            if (empty($orderId)) {
                return false;
            }

            if ($old_status == 'on-hold' && ($new_status == 'processing' || $new_status == 'completed')) {
                $status_update = array(
                    "orderId" => $orderId,
                    "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                );

                OpenPayU_Order::statusUpdate($status_update);
            }

            if ($new_status == 'cancelled') {
                OpenPayU_Order::cancel($orderId);
            }
        }

    }

    /**
     * @param $value
     * @return int
     */
    private function toAmount($value)
    {
        return (int)round($value * 100);
    }

    /**
     * @return string
     */
    private function getLanguage()
    {
        return substr(get_locale(), 0, 2);
    }

    /**
     * @param WC_Order $order
     * @return string
     */
    private function getOrderCurrency($order)
    {
        return method_exists($order,'get_currency') ? $order->get_currency() : $order->get_order_currency();
    }

    /**
     * @param WC_Order $order
     */
    private function reduceStock($order)
    {
        function_exists('wc_reduce_stock_levels') ?
            wc_reduce_stock_levels($order->get_id()) : $order->reduce_order_stock();

    }

    /**
     * @param string $notification
     * @return null|string
     */
    private function extractCurrencyFromNotification($notification)
    {
        $notification = json_decode($notification);

        if (is_object($notification) && $notification->order && $notification->order->currencyCode) {
            return $notification->order->currencyCode;
        }
        return null;
    }

    /**
     * @return string
     */
    private function getIP()
    {
        return ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
    }

    /** @return bool */
    private function isWpmlActiveAndConfigure() {
        global $woocommerce_wpml;

        return $woocommerce_wpml
            && property_exists($woocommerce_wpml, 'multi_currency')
            && $woocommerce_wpml->multi_currency
            && count($woocommerce_wpml->multi_currency->get_currency_codes()) > 1;
    }

    /**
     * @return array
     */
    private function getFormFieldsBasic()
    {
        return array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => __('Enable PayU payment method', 'payu'),
                'type' => 'checkbox',
                'description' => __('If you do not already have PayU merchant account, <a href="https://secure.payu.com/boarding/#/form&pk_campaign=Plugin&pk_kwd=WooCommerce" target="_blank">please register in Production</a> or <a href="https://secure.snd.payu.com/boarding/#/form&pk_campaign=Plugin&pk_kwd=WooCommerce" target="_blank">please register in Sandbox</a>.', 'payu'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title:', 'payu'),
                'type' => 'text',
                'description' => __('Title of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
                'default' => __('PayU', 'payu'),
                'desc_tip' => true
            ),
            'sandbox' => array(
                'title' => __('Sandbox mode:', 'payu'),
                'type' => 'checkbox',
                'label' => __('Use sandbox environment.', 'payu'),
                'default' => 'no'
            ));
    }

    /**
     * @return array
     */
    private function getFormFieldInfo()
    {
        return array(
            'description' => array(
                'title' => __('Description:', 'payu'),
                'type' => 'text',
                'description' => __('Description of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
                'default' => __('PayU is a leading payment services provider with presence in 16 growth markets across the world.', 'payu'),
                'desc_tip' => true
            ),
            'payu_feedback' => array(
                'title' => __('Automatic collection:', 'payu'),
                'type' => 'checkbox',
                'description' => __('Automatic collection makes it possible to automatically confirm incoming payments.', 'payu'),
                'label' => ' ',
                'default' => 'no',
                'desc_tip' => true
            )
        );
    }

    /**
     * @param array $currencies
     * @return array
     */
    private function getFormFieldConfig($currencies = [])
    {
        if (count($currencies) < 2) {
            $currencies = array('');
        }
        $config = array();

        foreach ($currencies as $code) {
            $idSuffix = ($code ? '_' : '') . $code;
            $namePrefix = $code . ($code ? ' - ' : '');

            $config += array(
                'pos_id' . $idSuffix => array(
                    'title' => $namePrefix . __('Id point of sales:', 'payu'),
                    'type' => 'text',
                    'description' => $namePrefix . __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'md5' . $idSuffix => array(
                    'title' => $namePrefix . __('Second key (MD5):', 'payu'),
                    'type' => 'text',
                    'description' => __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'client_id' . $idSuffix => array(
                    'title' => $namePrefix . __('OAuth - client_id:', 'payu'),
                    'type' => 'text',
                    'description' => __('Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'client_secret' . $idSuffix => array(
                    'title' => $namePrefix . __('OAuth - client_secret:', 'payu'),
                    'type' => 'text',
                    'description' => __('First key from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'sandbox_pos_id' . $idSuffix => array(
                    'title' => $namePrefix . __('Sandbox - Id point of sales:', 'payu'),
                    'type' => 'text',
                    'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'sandbox_md5' . $idSuffix => array(
                    'title' => $namePrefix . __('Sandbox - Second key (MD5):', 'payu'),
                    'type' => 'text',
                    'description' => __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'sandbox_client_id' . $idSuffix => array(
                    'title' => $namePrefix . __('Sandbox - OAuth - client_id:', 'payu'),
                    'type' => 'text',
                    'description' => __('Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                ),
                'sandbox_client_secret' . $idSuffix => array(
                    'title' =>$namePrefix .  __('Sandbox - OAuth - client_secret:', 'payu'),
                    'type' => 'text',
                    'description' => __('First key from "Configuration Keys" section of PayU management panel.', 'payu'),
                    'desc_tip' => true
                )
            );
        }
        return $config;
    }
}

?>