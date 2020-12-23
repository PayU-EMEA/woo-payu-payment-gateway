<?php

require_once 'lib/openpayu.php';
require_once 'OauthCacheWP.php';

class WC_Gateway_PayU extends WC_Payment_Gateway
{
    private $pluginVersion = '1.3.1';

    private $payu_feedback;
    private $sandbox;
    private $enable_for_shipping;

    function __construct()
    {
        $this->setup_properties();
        $this->init_form_fields();
        $this->init_settings();

        $this->title               = $this->get_option('title');
        $this->description         = $this->get_option('description');
        $this->payu_feedback       = $this->get_option('payu_feedback', true);
        $this->sandbox             = $this->get_option('sandbox', false);
        $this->enable_for_shipping = $this->get_option( 'enable_for_shipping', []);

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, [$this, 'gateway_ipn']);
        // Status change hook
        add_action('woocommerce_order_status_changed', [$this, 'change_status_action'], 10, 3);

        $this->init_OpenPayU();
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties() {
        $this->id                 = 'payu';
        $this->icon               = apply_filters('woocommerce_payu_icon', 'https://static.payu.com/plugins/woocommerce_payu_logo.png');
        $this->method_title       = __('PayU', 'payu');
        $this->method_description = __('Official PayU payment gateway for WooCommerce.', 'payu');
        $this->has_fields         = false;
        $this->supports           = ['products', 'refunds'];
    }

    protected function init_OpenPayU($currency = null)
    {
        $isSandbox = 'yes' === $this->get_option('sandbox');

        if ($this->isWpmlActiveAndConfigure() || apply_filters('woocommerce_payu_multicurrency_active', false))
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

    function init_form_fields()
    {
        global $woocommerce_wpml;

        $currencies = [];

        if ($this->isWpmlActiveAndConfigure())
        {
			$currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
		} elseif(apply_filters('woocommerce_payu_multicurrency_active', false)) {
			$currencies = apply_filters('woocommerce_payu_get_currency_codes', array());
		}

        $this->form_fields = array_merge($this->getFormFieldsBasic(), $this->getFormFieldConfig($currencies), $this->getFormFieldInfo());
    }

    /**
     * Check If The Gateway Is Available For Use.
     * Copy from COD module
     *
     * @return bool
     */
    public function is_available()
    {
        $order = null;
        $is_order_processing = false;

        if (WC()->cart) {
            $is_order_processing = true;
        } elseif ( is_page( wc_get_page_id( 'checkout' ) ) && get_query_var( 'order-pay' ) > 0 ) {
            $order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
            $is_order_processing = true;
        }

        if (!empty($this->enable_for_shipping) && $is_order_processing) {
            $order_shipping_items = is_object($order) ? $order->get_shipping_methods() : false;
            $chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

            if ($order_shipping_items) {
                $canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
            } else {
                $canonical_rate_ids = $this->get_canonical_package_rate_ids($chosen_shipping_methods_session);
            }

            if (!count($this->get_matching_rates($canonical_rate_ids))) {
                return false;
            }
        }

        return parent::is_available();
    }
    /**
     * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
     *
     * Copy from COD
     *
     * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
     * @return array $canonical_rate_ids    Rate IDs in a canonical format.
     */
    private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

        $canonical_rate_ids = array();

        foreach ( $order_shipping_items as $order_shipping_item ) {
            $canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
        }

        return $canonical_rate_ids;
    }

    /**
     * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
     *
     * Copy from COD
     *
     * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
     * @return array $canonical_rate_ids  Rate IDs in a canonical format.
     */
    private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

        $shipping_packages  = WC()->shipping()->get_packages();
        $canonical_rate_ids = array();

        if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
            foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
                if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
                    $chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
                    $canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
                }
            }
        }

        return $canonical_rate_ids;
    }

    /**
     * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
     *
     * Copy from COD
     *
     * @param array $rate_ids Rate ids to check.
     * @return boolean
     */
    private function get_matching_rates( $rate_ids ) {
        // First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
        return array_unique( array_merge( array_intersect( $this->enable_for_shipping, $rate_ids ), array_intersect( $this->enable_for_shipping, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
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
            'enable_for_shipping' => array(
                'title'             => __( 'Enable for shipping methods', 'payu' ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 400px;',
                'default'           => '',
                'description'       => __( 'If PayU is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'payu' ),
                'options'           => $this->getShippingMethods(),
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Select shipping methods', 'payu' ),
                ),
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


    /**
     * Checks to see whether or not the admin settings are being accessed by the current request.
     * Copy from COD module
     *
     * @return bool
     */
    private function is_accessing_settings() {
        if ( is_admin() ) {
            if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
                return false;
            }
            if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
                return false;
            }
            if ( ! isset( $_REQUEST['section'] ) || 'payu' !== $_REQUEST['section'] ) {
                return false;
            }

            return true;
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            global $wp;
            if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
                return true;
            }
        }

        return false;
    }
    private function getShippingMethods()
    {
        // Since this is expensive, we only want to do it if we're actually on the settings page.
        if ( ! $this->is_accessing_settings() ) {
            return array();
        }

        $data_store = WC_Data_Store::load( 'shipping-zone' );
        $raw_zones  = $data_store->get_zones();

        foreach ( $raw_zones as $raw_zone ) {
            $zones[] = new WC_Shipping_Zone( $raw_zone );
        }

        $zones[] = new WC_Shipping_Zone( 0 );

        $options = array();
        foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

            $options[ $method->get_method_title() ] = array();

            // Translators: %1$s shipping method name.
            $options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() );

            foreach ( $zones as $zone ) {

                $shipping_method_instances = $zone->get_shipping_methods();

                foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

                    if ( $shipping_method_instance->id !== $method->id ) {
                        continue;
                    }

                    $option_id = $shipping_method_instance->get_rate_id();

                    // Translators: %1$s shipping method title, %2$s shipping method id.
                    $option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

                    // Translators: %1$s zone name, %2$s shipping method instance name.
                    $option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title );

                    $options[ $method->get_method_title() ][ $option_id ] = $option_title;
                }
            }
        }

        return $options;
    }
}
