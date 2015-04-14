<?php
/**
 * Plugin Name: PayU Payment Gateway
 * Plugin URI: https://github.com/PayU/plugin_woocommerce
 * Description: PayU payment gateway for WooCommerce
 * Version: 1.0.0
 * Author: PayU SA
 * Copyright (c) 2015 PayU
 * License: LGPL 3.0
 */

require_once 'lib/openpayu.php';

class WC_Gateway_PayU extends WC_Payment_Gateway {

    function __construct() {
        $this->id = "payu";
        $this->pluginVersion = '1.0.0';
        $this->has_fields = false;
        $this->supported_currencies = array('PLN', 'EUR', 'USD', 'GPB');

        $this->order_button_text = __('Pay with PayU', 'payu');
        $this->method_title = __("PayU", 'payu');
        $this->method_description = __('Official PayU payment gateway for WooCommerce', 'payu');

        $this->icon = apply_filters('woocommerce_payu_icon', plugins_url('assets/images/payu.png', dirname(__FILE__)));

        $this->supports = array(
            'products',
            'refunds'
        );

        $this->init_settings();

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // pobranie ustawionej waluty
        $this->currency = get_woocommerce_currency();
        $this->currency_slug = strtolower(get_woocommerce_currency());

        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }

        $this->init_form_fields();

        // Zapisywanie ustawień
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_payu', array($this, 'gateway_ipn'));

