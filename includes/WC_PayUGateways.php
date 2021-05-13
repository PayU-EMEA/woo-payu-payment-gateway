<?php
require_once 'lib/openpayu.php';
require_once 'OauthCacheWP.php';

abstract class WC_PayUGateways extends WC_Payment_Gateway
{
    public static $paymethods = [];

    protected $enable_for_shipping;
    protected $paytype;

    public $pos_id;
    public $selected_method;
    public $has_terms_checkbox;
    public $cart_contents_total = 0;
    const CONDITION_PL = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf';
    const CONDITION_EN = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_en.pdf';
    const CONDITION_CS = 'http://static.payu.com/sites/terms/files/Podmínky pro provedení jednorázové platební transakce v PayU.pdf';
    const PRIVACY_PL = 'https://static.payu.com/sites/terms/files/payu_privacy_policy_pl_pl.pdf';
    const PRIVACY_EN = 'https://static.payu.com/sites/terms/files/payu_privacy_policy_en_en.pdf';


    /**
     * Setup general properties for the gateway.
     * @param string $id
     */
    function __construct($id)
    {
        $this->id = $id;

        $this->setup_properties($id);
        $this->init_form_fields();
        $this->init_settings();

        $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/logo-payu.svg', PAYU_PLUGIN_FILE ));
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description', ' ');
        $this->sandbox = $this->get_option('sandbox', false);
        $this->enable_for_shipping = $this->get_option('enable_for_shipping', []);

        if (!is_admin() && @$_GET['pay_for_order'] && @$_GET['key']) {
            $order_id = $this->get_post_id_by_meta_key_and_value('_order_key',
                filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)['key']);
            if ($order_id) {
                $order = wc_get_order($order_id);
                $this->cart_contents_total = $order->get_total();
            }
        } elseif (isset(WC()->cart->cart_contents_total)) {
            $this->cart_contents_total = WC()->cart->cart_contents_total;
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueue_payu_gateway_assets']);

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, [$this, 'gateway_ipn']);
    }

    public function enqueue_payu_gateway_assets()
    {
        wp_enqueue_script('payu-gateway', plugins_url( '/assets/js/payu-gateway.js', PAYU_PLUGIN_FILE ),
            ['jquery'], PAYU_PLUGIN_VERSION, true);
        wp_enqueue_style('payu-gateway', plugins_url( '/assets/css/payu-gateway.css', PAYU_PLUGIN_FILE ),
            [], PAYU_PLUGIN_VERSION);
    }

    /**
     * @return boolean
     */
    protected function is_enabled() {
        return 'yes' === $this->enabled;
    }

    /**
     * Get post id from meta key and value
     * @param string $key
     * @param mixed $value
     * @return int|bool
     */
    protected function get_post_id_by_meta_key_and_value($key, $value)
    {
        global $wpdb;
        $meta = $wpdb->get_results("SELECT * FROM `" . $wpdb->postmeta . "` WHERE meta_key='" . esc_sql($key) . "' AND meta_value='" . esc_sql($value) . "'");
        if (is_array($meta) && !empty($meta) && isset($meta[0])) {
            $meta = $meta[0];
        }
        if (is_object($meta)) {
            return $meta->post_id;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    protected function get_payu_response()
    {
        $this->init_OpenPayU();
        if (isset(static::$paymethods[$this->pos_id])) {
            return static::$paymethods[$this->pos_id];
        } else {
            try {
                static::$paymethods[$this->pos_id] = OpenPayU_Retrieve::payMethods();
                return static::$paymethods[$this->pos_id];
            } catch (OpenPayU_Exception $e) {
                unset($e);
            }
        }
    }

    /**
     * @return string
     */
    protected function get_condition_url()
    {
        $language = get_locale();
        switch ($language) {
            case 'pl_PL':
                return self::CONDITION_PL;
            case 'cs_CZ':
                return self::CONDITION_CS;
            default:
                return self::CONDITION_EN;
        }
    }

    /**
     * @return string
     */
    protected function get_privacy_policy_url()
    {
        return get_locale() === 'pl_PL' ? self::PRIVACY_PL : self::PRIVACY_EN;
    }

    /**
     * @return void
     */
    protected function agreements_field()
    {
        if ($this->has_terms_checkbox) {
            echo '<div class="payu-accept-conditions">';
            echo '<div class="payu-checkbox-line"><label><input name="condition-checkbox-' . $this->id . '" type="checkbox" checked="checked" required="required" />';
            printf(__('<span>I accept <a href="%s" target="_blank">Terms of single PayU payment transaction</a></span>',
                'payu'),
                $this->get_condition_url());
            echo '</label></div>';
            echo '<div class="payu-conditions-description">' . __('Payment is processed by PayU SA; The recipient\'s data, the payment title and the amount are provided to PayU SA by the recipient;',
                    'payu') . ' <span class="payu-read-more">' . __('read more',
                    'payu') . '</span> <span class="payu-more-hidden">' . __('The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees.',
                    'payu') . '</span><br />';
            echo __('The controller of your personal data is PayU S.A. with its registered office in Poznan (60-166), at Grunwaldzka Street 182 ("PayU").',
                    'payu') . ' <span class="payu-read-more">' . __('read more',
                    'payu') . '</span> <span class="payu-more-hidden">';
            echo __('Your personal data will be processed for purposes of processing  payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.',
                    'payu') . '<br />';
            echo __('The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.',
                    'payu') . '<br />';
            echo __('You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click ',
                'payu');
            printf(__('<a href="%s" target="_blank">PayU privacy policy</a>', 'payu'), $this->get_privacy_policy_url());
            echo '</span>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * @param string $id
     *
     * @return void
     */
    protected function setup_properties($id)
    {
        $this->id = $id;
        $this->method_title = $this->gateway_data('name');
        $this->method_description = __('Official PayU payment gateway for WooCommerce.', 'payu');
        $this->has_fields = false;
        $this->supports = ['products', 'refunds'];
    }

    /**
     * @return array
     */
    public static function gateways_list()
    {
        return [
            'payustandard' => [
                'name' => __('PayU - standard', 'payu'),
                'front_name' => __('Online payment by PayU', 'payu'),
                'default_description' => __('You will be redirected to a payment method selection page.', 'payu'),
                'api' => 'WC_Gateway_PayuStandard'
            ],
            'payulistbanks' => [
                'name' => __('PayU - list banks', 'payu'),
                'front_name' => __('Online payment by PayU', 'payu'),
                'default_description' => __('Choose payment method.', 'payu'),
                'api' => 'WC_Gateway_PayuListBanks'
            ],
            'payucreditcard' => [
                'name' => __('PayU - credit card', 'payu'),
                'front_name' => __('Card payment with PayU', 'payu'),
                'default_description' => __('You will be redirected to a card form.', 'payu'),
                'api' => 'WC_Gateway_PayuCreditCard'
            ],
            'payusecureform' => [
                'name' => __('PayU - secure form', 'payu'),
                'front_name' => __('Card payment with PayU', 'payu'),
                'default_description' => __('You may be redirected to a payment confirmation page.', 'payu'),
                'api' => 'WC_Gateway_PayuSecureForm'
            ],
            'payublik' => [
                'name' => __('PayU - Blik', 'payu'),
                'front_name' => __('Blik', 'payu'),
                'default_description' => __('You will be redirected to BLIK.', 'payu'),
                'api' => 'WC_Gateway_PayuBlik'
            ],
            'payuinstallments' => [
                'name' => __('PayU - installments', 'payu'),
                'front_name' => __('PayU installments', 'payu'),
                'default_description' => __('You will be redirected to an installment payment application.', 'payu'),
                'api' => 'WC_Gateway_PayuInstallments'
            ]
        ];
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function gateway_data($field)
    {
        $names = self::gateways_list();
        return $names[$this->id][$field];
    }

    function init_form_fields() {
        $this->payu_init_form_fields();
    }

    /**
     * @param bool $custom_order
     * @return void
     */
    function payu_init_form_fields($custom_order = false)
    {
        global $woocommerce_wpml;

        $currencies = [];

        if (is_wmpl_active_and_configure()) {
            $currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
        }

        $this->form_fields = array_merge(
            $this->get_form_fields_basic(),
            $this->get_form_field_config($currencies),
            $this->get_form_field_info(),
            $custom_order ? $this->get_form_custom_order() : []
        );
    }

    /**
     * @return array
     */
    private function get_form_fields_basic()
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => __('Enable PayU payment method', 'payu'),
                'type' => 'checkbox',
                'description' => __('If you do not already have PayU merchant account, <a href="https://poland.payu.com/en/how-to-activate-payu/" target="_blank" rel="nofollow">please register in Production</a> or <a href="https://secure.snd.payu.com/boarding/#/registerSandbox/?lang=en" target="_blank" rel="nofollow">please register in Sandbox</a>.',
                    'payu'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title:', 'payu'),
                'type' => 'text',
                'description' => __('Title of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
                'default' => self::gateways_list()[$this->id]['front_name'],
                'desc_tip' => true
            ],
            'sandbox' => [
                'title' => __('Sandbox mode:', 'payu'),
                'type' => 'checkbox',
                'label' => __('Use sandbox environment.', 'payu'),
                'default' => 'no'
            ],
            'use_global' => [
                'title' => __('Use global values:', 'payu'),
                'type' => 'checkbox',
                'label' => __('Use global values.', 'payu'),
                'default' => 'yes',
                'custom_attributes' => ['data-toggle-global' => '1']
            ]
        ];
    }

    /**
     * @return array
     */
    private function get_form_custom_order()
    {
        return [
            'custom_order' => [
                'title' => __('Custom order:', 'payu'),
                'type' => 'text',
                'description' => __('Custom order, separate payment methods with commas', 'payu'),
                'placeholder' => __('Custom order, separate payment methods with commas', 'payu'),
                'desc_tip' => true
            ],
            'show_inactive_methods' => [
                'title' => __('Show inactive methods:', 'payu'),
                'type' => 'checkbox',
                'description' => __('Show inactive payment methods as grayed out', 'payu'),
                'label' => __('show', 'payu'),
                'desc_tip' => true
            ]
        ];
    }

    /**
     * @param array $currencies
     *
     * @return array
     */
    private function get_form_field_config($currencies = [])
    {
        if (count($currencies) < 2) {
            $currencies = [''];
        }
        $config = [];

        foreach ($currencies as $code) {
            $idSuffix = ($code ? '_' : '') . $code;
            $namePrefix = $code . ($code ? ' - ' : '');
            $fields = PayUSettings::payu_fields();
            $settings = [];
            foreach ($fields as $field => $desc) {
                $field = $field . $idSuffix;
                $settings[$field] = [
                    'title' => $namePrefix . $desc['label'],
                    'type' => 'text',
                    'description' => $namePrefix . $desc['description'],
                    'desc_tip' => true,
                    'custom_attributes' => [
                        'data-global' => 'can-be-global',
                        'global-value' => $this->get_payu_option(['payu_settings_option_name', 'global_' . $field]),
                        'local-value' => $this->get_payu_option(['woocommerce_' . $this->id . '_settings', $field])
                    ],
                ];
            }
            $config += $settings;
        }
        return $config;
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get_payu_option($key)
    {
        if (!is_array($key)) {
            return false;
        }
        if (@get_option($key[0])[$key[1]]) {
            return get_option($key[0])[$key[1]];
        }
        return false;
    }

    /**
     * @return array
     */
    private function get_form_field_info()
    {
        return [
            'description' => [
                'title' => __('Description:', 'payu'),
                'type' => 'text',
                'description' => __('Description of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
                'default' => self::gateways_list()[$this->id]['default_description'],
                'desc_tip' => true
            ],
            'enable_for_shipping' => [
                'title' => __('Enable for shipping methods', 'payu'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 400px;',
                'default' => '',
                'description' => __('If PayU is only available for certain methods, set it up here. Leave blank to enable for all methods.',
                    'payu'),
                'options' => $this->getShippingMethods(),
                'desc_tip' => true,
                'custom_attributes' => [
                    'data-placeholder' => __('Select shipping methods', 'payu'),
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws
     */
    private function getShippingMethods()
    {
        // Since this is expensive, we only want to do it if we're actually on the settings page.
        if (!$this->is_accessing_settings()) {
            return [];
        }

        $data_store = WC_Data_Store::load('shipping-zone');
        $raw_zones = $data_store->get_zones();

        foreach ($raw_zones as $raw_zone) {
            $zones[] = new WC_Shipping_Zone($raw_zone);
        }

        $zones[] = new WC_Shipping_Zone(0);

        $options = [];
        foreach (WC()->shipping()->load_shipping_methods() as $method) {

            $options[$method->get_method_title()] = [];

            // Translators: %1$s shipping method name.
            $options[$method->get_method_title()][$method->id] = sprintf(__('Any &quot;%1$s&quot; method',
                'woocommerce'), $method->get_method_title());

            foreach ($zones as $zone) {

                $shipping_method_instances = $zone->get_shipping_methods();

                foreach ($shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance) {

                    if ($shipping_method_instance->id !== $method->id) {
                        continue;
                    }

                    $option_id = $shipping_method_instance->get_rate_id();

                    // Translators: %1$s shipping method title, %2$s shipping method id.
                    $option_instance_title = sprintf(__('%1$s (#%2$s)', 'woocommerce'),
                        $shipping_method_instance->get_title(), $shipping_method_instance_id);

                    // Translators: %1$s zone name, %2$s shipping method instance name.
                    $option_title = sprintf(__('%1$s &ndash; %2$s', 'woocommerce'),
                        $zone->get_id() ? $zone->get_zone_name() : __('Other locations', 'woocommerce'),
                        $option_instance_title);

                    $options[$method->get_method_title()][$option_id] = $option_title;
                }
            }
        }

        return $options;
    }

    /**
     * Checks to see whether or not the admin settings are being accessed by the current request.
     * Copy from COD module
     *
     * @return bool
     */
    private function is_accessing_settings()
    {
        if (is_admin()) {
            if (!isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                return false;
            }
            if (!isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
                return false;
            }

            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            global $wp;
            if (isset($wp->query_vars['rest_route']) && false !== strpos($wp->query_vars['rest_route'],
                    '/payment_gateways')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $currency
     * @throws
     *
     * @return void
     */
    public function init_OpenPayU($currency = null)
    {
        $isSandbox = 'yes' === $this->get_option('sandbox');

        if (is_wmpl_active_and_configure()) {
            $optionSuffix = '_' . (null !== $currency ? $currency : get_woocommerce_currency());
        } else {
            $optionSuffix = '';
        }

        $optionPrefix = $isSandbox ? 'sandbox_' : '';

        OpenPayU_Configuration::setEnvironment($isSandbox ? 'sandbox' : 'secure');
        if ($this->get_option('use_global') === 'yes' || !$this->get_option('use_global')) {
            $this->pos_id = $this->get_payu_option([
                'payu_settings_option_name',
                'global_' . $optionPrefix . 'pos_id' . $optionSuffix
            ]);
            OpenPayU_Configuration::setMerchantPosId($this->pos_id);
            OpenPayU_Configuration::setSignatureKey($this->get_payu_option([
                'payu_settings_option_name',
                'global_' . $optionPrefix . 'md5' . $optionSuffix
            ]));
            OpenPayU_Configuration::setOauthClientId($this->get_payu_option([
                'payu_settings_option_name',
                'global_' . $optionPrefix . 'client_id' . $optionSuffix
            ]));
            OpenPayU_Configuration::setOauthClientSecret($this->get_payu_option([
                'payu_settings_option_name',
                'global_' . $optionPrefix . 'client_secret' . $optionSuffix
            ]));
        } else {
            $this->pos_id = $this->get_option($optionPrefix . 'pos_id' . $optionSuffix);
            OpenPayU_Configuration::setMerchantPosId($this->pos_id);
            OpenPayU_Configuration::setSignatureKey($this->get_option($optionPrefix . 'md5' . $optionSuffix));
            OpenPayU_Configuration::setOauthClientId($this->get_option($optionPrefix . 'client_id' . $optionSuffix));
            OpenPayU_Configuration::setOauthClientSecret($this->get_option($optionPrefix . 'client_secret' . $optionSuffix));
        }


        OpenPayU_Configuration::setOauthTokenCache(new OauthCacheWP());
        OpenPayU_Configuration::setSender('Wordpress ver ' . get_bloginfo('version') . ' / WooCommerce ver ' . WC()->version . ' / Plugin ver ' . PAYU_PLUGIN_VERSION);
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
        } elseif (is_page(wc_get_page_id('checkout')) && get_query_var('order-pay') > 0) {
            $order = wc_get_order(absint(get_query_var('order-pay')));
            add_post_meta($order->get_id(), '_test_key', 'randomvalue');
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
     * Copy from COD
     *
     * @param array $order_shipping_items Array of WC_Order_Item_Shipping objects.
     *
     * @return array $canonical_rate_ids    Rate IDs in a canonical format.
     */
    private function get_canonical_order_shipping_item_rate_ids($order_shipping_items)
    {

        $canonical_rate_ids = [];

        foreach ($order_shipping_items as $order_shipping_item) {
            $canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
        }

        return $canonical_rate_ids;
    }

    /**
     * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
     * Copy from COD
     *
     * @param array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
     *
     * @return array $canonical_rate_ids  Rate IDs in a canonical format.
     */
    private function get_canonical_package_rate_ids($chosen_package_rate_ids)
    {

        $shipping_packages = WC()->shipping()->get_packages();
        $canonical_rate_ids = [];

        if (!empty($chosen_package_rate_ids) && is_array($chosen_package_rate_ids)) {
            foreach ($chosen_package_rate_ids as $package_key => $chosen_package_rate_id) {
                if (!empty($shipping_packages[$package_key]['rates'][$chosen_package_rate_id])) {
                    $chosen_rate = $shipping_packages[$package_key]['rates'][$chosen_package_rate_id];
                    $canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
                }
            }
        }

        return $canonical_rate_ids;
    }

    /**
     * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
     *
     *
     * @param array $rate_ids Rate ids to check.
     *
     * @return array
     */
    private function get_matching_rates($rate_ids)
    {
        // First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
        return array_unique(array_merge(array_intersect($this->enable_for_shipping, $rate_ids),
            array_intersect($this->enable_for_shipping,
                array_unique(array_map('wc_get_string_before_colon', $rate_ids)))));
    }

    /**
     * @param int $order_id
     *
     * @return array|bool
     *
     */
    function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $this->init_OpenPayU();
        $billingData = $order->get_address();
        $orderData = [
            'continueUrl' => $this->get_return_url($order),
            'notifyUrl' => add_query_arg('wc-api', $this->gateway_data('api'), home_url('/')),
            'customerIp' => $this->getIP(),
            'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'description' => get_bloginfo('name') . ' #' . $order->get_order_number(),
            'currencyCode' => get_woocommerce_currency(),
            'totalAmount' => $this->toAmount($order->get_total()),
            'extOrderId' => uniqid($order_id . '_', true),
            'products' => [
                [
                    'name' => get_bloginfo('name') . ' #' . $order->get_order_number(),
                    'unitPrice' => $this->toAmount($order->get_total()),
                    'quantity' => 1
                ]
            ],
            'buyer' => [
                'email' => $billingData['email'],
                'phone' => $billingData['phone'],
                'firstName' => $billingData['first_name'],
                'lastName' => $billingData['last_name'],
                'language' => $this->getLanguage()
            ]
        ];
        if ($this->id !== 'payustandard') {
            $orderData['payMethods'] = $this->get_payu_pay_method();
        }

        try {
            if ($this->has_terms_checkbox) {
                if (!isset($_POST['condition-checkbox-' . $this->id]) || $_POST['condition-checkbox-' . $this->id] !== 'on') {
                    wc_add_notice(__('Payment error: accept terms and conditions', 'payu'), 'error');
                    return false;
                }
            }
            $response = OpenPayU_Order::create($orderData);

            if ($response->getStatus() === OpenPayU_Order::SUCCESS || $response->getStatus() === 'WARNING_CONTINUE_3DS') {

                $this->reduceStock($order);
                WC()->cart->empty_cart();

                //add link to email
                if (isset(get_option('payu_settings_option_name')['global_repayment'])) {
                    add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);
                }

                $order->update_status(get_option('payu_settings_option_name')['global_default_on_hold_status'],
                    __('Awaiting PayU payment.', 'payu'));

                update_post_meta($order_id, '_transaction_id', $response->getResponse()->orderId);
                update_post_meta($order_id, '_payu_payment_method', $this->selected_method, true);
                $redirect = $this->get_return_url($order);
                if ($response->getResponse()->redirectUri) {
                    $redirect = $response->getResponse()->redirectUri;
                }
                $result = [
                    'result' => 'success',
                    'redirect' => $redirect
                ];

                return $result;
            } else {
                wc_add_notice(__('Payment error. Status code: ', 'payu') . $response->getStatus(), 'error');

                return false;
            }
        } catch (OpenPayU_Exception $e) {
            wc_add_notice(__('Payment error: ', 'payu') . $e->getMessage() . ' (' . $e->getCode() . ')', 'error');

            return false;
        }
    }

    /**
     * @return string
     */
    protected function getIP()
    {
        return ($_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return string
     */
    protected function getLanguage()
    {
        return substr(get_locale(), 0, 2);
    }

    /**
     * @param WC_Order $order
     *
     * @return void
     */
    protected function reduceStock($order)
    {
        function_exists('wc_reduce_stock_levels') ?
            wc_reduce_stock_levels($order->get_id()) : $order->reduce_order_stock();
    }

    /**
     * @param float $value
     *
     * @return int
     */
    protected function toAmount($value)
    {
        return (int)round($value * 100);
    }

    /**
     * @param int $order_id
     *
     * @return string|bool
     */
    protected function completed_transaction_id($order_id)
    {
        $payu_statuses = get_post_meta($order_id, '_payu_order_status');
        foreach ($payu_statuses as $payu_status) {
            $ps = explode('|', $payu_status);
            if ($ps[0] === OpenPayuOrderStatus::STATUS_COMPLETED) {
                return $ps[1];
            }
        }
        return false;
    }

    /**
     * @param int $order_id
     * @param null|float $amount
     * @param string $reason
     *
     * @throws
     *
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if($amount > 0) {
            $order = wc_get_order($order_id);
            $orderId = $this->completed_transaction_id($order_id);
            if (empty($orderId)) {
                return false;
            }

            $this->init_OpenPayU($order->get_currency());
            $refund = OpenPayU_Refund::create(
                $orderId,
                __('Refund of: ', 'payu') . ' ' . $amount . $this->getOrderCurrency($order) . __(' for order: ',
                    'payu') . $order_id,
                $this->toAmount($amount)
            );


            return ($refund->getStatus() === 'SUCCESS');
        }
        return false;
    }

    /**
     * @param WC_Order $order
     *
     * @return string
     */
    private function getOrderCurrency($order)
    {
        return method_exists($order, 'get_currency') ? $order->get_currency() : $order->get_order_currency();
    }

    /**
     * @return array
     */
    protected function get_payu_pay_method()
    {
        return $this->get_payu_pay_method_array('PBL', $this->paytype);
    }

    /**
     * @param string $type
     * @param string $value
     * @return array
     */
    protected function get_payu_pay_method_array($type, $value, $paymethod = null)
    {
        $this->selected_method = $paymethod ? $paymethod : $value;

        return [
            'payMethod' => [
                'type' => $type,
                'value' => $value
            ]
        ];
    }

    /**
     * @param object $payMethod
     * @param string $paytype
     * @return bool
     */
    protected function check_min_max($payMethod, $paytype)
    {
        if ($payMethod->status === 'ENABLED' && $payMethod->value === $paytype) {
            if (isset($payMethod->minAmount) && $this->toAmount($this->cart_contents_total) < $payMethod->minAmount) {
                return false;
            }
            if (isset($payMethod->maxAmount) && $this->toAmount($this->cart_contents_total) > $payMethod->maxAmount) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $notification
     *
     * @return null|string
     */
    private function extractCurrencyFromNotification($notification)
    {
        $notification = json_decode($notification);

        if (is_object($notification) && $notification->order && $notification->order->currencyCode) {
            return $notification->order->currencyCode;
        } elseif (is_object($notification) && $notification->refund && $notification->refund->currencyCode) {
            return $notification->refund->currencyCode;
        }
        return null;
    }

    /**
     * @throws
     *
     * @return void
     */
    function gateway_ipn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            if (property_exists($response->getResponse(), 'refund')) {
                $reportOutput = 'Refund notification - ignore|';
                $order_id = (int)preg_replace('/_.*$/', '', $response->getResponse()->extOrderId);
                $order = wc_get_order($order_id);
                $note = '[PayU] ' . $response->getResponse()->refund->reasonDescription . ' ' . __('has status', 'payu') . ' ' . $response->getResponse()->refund->status;
                $order->add_order_note($note);
            } else {
                $order_id = (int)preg_replace('/_.*$/', '', $response->getResponse()->order->extOrderId);
                $status = $response->getResponse()->order->status;
                $transaction_id = $response->getResponse()->order->orderId;

                $reportOutput = 'OID: ' . $order_id . '|PS: ' . $status . '|TID: ' . $transaction_id . '|';

                $order = wc_get_order($order_id);

                $reportOutput .= 'WC AS: ' . $order->get_status() . '|';
                add_post_meta($order_id, '_payu_order_status',
                    $status . '|' . $response->getResponse()->order->orderId);
                if ($order->get_status() !== 'completed' && $order->get_status() !== 'processing') {
                    switch ($status) {
                        case OpenPayuOrderStatus::STATUS_CANCELED:
                            if (!isset(get_option('payu_settings_option_name')['global_repayment'])) {
                                $order->update_status('cancelled', __('Payment has been cancelled.', 'payu'));
                            }
                            break;

                        case OpenPayuOrderStatus::STATUS_REJECTED:
                            $order->update_status('failed', __('Payment has been rejected.', 'payu'));
                            break;

                        case OpenPayuOrderStatus::STATUS_COMPLETED:
                            $order->payment_complete($transaction_id);
                            break;

                        case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
                            if ($order->get_status() === 'cancelled') {
                                $response_order_id = $response->getResponse()->order->orderId;
                                OpenPayU_Order::cancel($response_order_id);
                            } else {
                                $order->update_status('payu-waiting',
                                    __('Payment has been put on hold - merchant must approve this payment manually.',
                                        'payu')
                                );
                                if (isset(get_option('payu_settings_option_name')['global_repayment'])) {
                                    $payu_statuses = get_post_meta($order_id, '_payu_order_status');

                                    if (in_array(OpenPayuOrderStatus::STATUS_COMPLETED,
                                        $this->clean_payu_statuses($payu_statuses))) {
                                        OpenPayU_Refund::create(
                                            $transaction_id,
                                            __('Refund of: ',
                                                'payu') . ' ' . $order->get_total() . $this->getOrderCurrency($order) . __(' for order: ',
                                                'payu') . $order_id,
                                            $this->toAmount($order->get_total())
                                        );
                                    } else {
                                        $status_update = [
                                            "orderId" => $transaction_id,
                                            "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                                        ];
                                        OpenPayU_Order::statusUpdate($status_update);
                                    }
                                }
                            }
                            break;
                    }
                } else {
                    if ($status === OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION) {
                        $response_order_id = $response->getResponse()->order->orderId;
                        OpenPayU_Order::cancel($response_order_id);
                    }
                }
                $reportOutput .= 'WC BS: ' . $order->get_status() . '|';
            }

            header("HTTP/1.1 200 OK");

            echo $reportOutput;
        }

        ob_flush();
    }

    /**
     * @param array $payu_statuses
     *
     * @return array
     */
    public static function clean_payu_statuses($payu_statuses)
    {
        $result = [];
        if (is_array($payu_statuses)) {
            foreach ($payu_statuses as $payu_status) {
                $status = explode('|', $payu_status)[0];
                array_push($result, $status);
            }
        }
        return $result;
    }

    /**
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     *
     * @return void
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if (!$sent_to_admin && $order->has_status(get_option('payu_settings_option_name')['global_default_on_hold_status'])) {
            $url = wc_get_endpoint_url('order-pay', $order->get_id(), wc_get_checkout_url()) . '?pay_for_order=true&key=' . $order->get_order_key();
            echo __('If you have not yet paid for the order, you can do so by going to', 'payu')
                . ($plain_text ?
                    ' ' . __('the website', 'payu') . ': ' . $url . "\n" :
                    ' <a href="' . $url . '">' . __('the website', 'payu') . '</a>.<br /><br />');
        }
    }

    /**
     * @param array $gateways
     *
     * @return array
     */
    public function unset_gateway($gateways)
    {
        unset($gateways[$this->id]);
        return $gateways;
    }
}
