<?php
/**
 * Plugin Name: PayU Payment Gateway
 * Plugin URI: https://github.com/PayU/woo-payu-payment-gateway
 * GitHub Plugin URI: https://github.com/PayU-EMEA/woo-payu-payment-gateway
 * Description: PayU payment gateway for WooCommerce
 * Version: 2.0.29
 * Author: PayU SA
 * Author URI: http://www.payu.com
 * License: Apache License 2.0
 * Text Domain: woo-payu-payment-gateway
 * Domain Path: /lang
 * WC requires at least: 3.0
 * WC tested up to: 7.7.1
 */

define('PAYU_PLUGIN_VERSION', '2.0.29');
define('PAYU_PLUGIN_FILE', __FILE__);
define('PAYU_PLUGIN_STATUS_WAITING', 'payu-waiting');

add_action('plugins_loaded', 'init_gateway_payu');
add_action('admin_init', 'move_old_payu_settings');

add_action(
    'before_woocommerce_init',
    function() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);
/**
 * Init function that runs after plugin install.
 */

function init_gateway_payu()
{
    handle_plugin_update();
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    load_plugin_textdomain('woo-payu-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/lang/');

    require_once('includes/WC_PayUGateways.php');
    require_once('includes/PayUSettings.php');
    require_once('includes/WC_Gateway_PayuCreditCard.php');
    require_once('includes/WC_Gateway_PayuListBanks.php');
    require_once('includes/WC_Gateway_PayuStandard.php');
    require_once('includes/WC_Gateway_PayuSecureForm.php');
    require_once('includes/WC_Gateway_PayuBlik.php');
    require_once('includes/WC_Gateway_PayuInstallments.php');
    require_once('includes/WC_Gateway_PayuPaypo.php');
    require_once('includes/WC_Gateway_PayuTwistoPl.php');

    add_filter('woocommerce_payment_gateways', 'add_payu_gateways');
    add_filter('woocommerce_valid_order_statuses_for_payment_complete', 'payu_filter_woocommerce_valid_order_statuses_for_payment_complete', 10, 2 );
    add_filter('woocommerce_email_actions', 'add_payu_order_status_to_email_notifications');
    add_filter('woocommerce_email_classes', 'add_payu_order_status_to_email_notifications_triger');

    if (!is_admin() && isset($_GET['pay_for_order'], $_GET['key'])) {
        add_filter('woocommerce_valid_order_statuses_for_payment',
            'payu_filter_woocommerce_valid_order_statuses_for_payment',
            10, 2);
    }
    add_filter('plugin_row_meta', 'plugin_row_meta', 10, 2);
}

//enable pbl and standard if first install
register_activation_hook(__FILE__, 'payu_plugin_on_activate');
function payu_plugin_on_activate()
{
    if (!get_option('woocommerce_payu_settings') && !get_option('_payu_plugin_version')) {
        add_option('_payu_plugin_version', PAYU_PLUGIN_VERSION);
        add_option('woocommerce_payulistbanks_settings', ['enabled' => 'yes']);
        add_option('woocommerce_payucreditcard_settings', ['enabled' => 'yes']);
        add_option('payu_settings_option_name', ['global_default_on_hold_status' => 'on-hold']);
        add_option('woocommerce_payuinstallments_settings', [
            'enabled' => 'no',
            'credit_widget_on_listings' => 'yes',
            'credit_widget_on_product_page' => 'yes',
            'credit_widget_on_cart_page' => 'yes',
            'credit_widget_on_checkout_page' => 'yes'
        ]);
    }
}

function handle_plugin_update() {
    if (PAYU_PLUGIN_VERSION !== get_option('_payu_plugin_version')) {
        update_option('_payu_plugin_version', PAYU_PLUGIN_VERSION);
        $defaultInstallmentsSettings = [
            'enabled' => 'no',
            'credit_widget_on_listings' => 'yes',
            'credit_widget_on_product_page' => 'yes',
            'credit_widget_on_cart_page' => 'yes',
            'credit_widget_on_checkout_page' => 'yes'
        ];
        $payuInstallmentsSettings = get_option('woocommerce_payuinstallments_settings');
        if(empty($payuInstallmentsSettings)) {
            add_option('woocommerce_payuinstallments_settings', $defaultInstallmentsSettings);
        } else {
            $mergedInstallmentsSettings = array_merge($defaultInstallmentsSettings, $payuInstallmentsSettings);
            update_option('woocommerce_payuinstallments_settings', $mergedInstallmentsSettings);
        }
    }
}

