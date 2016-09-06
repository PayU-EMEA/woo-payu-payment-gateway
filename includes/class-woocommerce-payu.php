<?php

require_once 'lib/openpayu.php';
require_once('OautchCacheWoocommerce.php');

class WC_Gateway_PayU extends WC_Payment_Gateway {


    function __construct() {
        $this->id = "payu";
        $this->pluginVersion = '1.1.1';
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

        add_filter('process_payment', array($this, 'process_payment'));

        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

        add_filter('wc_add_to_cart_message', 'handler_function_name', 10,1);

        $this->init_OpenPayU();

        $this->notifyUrl = add_query_arg('wc-api', 'WC_Gateway_PayU', home_url('/'));
    }



    protected function init_OpenPayU()
    {
        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setMerchantPosId($this->pos_id);
        OpenPayU_Configuration::setSignatureKey($this->md5);
        // Oauth configuration

        if ($this->client_secret && $this->client_id) {
            OpenPayU_Configuration::setOauthClientId($this->pos_id);
            OpenPayU_Configuration::setOauthClientSecret($this->client_secret);
            OpenPayU_Configuration::setOauthTokenCache(new OauthCacheWP());
        }

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
        $payMethod=null;
        $bank_list=$this->get_option('display_bank');
        var_dump($bank_list);
        if ($this->get_option('display_bank')=="no"){
            return $this->PayUOrder($order,$order_id,$payMethod,$bank_list);
       }
        else if ($this->get_option('display_bank')=="yes"){
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url($order,$order_id));
        };
    }


    private function prepareOrder($order, $payMethod)
    {
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
        if ($payMethod != null) {
            $orderData['payMethods'] = array(
                'payMethod' => array(
                    'type' => 'PBL',
                    'value' => $payMethod
                )
            );
        };
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

        return $orderData;
    }



    function receipt_page($order_id)
    {

        $message=null;
        $payuCondition=null;
        $order = new WC_Order($order_id);
        $retreive = OpenPayU_Retrieve::payMethods("pl");
        $response = $retreive->getResponse();
        $payByLink = $response->payByLinks;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $Condition = $_POST["payuConditions"];
            $Method = $_POST["payMethod"];
            echo '<ul class="woocommerce-error">';
            if ($Condition == null) {
                echo'<li>'.__('Payment error : Terms of single PayU payment transaction','payu').'</li>';
            }
            if ($Method==null) {
                echo '<li>'.__('Payment error : Please select a method of payment','payu').'</li>';
            }
       echo'</ul>';
        }
            $payMethod=null;
            $this->listBank($payByLink);
            $this->GetChoosedBank($order,$order_id);
    }
    function listBank($payByLink){

        echo '<form action="" method="post" id="payuForm">';
        echo '<input type="hidden" name="payuPay" value="1" />';
        echo '<div class="payMethods">';
        foreach ($payByLink as $value) {
            if ($value->status != 'DISABLED') {
                echo '<div class="payMethod payMethodEnable ">';
                echo '<input id="payMethod-' . $value->value . '" type="radio" value="' . $value->value . '" name="payMethod" checked="checked" />';
                echo '<label for="payMethod-' . $value->value . '" class="payMethodLabel">';
                echo '<div class="payMethodImage" >
                      <img src = "' . $value->brandImageUrl . '" alt="' . $value->name . '">
                          </div >';
                echo $value->name;
                echo '</label>';
                echo '</div >';
            };
        };
        echo '</div >';
        echo '<input type="submit" class="button alt" id="place_order" value="Zapłać" />';
        echo'<div id="TermsCheckbox">';
        echo '</p>'.__('Payment is processed by PayU SA; The recipient\'s data, the payment title and the amount are provided to PayU SA by the recipient; The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees.','payu').'</p>';
        echo'<p><input type="checkbox" value="1" checked="checked" name="payuConditions" id="payuCondition">';
        echo'<a href=" '.__('http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_en.pdf','payu').' " target="_blank">';
        echo __('Terms of single PayU payment transaction', 'payu');
        echo'</a>';
        echo'</input>';
        echo'</form>';
        echo '<p>'.__('The administrator of your personal data within the meaning of the Personal Data Protection Act of 29 August 1997 (Journal of Laws of 2002, No. 101, item 926 as amended) is PayU SA with the registered office in Poznań (60-166) at ul. Grunwaldzka 182. Your personal data will be processed according to the applicable provisions of law for archiving and service provision purposes. Your data will not be made available to other entities, except of entities authorized by law. You are entitled to access and edit your data. Data provision is voluntary but required to achieve the above-mentioned purposes.','payu').'<p>';
        echo'</div>';

    }
    function GetChoosedBank($order,$order_id,$payMethod = null){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $payuCondition = $_POST["payuConditions"];
            $payMethod = $_POST["payMethod"];
            if ($payMethod != null and $payuCondition != null) {
                $wc_payu= $this->PayUOrder($order,$order_id,$payMethod,$bank_list='yes');
                return $wc_payu;
            }
            else {
                $payMethod=null;
            }
        }

    }

    function PayUOrder($order,$order_id,$payMethod,$bank_list){


        try {
            $orderData = $this->prepareOrder($order, $payMethod);
            $response = OpenPayU_Order::create($orderData);
            if ($response->getStatus() == 'SUCCESS' || $response->getStatus() == 'WARNING_CONTINUE_REDIRECT') {

                add_post_meta($order_id, '_transaction_id', $response->getResponse()->orderId, true);
                if($bank_list!='no') {
                    return array(
                        'result' => 'success',
                        'redirect' => wp_redirect($response->getResponse()->redirectUri)
                    );
                }
                else if($bank_list='no'){
                    return array(
                        'result' => 'success',
                        'redirect' => $response->getResponse()->redirectUri
                    );
                }


            }  else {
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
            $data = stripslashes(trim($body));
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