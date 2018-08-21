<?php

require_once 'lib/openpayu.php';
require_once 'OauthCacheWP.php';

class WC_Gateway_PayU extends WC_Payment_Gateway
{

    private $pluginVersion = '1.2.6';

    private $pos_id;
    private $md5;
    private $client_id;
    private $client_secret;
    private $payu_feedback;
    private $sandbox;
    private $sandbox_pos_id;
    private $sandbox_md5;
    private $sandbox_client_id;
    private $sandbox_client_secret;

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
        $this->pos_id = $this->get_option('pos_id');
        $this->md5 = $this->get_option('md5');
        $this->client_id = $this->get_option('client_id');
        $this->client_secret = $this->get_option('client_secret');
        $this->payu_feedback = $this->get_option('payu_feedback');
        $this->sandbox = $this->get_option('sandbox');
        $this->sandbox_pos_id = $this->get_option('sandbox_pos_id');
        $this->sandbox_md5 = $this->get_option('sandbox_md5');
        $this->sandbox_client_id = $this->get_option('sandbox_client_id');
        $this->sandbox_client_secret = $this->get_option('sandbox_client_secret');

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'gateway_ipn'));

        // Status change hook
        add_action('woocommerce_order_status_changed', array($this, 'change_status_action'), 10, 3);

        $this->init_OpenPayU();
    }

    protected function init_OpenPayU()
    {
        OpenPayU_Configuration::setEnvironment($this->isSanbox() ? 'sandbox' : 'secure');
        OpenPayU_Configuration::setMerchantPosId($this->isSanbox() ? $this->sandbox_pos_id : $this->pos_id);
        OpenPayU_Configuration::setSignatureKey($this->isSanbox() ? $this->sandbox_md5 : $this->md5);
        OpenPayU_Configuration::setOauthClientId($this->isSanbox() ? $this->sandbox_client_id : $this->client_id);
        OpenPayU_Configuration::setOauthClientSecret($this->isSanbox() ? $this->sandbox_client_secret : $this->client_secret);

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
        $this->form_fields = include('form-fields.php');
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
                'lastName' => $billingData['last_name']
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
                    'redirect' => $response->getResponse()->redirectUri . '&lang=' . $this->getLanguage()
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

            try {
                $response = OpenPayU_Order::consumeNotification($data);
            } catch (Exception $e) {
                header('X-PHP-Response-Code: 500', true, 500);
                die($e->getMessage());
            }


            if ($response->getResponse()->refund) {
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
     * @return bool
     */
    private function isSanbox()
    {
        return 'yes' === $this->sandbox;
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
}

?>