function move_old_payu_settings()
{
    if ($old_payu = get_option('woocommerce_payu_settings')) {
        unset($old_payu['payu_feedback']);

        $global = [];
        $standard = [];
        foreach ($old_payu as $key => $value) {
            if (!in_array($key, ['enabled', 'title', 'sandbox'])) {
                $global['global_' . $key] = $value;
            }
        }
        $global['global_default_on_hold_status'] = 'on-hold';
        update_option('payu_settings_option_name', $global);
        foreach ($old_payu as $key => $value) {
            if (!in_array($key, ['enabled', 'title', 'sandbox', 'description', 'enable_for_shipping'])) {
                $standard[$key] = '';
            } else {
                $standard[$key] = $value;
            }
        }
        $standard['use_global'] = 'yes';
        update_option('woocommerce_payustandard_settings', $standard);
        update_option('_payu_plugin_version', PAYU_PLUGIN_VERSION);
        delete_option('woocommerce_payu_settings');
    }
}

/**
 * @return array
 */
function payu_filter_woocommerce_valid_order_statuses_for_payment()
{
    return ['pending', 'failed', 'on-hold', PAYU_PLUGIN_STATUS_WAITING];
}

/**
 * @param array $statuses
 * @return array
 */
function payu_filter_woocommerce_valid_order_statuses_for_payment_complete($statuses)
{
    $statuses[] = 'payu-waiting';
    return $statuses;
}

/**
 * @param array $gateways
 *
 * @return array
 */
function add_payu_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_PayuCreditCard';
    $gateways[] = 'WC_Gateway_PayuListBanks';
    $gateways[] = 'WC_Gateway_PayUStandard';
    $gateways[] = 'WC_Gateway_PayuSecureForm';
    $gateways[] = 'WC_Gateway_PayuBlik';
    $gateways[] = 'WC_Gateway_PayuInstallments';
    $gateways[] = 'WC_Gateway_PayuPaypo';
    $gateways[] = 'WC_Gateway_PayuTwistoPl';
    return $gateways;
}

/**
 * @param array $actions
 *
 * @return array
 */
function add_payu_order_status_to_email_notifications($actions)
{
    $actions[] = 'woocommerce_order_status_'.PAYU_PLUGIN_STATUS_WAITING.'_to_processing';

    return $actions;
}

/**
 * @param array $clases
 *
 * @return array
 */
function add_payu_order_status_to_email_notifications_triger($clases)
{
    if ($clases['WC_Email_Customer_Processing_Order']) {
        add_action('woocommerce_order_status_' . PAYU_PLUGIN_STATUS_WAITING . '_to_processing_notification', array($clases['WC_Email_Customer_Processing_Order'], 'trigger'), 10, 2);
    }

    return $clases;
}

function plugin_row_meta($links, $plugin_file) {
    if (strpos($plugin_file, 'woo-payu-payment-gateway') === false) {
        return $links;
    }
    $row_meta = array(
        'docs' => '<a href="' . esc_url( 'https://github.com/PayU-EMEA/woo-payu-payment-gateway' ) . '">' . esc_html__( 'Docs', 'woo-payu-payment-gateway' ) . '</a>'
    );

    return array_merge( $links, $row_meta );
}

//enqueue assets
function enqueue_payu_admin_assets()
{
    wp_enqueue_script('payu-admin', plugins_url( '/assets/js/payu-admin.js', PAYU_PLUGIN_FILE ), ['jquery'], PAYU_PLUGIN_VERSION);
    wp_enqueue_style('payu-admin', plugins_url( '/assets/css/payu-admin.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION);
}

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'enqueue_payu_admin_assets');
}

function get_installment_option($option)
{

    $paymentGateways = WC()->payment_gateways->payment_gateways();
    $result = null;

    if (array_key_exists('payuinstallments', $paymentGateways)) {

        /**
         * @var  $installmentsGateway WC_Gateway_PayuInstallments
         */
        $installmentsGateway = $paymentGateways['payuinstallments'];

        switch ($option) {
            case 'pos_id':
                $result = $installmentsGateway->pos_id;
                break;
            case 'widget_key':
                $result = $installmentsGateway->pos_widget_key;
                break;
            case 'enable_for_shipping':
                $result = $installmentsGateway->enable_for_shipping;
                break;
            case 'enable_for_virtual':
                $result = $installmentsGateway->enable_for_virtual;
                break;
        }
    }

    return $result;
}