        // Zmiana statusu
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);

        // konfiguracja OpenPayU
        $this->init_OpenPayU();

        $this->notifyUrl = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/')));
    }

    protected function init_OpenPayU()
    {
        OpenPayU_Configuration::setApiVersion(2.1);
        OpenPayU_Configuration::setEnvironment('secure');
        $key = 'pos_id_' . $this->currency_slug;
        OpenPayU_Configuration::setMerchantPosId($this->$key);
        $key = 'md5_' . $this->currency_slug;
        OpenPayU_Configuration::setSignatureKey($this->$key);
        OpenPayU_Configuration::setSender('Wordpress v' . get_bloginfo('version') . '/WooCommerce v' . WOOCOMMERCE_VERSION . '/Plugin v' . $this->pluginVersion);
    }

    public function is_valid_for_use() {
        return in_array($this->currency, $this->supported_currencies);
    }

    public function admin_options() {
        if ($this->is_valid_for_use()) {
            parent::admin_options();
        } else {
            ?>
            <h2><?php echo $this->get_description(); ?></h2>
            <h3><?php _e('Gateway has been disabled.', 'payu'); ?></h3>
            <p><?php _e("This plugin doesn't support the currency of your shop.", 'payu'); ?></p>
            <p><?php _e("Supported currencies: ", 'payu'); ?> <?php echo implode(', ', $this->supported_currencies); ?>.</p>
            <?php
        }
    }

    function init_form_fields() {
        $this->form_fields = include('form-fields.php');
    }

    function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $order->update_status('pending', __('Płatność jest w trakcie rozliczenia.', 'payu'));

        $woocommerce->cart->empty_cart();
        $shipping = round($order->get_total_shipping() * 100);

        $orderData['continueUrl'] = $this->get_return_url($order);
        $orderData['notifyUrl'] = $this->notifyUrl;
        $orderData['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $orderData['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $orderData['description'] = get_bloginfo('name') . ' #' . $order->get_order_number();
        $orderData['currencyCode'] = $this->currency;
        $orderData['totalAmount'] = round(round($order->get_total(), 2) * 100) - $shipping;
        $orderData['extOrderId'] = $order->get_order_number() . '_' . microtime(true);

        if (!empty($this->validity_time)) {
            $orderData['validityTime'] = $this->validity_time;
        }

        $items = $order->get_items();
        $i = 0;
        foreach ($items as $item) {
            $orderData['products'][$i]['name'] = $item['name'];
            $orderData['products'][$i]['unitPrice'] = round(round($item['line_total'], 2) * 100.0 / $item['qty']);
            $orderData['products'][$i]['quantity'] = $item['qty'];
            $i++;
        }

        if (!empty($shipping)) {
            $orderData['shippingMethods'][] = array(
                'price' => $shipping,
                'name' => __('Koszty wysyłki', 'payu'),
                'country' => 'PL'
            );
        }

        $orderData['buyer']['email'] = $order->billing_email;
        $orderData['buyer']['phone'] = $order->billing_phone;
        $orderData['buyer']['firstName'] = $order->billing_first_name;
        $orderData['buyer']['lastName'] = $order->billing_last_name;

        try {
            $response = OpenPayU_Order::create($orderData);

            if ($response->getStatus() == 'SUCCESS') {
                add_post_meta($order_id, '_transaction_id', $response->getResponse()->orderId, true);

                return array(
                    'result' => 'success',
                    'redirect' => $response->getResponse()->redirectUri
                );
            }
            else {
                wc_add_notice(__('Błąd płatności. Status z PayU: ', 'payu') . $response->getStatus(), 'error');

                return;
            }
        } catch (OpenPayU_Exception $e) {
            wc_add_notice(__('Błąd płatności: ', 'payu') . $e->getCode() . ' ' . $e->getMessage(), 'error');

            return;
        }
    }

    function gateway_ipn() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = stripslashes(trim($body));

            $response = OpenPayU_Order::consumeNotification($data);
            $order_id = (int) preg_replace('/_.*$/', '', $response->getResponse()->order->extOrderId);
            $status = $response->getResponse()->order->status;
            $transaction_id = $response->getResponse()->order->orderId;

            $order = new WC_Order($order_id);

            switch ($status) {
                case 'NEW':
                case 'PENDING':
                    break;

                case 'CANCELED':
                    $order->update_status('cancelled', __('Płatność została anulowana.', 'payu'));
                    break;

                case 'REJECTED':
                    $order->update_status('failed', __('Płatność została odrzucona z uwagi na życzenie sprzedawcy.', 'payu'));
                    break;

                case 'COMPLETED':
                    $order->payment_complete($transaction_id);
                    break;

                case 'WAITING_FOR_CONFIRMATION':
                    $order->update_status('on-hold', __('System PayU oczekuje na akcje ze strony sprzedawcy w celu wykonania płatności. Ten status występuje w przypadku gdy auto-odbiór na posie sprzedawcy jest wyłączony.', 'payu'));
                    break;

                default:
            }

            header("HTTP/1.1 200 OK");
        }
    }

    public function process_refund($order_id, $amount = null) {
        $order = new WC_Order($order_id);
        $orderId = $order->get_transaction_id();

        if (empty($orderId)) {
            return false;
        }

        $refund = OpenPayU_Refund::create(
            $orderId,
            __('Zwrot kwoty: ', 'payu') . ' ' . $amount . ' ' . $this->currency . __(' dla zamówienia nr: ', 'payu') . $order_id,
            round($amount * 100.0)
        );

        $status_desc = OpenPayU_Util::statusDesc($refund->getStatus());
        if ($refund->getStatus() != 'SUCCESS')
            return false;

        return true;
    }

    public function change_status_action($order_id, $old_status, $new_status) {
        if ($this->payu_feedback == 'yes' && isset($_REQUEST['_wpnonce'])) {
            $order = new WC_Order($order_id);
            $orderId = $order->get_transaction_id();

            if (empty($orderId))
                return false;

            // zatwierdzenie płatności oczekującej WAITING_FOR_CONFIRMATION -> COMPLETED
            if ($old_status == 'on-hold' && ($new_status == 'processing' || $new_status == 'completed')) {
                $status_update = array(
                    "orderId" => $orderId,
                    "orderStatus" => 'COMPLETED'
                );

                $response = OpenPayU_Order::statusUpdate($status_update);
            }

            // anulowanie zamówienia
            if($new_status == 'cancelled') {
                $response = OpenPayU_Order::cancel($orderId);
            }
        }

    }
}
?>