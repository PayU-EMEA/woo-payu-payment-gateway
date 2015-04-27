<?php

require_once 'lib/openpayu.php';

class WC_Gateway_PayU extends WC_Payment_Gateway {

    function __construct() {
        $this->id = "payu";
        $this->pluginVersion = '1.0.0';
        $this->has_fields = false;
        $this->supported_currencies = array('PLN', 'EUR', 'USD', 'GPB');

        $this->order_button_text = __('Pay with PayU', 'payu');
        $this->method_title = __('PayU', 'payu');
        $this->method_description = __('Official PayU payment gateway for WooCommerce.', 'payu');

        $this->icon = apply_filters('woocommerce_payu_icon', 'https://static.payu.com/plugins/woocommerce_payu_logo.png');

        $this->supports = array(
            'products',
            'refunds'
        );

        $this->init_settings();

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        $this->currency = get_woocommerce_currency();
        $this->currency_slug = strtolower(get_woocommerce_currency());

        if (!in_array($this->currency, $this->supported_currencies)) {
            $this->enabled = false;
        }

        $this->init_form_fields();

        // Settings' saving hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_payu', array($this, 'gateway_ipn'));

        // Status change hook
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);

        $this->init_OpenPayU();

        $this->notifyUrl = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/')));
    }

    protected function init_OpenPayU()
    {
        OpenPayU_Configuration::setApiVersion(2.1);
        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setMerchantPosId($this->{'pos_id_' . $this->currency_slug});
        OpenPayU_Configuration::setSignatureKey($this->{'md5_' . $this->currency_slug});
        OpenPayU_Configuration::setSender('Wordpress v' . get_bloginfo('version') . '/WooCommerce v' . WOOCOMMERCE_VERSION . '/Plugin v' . $this->pluginVersion);
    }

    public function admin_options() {
        ?>

        <h3><?php echo $this->method_title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <p><?php _e('Supported currencies: ', 'payu'); ?> <?php echo implode(', ', $this->supported_currencies); ?>.</p>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>

        <?php
    }

    function init_form_fields() {
        $this->form_fields = include('form-fields.php');
    }

    function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $woocommerce->cart->empty_cart();

        $shipping = $order->get_total_shipping();

        $orderData['continueUrl'] = $this->get_return_url($order);
        $orderData['notifyUrl'] = $this->notifyUrl;
        $orderData['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $orderData['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $orderData['description'] = get_bloginfo('name') . ' #' . $order->get_order_number();
        $orderData['currencyCode'] = $this->currency;
        $orderData['totalAmount'] = round(round($order->get_total() - $shipping, 2) * 100);
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

        $orderData['shippingMethods'][0]['name'] = $order->get_shipping_method();
        $orderData['shippingMethods'][0]['country'] = $order->shipping_country;
        $orderData['shippingMethods'][0]['price'] = round($shipping * 100);

        $orderData['buyer']['delivery']['recipientName'] = trim($order->shipping_first_name . ' ' . $order->shipping_last_name);
        $orderData['buyer']['delivery']['street'] = trim($order->shipping_address_1 . ' ' . $order->shipping_address_2);
        $orderData['buyer']['delivery']['postalCode'] = $order->shipping_postcode;
        $orderData['buyer']['delivery']['city'] = $order->shipping_city;
        $orderData['buyer']['delivery']['countryCode'] = $order->shipping_country;

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
            } else {
                wc_add_notice(__('Payment error. Status code: ', 'payu') . $response->getStatus(), 'error');
                return;
            }
        } catch (OpenPayU_Exception $e) {
            wc_add_notice(__('Payment error: ', 'payu') . $e->getMessage() . ' (' . $e->getCode() . ')', 'error');
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
                case 'CANCELED':
                    $order->update_status('cancelled', __('Payment has been cancelled.', 'payu'));
                    break;

                case 'REJECTED':
                    $order->update_status('failed', __('Payment has been rejected.', 'payu'));
                    break;

                case 'COMPLETED':
                    $shipping_address = $response->getResponse()->order->buyer->delivery;
                    $recipient_name = preg_split("/ ([a-zA-Z_-]+)$/", $shipping_address->recipientName, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                    $address = array(
                        'first_name' => $recipient_name[0],
                        'last_name' => $recipient_name[1],
                        'city' => $shipping_address->city,
                        'postcode' => $shipping_address->postalCode,
                        'address_1' => $shipping_address->street,
                        'address_2' => '',
                        'country' => $shipping_address->countryCode
                    );

                    $order->set_address($address, 'shipping');
                    $order->payment_complete($transaction_id);

                    break;

                case 'WAITING_FOR_CONFIRMATION':
                    $order->update_status('on-hold', __('Payment has been put on hold - merchant must approve this payment manually.', 'payu'));
                    break;
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
            __('Refund of: ', 'payu') . ' ' . $amount . ' ' . $this->currency . __(' for order: ', 'payu') . $order_id,
            round($amount * 100.0)
        );

        return ($refund->getStatus() == 'SUCCESS');
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