function is_installments_widget_available_for_feature($featureName) {
    return !empty(get_option('woocommerce_payuinstallments_settings')) &&
        get_option('woocommerce_payuinstallments_settings')['enabled'] === 'yes' &&
        get_option('woocommerce_payuinstallments_settings')[$featureName] === 'yes';
}

if(is_installments_widget_available_for_feature('credit_widget_on_listings')) {
    add_action('woocommerce_after_shop_loop_item', 'installments_mini');
}

if(is_installments_widget_available_for_feature('credit_widget_on_product_page')) {
    add_action('woocommerce_before_add_to_cart_form', 'installments_mini');
}

function installments_mini() {
    if(get_woocommerce_currency() !== 'PLN') {
        return;
    }
    global $product;
    $price = wc_get_price_including_tax($product);
    $productId = $product->get_id();

    $posId = get_installment_option('pos_id');
    $widgetKey = get_installment_option('widget_key');

    wp_enqueue_script('payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION);

    ?>
        <div>
            <span id="installment-mini-<?php echo esc_html($productId)?>"></span>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function () {
                    var value = <?php echo esc_html($price)?>;
                    var options = {
                        creditAmount: value,
                        posId: '<?php echo esc_html($posId)?>',
                        key: '<?php echo esc_html($widgetKey)?>',
                        showLongDescription: true
                    };
                    OpenPayU.Installments.miniInstallment('#installment-mini-<?php echo esc_html($productId)?>', options);
                });
            </script>
        </div>
    <?php
}


if(is_installments_widget_available_for_feature('credit_widget_on_cart_page')) {
    add_action('woocommerce_cart_totals_after_order_total', 'installments_mini_cart');
}

function is_shipping_method_in_supported_methods_set($chosenShippingMethod, $availableShippingMethods) {
    if(empty($availableShippingMethods)) {
        return true;
    }
    if(!empty($chosenShippingMethod)) {
        foreach($availableShippingMethods as $supportedShippingMethod) {
            if(strpos($chosenShippingMethod, $supportedShippingMethod) === 0) {
                return true;
            }
        }
    }
    return false;
}

