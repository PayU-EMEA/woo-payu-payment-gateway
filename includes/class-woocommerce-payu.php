<?php

require_once 'lib/openpayu.php';

class WC_Gateway_PayU extends WC_Payment_Gateway {

    function __construct() {
        $this->id = "payu";
        $this->pluginVersion = '1.1.0';
        $this->has_fields = false;

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

        $this->init_form_fields();

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_payu', array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_payu', array($this, 'gateway_ipn'));

        // Status change hook
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);

        $this->init_OpenPayU();

        $this->notifyUrl = add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/'));
    }

    protected function init_OpenPayU()
    {
        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setMerchantPosId($this->pos_id);
        OpenPayU_Configuration::setSignatureKey($this->md5);
        OpenPayU_Configuration::setSender('Wordpress ver ' . get_bloginfo('version') . ' / WooCommerce ver ' . WOOCOMMERCE_VERSION . ' / Plugin ver ' . $this->pluginVersion);
    }

    public function admin_options() {
        ?>

        <h3><?php echo $this->method_title; ?></h3>
        <p><?php echo $this->method_description; ?></p>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>

        <?php
    }

    function init_form_fields() {
        $this->form_fields = include('form-fields.php');
    }

    function process_payment($order_id) {

        $order = new WC_Order($order_id);

        WC()->cart->empty_cart();

        $shipping = $order->get_total_shipping() + $order->get_shipping_tax();

        $orderData['continueUrl'] = $this->get_return_url($order);
        $orderData['notifyUrl'] = add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/'));
        $orderData['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $orderData['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $orderData['description'] = get_bloginfo('name') . ' #' . $order->get_order_number();
        $orderData['currencyCode'] = get_woocommerce_currency();
        $orderData['totalAmount'] = round(round($order->get_total(), 2) * 100);
        $orderData['extOrderId'] = uniqid($order->get_order_number() . '_', true);
        $orderData['settings']['invoiceDisabled'] = true;

        if (!empty($this->validity_time)) {
            $orderData['validityTime'] = $this->validity_time;
        }

        $items = $order->get_items();
        $i = 0;
        $orderData['products'][$i]['name'] = __('Shipment', 'payu').': '. $order->get_shipping_method();
        $orderData['products'][$i]['unitPrice'] = round($shipping * 100);
        $orderData['products'][$i]['quantity'] = 1;

        foreach ($items as $item) {
            $i++;
            $orderData['products'][$i]['name'] = $item['name'];
            $orderData['products'][$i]['unitPrice'] = $order->get_item_total($item, true) * 100;
            $orderData['products'][$i]['quantity'] = $item['qty'];
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
            } else {
                wc_add_notice(__('Payment error. Status code: ', 'payu') . $response->getStatus(), 'error');

                return false;
            }
        } catch (OpenPayU_Exception $e) {
            wc_add_notice(__('Payment error: ', 'payu') . $e->getMessage() . ' (' . $e->getCode() . ')', 'error');

            return false;
        }
    }

    function gateway_ipn() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = trim($body);
            $response = OpenPayU_Order::consumeNotification($data);
            $order_id = (int) preg_replace('/_.*$/', '', $response->getResponse()->order->extOrderId);
            $status = $response->getResponse()->order->status;
            $transaction_id = $response->getResponse()->order->orderId;

            $order = new WC_Order($order_id);

            if ($order->get_status() != 'completed') {
                switch ($status) {
                    case 'CANCELED':
                        $order->update_status('cancelled', __('Payment has been cancelled.', 'payu'));
                        break;

                    case 'REJECTED':
                        $order->update_status('failed', __('Payment has been rejected.', 'payu'));
                        break;

                    case 'COMPLETED':
                        $order->payment_complete($transaction_id);
                        break;

                    case 'WAITING_FOR_CONFIRMATION':
                        $order->update_status(
                            'on-hold',
                            __('Payment has been put on hold - merchant must approve this payment manually.', 'payu')
                        );
                        break;
                }
            }
            header("HTTP/1.1 200 OK");
        }
    }

	public function process_refund($order_id, $amount = null, $reason = '') {
        $order = new WC_Order($order_id);
        $orderId = $order->get_transaction_id();

        if (empty($orderId)) {
            return false;
        }

        $refund = OpenPayU_Refund::create(
            $orderId,
            __('Refund of: ', 'payu') . ' ' . $amount . $order->order_currency . __(' for order: ', 'payu') . $order_id,
            round($amount * 100.0)
        );

        return ($refund->getStatus() == 'SUCCESS');
    }

    public function change_status_action($order_id, $old_status, $new_status) {
        if ($this->payu_feedback == 'yes' && isset($_REQUEST['_wpnonce'])) {
            $order = new WC_Order($order_id);
            $orderId = $order->get_transaction_id();

            if (empty($orderId)) {
                return false;
            }

            if ($old_status == 'on-hold' && ($new_status == 'processing' || $new_status == 'completed')) {
                $status_update = array(
                    "orderId" => $orderId,
                    "orderStatus" => 'COMPLETED'
                );

                OpenPayU_Order::statusUpdate($status_update);
            }

            if($new_status == 'cancelled') {
                OpenPayU_Order::cancel($orderId);
            }
        }

    }
}
?>