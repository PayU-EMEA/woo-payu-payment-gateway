<?php
/**
 * Plugin Name: PayU - WooCommerce Gateway
 * Plugin URI: http://payu.pl
 * Description: Bramka płatności PayU dla WooCommerce.
 * Version: 2.1.0
 * Author: PayU
 * Copyright Copyright (c) 2015 PayU
 * License: http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */

require_once 'lib/openpayu.php';

class BPMJ_WooCommerce_PayU extends WC_Payment_Gateway {

    function __construct() {

        $this->id = "bpmj_payu";
        $this->pluginVersion = '2.1.0';

        $this->method_title = __( "PayU", 'bpmj-woocommerce-payu' );
        $this->method_description = __( "Bramka płatności PayU dla WooCommerce.", 'bpmj-woocommerce-payu' );

        $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( 'assets/images/payu.png' , dirname(__FILE__) ) );

        $this->has_fields = true;

        $this->supports = array(
            'products',
            'refunds'
        );

        //$this->view_transaction_url = 'https://secure.payu.com/front/paymentinfo/%s'; ???

        $this->init_settings();

        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }

        // pobranie ustawionej waluty
        $this->currency = get_woocommerce_currency();
        $this->currency_slug = strtolower(get_woocommerce_currency());

        // sprawdź, czy bramka może być włączona (obsługuje waluty PLN, EUR, USD, GPB)
        if ( !$this->is_valid_for_use() ) {
            $this->enabled = false;
        }

        $this->init_form_fields();

        // Zapisywanie ustawień
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Payment listener/API hook
        add_action('woocommerce_api_bpmj_woocommerce_payu', array($this, 'gateway_ipn'));

        // Zmiana statusu
        add_action( 'woocommerce_order_status_changed', array( $this, 'change_status_action' ), 10, 3 );

        // konfiguracja OpenPayU
        $this->initializeOpenPayU();

        $this->notifyUrl = str_replace('https:', 'http:', add_query_arg('wc-api', 'BPMJ_WooCommerce_PayU', home_url('/')));
    }

    protected function initializeOpenPayU()
    {
        OpenPayU_Configuration::setApiVersion(2.1);
        OpenPayU_Configuration::setEnvironment('secure');
        $key = 'pos_id_' . $this->currency_slug;
        OpenPayU_Configuration::setMerchantPosId($this->$key); // POS ID (Checkout)
        $key = 'md5_' . $this->currency_slug;
        OpenPayU_Configuration::setSignatureKey($this->$key); // Drugi klucz MD5
        OpenPayU_Configuration::setSender('WooCommerce ver ' . $this->getWoocommerceVersionNumber() . '/Plugin ver ' . $this->pluginVersion);
    }

    function getWoocommerceVersionNumber() {
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $pluginFolder = get_plugins( '/' . 'woocommerce' );
        $pluginFile = 'woocommerce.php';

        if ( isset( $pluginFolder[$pluginFile]['Version'] ) ) {
            return $pluginFolder[$pluginFile]['Version'];

        } else {
            return false;
        }
    }

    public function is_valid_for_use(){
        if( !in_array( $this->currency, array('PLN', 'EUR', 'USD', 'GPB') ) )
            return false;

        return true;
    }

    function init_form_fields(){

        if ( !$this->is_valid_for_use() ) {
            $this->method_description .= '<br />' . __( 'Bramka płatności PayU nie obsługuje waluty Twojego sklepu. Obsługiwane waluty: PLN, EUR, USD, GPB.', 'bpmj-woocommerce-payu' );
            return;
        }

        $this -> form_fields = array(

            'enabled' => array(
                'title'=> __('Włącz / Wyłącz', 'bpmj-woocommerce-payu'),
                'type' => 'checkbox',
                'label' => __('Włącz PayU', 'bpmj-woocommerce-payu'),
                'default' => 'no'),

            'title' => array(
                'title' => __('Tytuł:', 'bpmj-woocommerce-payu'),
                'type'=> 'text',
                'description' => __('Tytuł, który widzi użytkownik podczas składania zamówienia.', 'bpmj-woocommerce-payu'),
                'default' => __('PayU', 'bpmj-woocommerce-payu'),
                'desc_tip' => true),

            'description' => array(
                'title' => __('Opis:', 'bpmj-woocommerce-payu'),
                'type' => 'text',
                'description' => __('Opis, który widzi użytkownik podczas składania zamówienia.', 'bpmj-woocommerce-payu'),
                'default' => __('PayU - płatności internetowe, szybkie przelewy przez Internet', 'bpmj-woocommerce-payu'),
                'desc_tip' => true),

            'pos_id_' . $this->currency_slug => array(
                'title' => __('Id punktu płatności (pos_id):', 'bpmj-woocommerce-payu'),
                'type' => 'text',
                'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE"'),
                'desc_tip' => true),

            'md5_' . $this->currency_slug => array(
                'title' => __('Drugi klucz (MD5):', 'bpmj-woocommerce-payu'),
                'type' => 'text',
                'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'bpmj-woocommerce-payu'),
                'desc_tip' => true),

            'validity_time' => array(
                'title' => __('Ważność zamówienia [s]:', 'bpmj-woocommerce-payu'),
                'type' => 'text',
                'description' =>  __('Wpisz tutaj, czas (w sekundach) po jakim nieopłacone zamówienie powinno stracić ważność.', 'bpmj-woocommerce-payu'),
                'default' => '',
                'desc_tip' => true),

            'payu_feedback' => array(
                'title'=> __('Wysyłaj statusy do PayU', 'bpmj-woocommerce-payu'),
                'type' => 'checkbox',
                'description' =>  __('Zaznacz tę opcję, jeśli chcesz, aby przy ręcznej zmianie statusu zamówienia na anulowane lub zakceptowane informować PayU, w celu odzrucenia lub przyjęcia płatności.', 'bpmj-woocommerce-payu'),
                'label' => __('Włącz', 'bpmj-woocommerce-payu'),
                'default' => 'no',
                'desc_tip' => true),
        );
    }

    function process_payment($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $order->update_status('pending', __('Płatność jest w trakcie rozliczenia.', 'bpmj-woocommerce-payu'));

        $woocommerce->cart->empty_cart();
        $shipping = round($order->get_total_shipping() * 100);

        $orderData['continueUrl'] = $this->get_return_url($order);
        $orderData['notifyUrl'] = $this->notifyUrl;
        $orderData['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $orderData['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $orderData['description'] = get_bloginfo('name') . ' #' . $order->get_order_number();
        $orderData['currencyCode'] = $this->currency;
        $orderData['totalAmount'] = round( round( $order->get_total(), 2) * 100 ) - $shipping;
        $orderData['extOrderId'] = $order->get_order_number().'__'.microtime(); // musi być unikalny dla danego Pos'a

        if( !empty ( $this->validity_time ) )
            $orderData['validityTime'] = $this->validity_time;

        $items = $order->get_items();
        $i = 0;
        foreach($items as $item) {
            $orderData['products'][$i]['name'] = $item['name'];
            $orderData['products'][$i]['unitPrice'] = round( round( $item['line_total'], 2) * 100.0 / $item['qty'] );
            $orderData['products'][$i]['quantity'] = $item['qty'];
            $i++;
        }

        if( !empty( $shipping) ) {
            $orderData['shippingMethods'][] = array(
                'price' => $shipping,
                'name' => __('Koszty wysyłki', 'bpmj-woocommerce-payu'),
                'country' => 'PL'
            );
        }

        $orderData['buyer']['email'] = $order->billing_email;
        $orderData['buyer']['phone'] = $order->billing_phone;
        $orderData['buyer']['firstName'] = $order->billing_first_name;
        $orderData['buyer']['lastName'] = $order->billing_last_name;

//        try {
        $response = OpenPayU_Order::create($orderData);

        if($response->getStatus() == 'SUCCESS'){
            add_post_meta( $order_id, '_transaction_id', $response->getResponse()->orderId, true );

            return array(
                'result' => 'success',
                'redirect' => $response->getResponse()->redirectUri
            );
        }
        else {
            wc_add_notice( __('Błąd płatności. Status z PayU: ', 'bpmj-woocommerce-payu') . $response->getStatus(), 'error' );
            return;
        }
//        } catch (OpenPayU_Exception $e) {
//            wc_add_notice( __('Błąd płatności: ', 'bpmj-woocommerce-payu') . $e->getCode() . ' ' . $e->getMessage(), 'error' );
//            return;
//        }
    }

    function gateway_ipn () {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $body = file_get_contents('php://input');
            $data = stripslashes(trim($body));

            $response = OpenPayU_Order::consumeNotification($data);
            $order_id = (int)$response->getResponse()->order->extOrderId;
            $status = $response->getResponse()->order->status; //NEW PENDING CANCELED REJECTED COMPLETED WAITING_FOR_CONFIRMATION
            $transaction_id = $response->getResponse()->order->orderId;

            $order = new WC_Order($order_id);

            switch($status) {
                case 'NEW':
                case 'PENDING':
                    add_post_meta( $order->id, '_transaction_id', $transaction_id, true );
                    break;

                case 'CANCELED':
                    $order->update_status('cancelled', __('Płatność została anulowana.', 'bpmj-woocommerce-payu'));
                    break;

                case 'REJECTED':
                    $order->update_status('failed', __('Płatność została odrzucona z uwagi na życzenie sprzedawcy.', 'bpmj-woocommerce-payu'));
                    break;

                case 'COMPLETED':
                    $order->payment_complete( $transaction_id );
                    break;

                case 'WAITING_FOR_CONFIRMATION':
                    $order->update_status('on-hold', __('System PayU oczekuje na akcje ze strony sprzedawcy w celu wykonania płatności. Ten status występuje w przypadku gdy auto-odbiór na posie sprzedawcy jest wyłączony.', 'bpmj-woocommerce-payu'));
                    break;

                default:
            }

            header("HTTP/1.1 200 OK");
        }
    }

    public function process_refund( $order_id, $amount = null ) {

        $order = new WC_Order($order_id);
        $orderId = $order->get_transaction_id();

        if( empty( $orderId ) )
            return false;

        $refund = OpenPayU_Refund::create(
            $orderId,
            __('Zwrot kwoty: ', 'bpmj-woocommerce-payu') . ' ' . $amount . ' ' . $this->currency . __(' dla zamówienia nr: ', 'bpmj-woocommerce-payu') . $order_id,
            round( $amount * 100.0 )
        );

        $status_desc = OpenPayU_Util::statusDesc($refund->getStatus());
        if($refund->getStatus() != 'SUCCESS')
            return false;

        return true;
    }

    public function change_status_action( $order_id, $old_status, $new_status ) {
        if( $this->payu_feedback == 'yes' && isset ($_REQUEST['_wpnonce']) ) {
            $order = new WC_Order($order_id);
            $orderId = $order->get_transaction_id();

            if( empty( $orderId ) )
                return false;

            // zatwierdzenie płatności oczekującej WAITING_FOR_CONFIRMATION -> COMPLETED
            if( $old_status == 'on-hold' && ( $new_status == 'processing' || $new_status == 'completed' ) ) {
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