add_action('wc_ajax_payu_installments_get_cart_total', 'installments_get_cart_total' );
function installments_get_cart_total() {
    $price = WC()->cart->get_total('');
    echo $price;
    wp_die();
}
function installments_mini_cart() {
    if(get_woocommerce_currency() !== 'PLN') {
        return;
    }

    $chosenShippingMethod = WC()->session->get('chosen_shipping_methods')[0];
    $supportedInstallmentShippingMethods = get_installment_option('enable_for_shipping');
    if(!is_shipping_method_in_supported_methods_set($chosenShippingMethod, $supportedInstallmentShippingMethods)) {
        return;
    }

    $price = WC()->cart->get_total('');

    $posId = get_installment_option('pos_id');
    $widgetKey = get_installment_option('widget_key');

    wp_enqueue_script('payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION);

    ?>
    <tr>
        <td></td>
        <td>
            <span id="installment-mini-cart"></span>
            <script type="text/javascript">
                var priceTotal = <?php echo esc_html($price)?>;
                function showInstallmentsWidget() {
                    if (window.OpenPayU) {
                        var options = {
                            creditAmount: priceTotal,
                            posId: '<?php echo esc_html($posId)?>',
                            key: '<?php echo esc_html($widgetKey)?>',
                            showLongDescription: true
                        };
                        OpenPayU.Installments.miniInstallment('#installment-mini-cart', options);
                    }
                }
                document.addEventListener("DOMContentLoaded", showInstallmentsWidget);
                jQuery(document.body).on('updated_cart_totals', function(){
                    var data = {
                        'action': 'installments_get_cart_total'
                    };
                    jQuery.post(woocommerce_params.wc_ajax_url.toString().replace('%%endpoint%%', 'payu_installments_get_cart_total'), data, function(response) {
                        priceTotal = Number(response);
                        showInstallmentsWidget();
                    });
                });

                showInstallmentsWidget();
            </script>
        </td>
    </tr>
    <?php
}

if(is_installments_widget_available_for_feature('credit_widget_on_listings')) {
    add_filter('woocommerce_blocks_product_grid_item_html', 'installments_mini_aware_product_block', 10, 3);
}
function installments_mini_aware_product_block( $html, $data, $product ) {
    if(get_woocommerce_currency() !== 'PLN') {
        return;
    }
    $price = wc_get_price_including_tax($product);
    $productId = $product->get_id();

    $posId = get_installment_option('pos_id');
    $widgetKey = get_installment_option('widget_key');

    wp_enqueue_script('payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION);

    return "<li class=\"wc-block-grid__product\">
        <div >
            <a href=\"{$data->permalink}\" class=\"wc-block-grid__product-link\">
               {$data->image}
			   {$data->title}
            </a>
		</div>
            {$data->badge}
            {$data->price}
			<div>
				<p><span id=\"installment-mini-{$productId}\"></span></p>
				<script type=\"text/javascript\">
				document.addEventListener(\"DOMContentLoaded\", function () {
                        var value = {$price};
                        var options = {
                            creditAmount: value,
                            posId: '{$posId}',
                            key: '{$widgetKey}',
                            showLongDescription: true
                        };
                        OpenPayU.Installments.miniInstallment('#installment-mini-{$productId}', options);
					});
				</script>
			</div>
            {$data->rating}
            {$data->button}
         </li>";

}

add_action('woocommerce_view_order', 'view_order');
function view_order($order_id)
{
    wp_enqueue_style('payu-gateway', plugins_url( '/assets/css/payu-gateway.css', PAYU_PLUGIN_FILE ),
        [], PAYU_PLUGIN_VERSION);

    $order = wc_get_order($order_id);
    $payu_gateways = WC_PayUGateways::gateways_list();
    if (in_array($order->get_status(), ['on-hold', 'pending', 'failed'])) {
        if (@$payu_gateways[$order->get_payment_method()] && isset(get_option('payu_settings_option_name')['global_repayment'])) {
            $pay_now_url = add_query_arg([
                'pay_for_order' => 'true',
                'key' => $order->get_order_key()
            ], wc_get_endpoint_url('order-pay', $order->get_id(), wc_get_checkout_url()));

            ?>
            <a href="<?php echo esc_url($pay_now_url) ?>" class="autonomy-payu-button"><?php esc_html_e('Pay with', 'woo-payu-payment-gateway'); ?>
                <img src="<?php echo esc_url(plugins_url( '/assets/images/logo-payu.svg', PAYU_PLUGIN_FILE )) ?>"/>
            </a>
            <?php
        }
    }
}

// Register new status
function register_waiting_payu_order_status()
{
    register_post_status('wc-'. PAYU_PLUGIN_STATUS_WAITING,
        [
            'label' => __('Awaiting receipt of payment', 'woo-payu-payment-gateway'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
        ]
    );
}

/**
 * @return bool
 */
function woocommerce_payu_is_wmpl_active_and_configure()
{
    global $woocommerce_wpml;

    return $woocommerce_wpml
        && property_exists($woocommerce_wpml, 'multi_currency')
        && $woocommerce_wpml->multi_currency
        && count($woocommerce_wpml->multi_currency->get_currency_codes()) > 1;
}

/**
 * @return bool
 */
function woocommerce_payu_is_currency_custom_config()
{
    return apply_filters('woocommerce_payu_multicurrency_active', false)
        && count(apply_filters('woocommerce_payu_get_currency_codes', [])) > 1;
}

/**
 * @return array
 */
function woocommerce_payu_get_currencies()
{
    global $woocommerce_wpml;

    $currencies = [];

    if (woocommerce_payu_is_wmpl_active_and_configure()) {
        $currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
    } elseif (woocommerce_payu_is_currency_custom_config()) {
        $currencies = apply_filters('woocommerce_payu_get_currency_codes', []);
    }

    return $currencies;
}

add_action('init', 'register_waiting_payu_order_status');

// Add to list of WC Order statuses
function add_waiting_payu_to_order_statuses($order_statuses)
{
    $order_statuses['wc-'.PAYU_PLUGIN_STATUS_WAITING] = __('Awaiting receipt of payment', 'woo-payu-payment-gateway');
    return $order_statuses;
}

add_filter('wc_order_statuses', 'add_waiting_payu_to_order_statuses');

//remove pay button
function filter_woocommerce_my_account_my_orders_actions($actions, $order)
{
    // Get status
    $order_status = $order->get_status();

    if (in_array($order_status,
        [
            'failed',
            PAYU_PLUGIN_STATUS_WAITING,
            get_option('payu_settings_option_name')['global_default_on_hold_status']
        ])) {
        $payu_gateways = WC_PayUGateways::gateways_list();
        if (@$payu_gateways[$order->get_payment_method()] && isset(get_option('payu_settings_option_name')['global_repayment'])) {
            $actions['repayu'] = [
                'name' => __('Pay with PayU', 'woo-payu-payment-gateway'),
                'url' => wc_get_endpoint_url('order-pay', $order->get_id(),
                        wc_get_checkout_url()) . '?pay_for_order=true&key=' . $order->get_order_key()
            ];
            unset($actions['pay']);
        }

    }

    return $actions;
}

add_filter('woocommerce_my_account_my_orders_actions', 'filter_woocommerce_my_account_my_orders_actions', 10, 2);
add_action('woocommerce_order_item_add_action_buttons', 'wc_order_item_add_action_buttons_callback', 10, 1);

function wc_order_item_add_action_buttons_callback($order)
{
    $payu_gateways = WC_PayUGateways::gateways_list();
    $payuOrderStatus = $order->get_meta('_payu_order_status', false, '');

    if (@$payu_gateways[$order->get_payment_method()] && !isset(get_option('payu_settings_option_name')['global_repayment']) && $payuOrderStatus) {
        $payu_statuses = WC_PayUGateways::clean_payu_statuses($payuOrderStatus);
        if ((!in_array(OpenPayuOrderStatus::STATUS_COMPLETED,
                    $payu_statuses) && !in_array(OpenPayuOrderStatus::STATUS_CANCELED,
                    $payu_statuses)) && in_array(OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION,
                $payu_statuses)) {
            $url_receive = add_query_arg([
                'post' => $order->get_id(),
                'action' => 'edit',
                'receive-payment' => 1
            ], admin_url('post.php'));
            $url_discard = add_query_arg([
                'post' => $order->get_id(),
                'action' => 'edit',
                'discard-payment' => 1
            ], admin_url('post.php'));
            ?>
            <a href="<?php echo esc_url($url_receive) ?>" type="button"
               class="button receive-payment"><?php esc_html_e('Receive payment', 'woo-payu-payment-gateway') ?></a>
            <a href="<?php echo esc_url($url_discard) ?>" type="button"
               class="button discard-payment"><?php esc_html_e('Discard payment', 'woo-payu-payment-gateway') ?></a>
            <?php
            $url_return = add_query_arg([
                'post' => $order->get_id(),
                'action' => 'edit',
            ], admin_url('post.php'));
            if (is_admin() && (isset($_GET['receive-payment']) || isset($_GET['discard-payment']))) {
                global $current_user;
                wp_get_current_user();

                if (isset($_GET['receive-payment']) && !isset($_GET['discard-payment'])) {
                    $orderId = $order->get_transaction_id();
                    $status_update = [
                        "orderId" => $orderId,
                        "orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
                    ];
                    $payment_method_name = $order->get_payment_method();
                    $payment_init = WC_PayUGateways::gateways_list()[$payment_method_name]['api'];
                    $payment = new $payment_init;
                    $payment->init_OpenPayU($order->get_currency());
                    OpenPayU_Order::statusUpdate($status_update);
                    $order->add_order_note(sprintf(__('[PayU] User %s accepted payment', 'woo-payu-payment-gateway'),
                        $current_user->user_login));
                    wp_redirect($url_return);
                }
                if (!isset($_GET['receive-payment']) && isset($_GET['discard-payment'])) {
                    $payment_method_name = $order->get_payment_method();
                    $payment_init = WC_PayUGateways::gateways_list()[$payment_method_name]['api'];
                    $payment = new $payment_init;
                    $payment->init_OpenPayU($order->get_currency());
                    $orderId = $order->get_transaction_id();
                    OpenPayU_Order::cancel($orderId);
                    $order->add_order_note(sprintf(__('[PayU] User %s rejected payment', 'woo-payu-payment-gateway'),
                        $current_user->user_login));
                    wp_redirect($url_return);
                }
            }
        }
    }
}
