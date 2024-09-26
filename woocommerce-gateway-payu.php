<?php
/**
 * Plugin Name: PayU Payment Gateway for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://github.com/PayU/woo-payu-payment-gateway
 * GitHub Plugin URI: https://github.com/PayU-EMEA/woo-payu-payment-gateway
 * Description: PayU fast online payments for WooCommerce. Banks, BLIK, credit or debit cards, Installments, Apple Pay, Google Pay.
 * Version: 2.6.1
 * Author: PayU SA
 * Author URI: http://www.payu.com
 * License: Apache License 2.0
 * Text Domain: woo-payu-payment-gateway
 * Domain Path: /lang
 * WC requires at least: 4.0
 * WC tested up to: 9.3.2
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Payu\PaymentGateway\Blocks\PayuBlikBlock;
use Payu\PaymentGateway\Blocks\PayuCreditCardBlock;
use Payu\PaymentGateway\Blocks\PayuInstallmentsBlock;
use Payu\PaymentGateway\Blocks\PayuKlarnaBlock;
use Payu\PaymentGateway\Blocks\PayuListBanksBlock;
use Payu\PaymentGateway\Blocks\PayuPaypoBlock;
use Payu\PaymentGateway\Blocks\PayuSecureFormBlock;
use Payu\PaymentGateway\Blocks\PayuStandardBlock;
use Payu\PaymentGateway\Blocks\PayuTwistoPlBlock;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuBlik;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuCreditCard;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuInstallments;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuKlarna;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuListBanks;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuPaypo;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuSecureForm;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuStandard;
use Payu\PaymentGateway\Gateways\WC_Gateway_PayuTwistoPl;
use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;

require __DIR__ . '/vendor/autoload.php';

define( 'PAYU_PLUGIN_VERSION', '2.6.1' );
define( 'PAYU_PLUGIN_FILE', __FILE__ );
define( 'PAYU_PLUGIN_STATUS_WAITING', 'payu-waiting' );

define( 'WC_PAYU_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_PAYU_PLUGIN_URL', trailingslashit( plugins_url( basename( WC_PAYU_PLUGIN_PATH ), basename( __FILE__ ) ) ) );

add_action( 'plugins_loaded', 'init_gateway_payu' );
add_action( 'woocommerce_blocks_loaded', 'init_payu_blocks' );
add_action( 'admin_init', 'move_old_payu_settings' );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
		}
	}
);

function init_payu_blocks() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new PayuStandardBlock() );
				$payment_method_registry->register( new PayuListBanksBlock() );
				$payment_method_registry->register( new PayuCreditCardBlock() );
				$payment_method_registry->register( new PayuSecureFormBlock() );
				$payment_method_registry->register( new PayuPaypoBlock() );
				$payment_method_registry->register( new PayuKlarnaBlock() );
				$payment_method_registry->register( new PayuTwistoPlBlock() );
				$payment_method_registry->register( new PayuInstallmentsBlock() );
				$payment_method_registry->register( new PayuBlikBlock() );
			}
		);
	}
}

/**
 * Init function that runs after plugin install.
 */
function init_gateway_payu() {
	handle_plugin_update();
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	load_plugin_textdomain( 'woo-payu-payment-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	add_filter( 'woocommerce_payment_gateways', 'add_payu_gateways' );
	add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', 'payu_filter_woocommerce_valid_order_statuses_for_payment_complete', 10, 2 );
	add_filter( 'woocommerce_email_actions', 'add_payu_order_status_to_email_notifications' );
	add_filter( 'woocommerce_email_classes', 'add_payu_order_status_to_email_notifications_trigger' );

	if ( ! is_admin() && isset( $_GET['pay_for_order'], $_GET['key'] ) ) {
		add_filter( 'woocommerce_valid_order_statuses_for_payment',
			'payu_filter_woocommerce_valid_order_statuses_for_payment',
			10, 2 );
	}
	add_filter( 'plugin_row_meta', 'plugin_row_meta', 10, 2 );
}

//enable pbl and standard if first install
register_activation_hook( __FILE__, 'payu_plugin_on_activate' );
function payu_plugin_on_activate() {
	if ( ! get_option( 'woocommerce_payu_settings' ) && ! get_option( '_payu_plugin_version' ) ) {
		add_option( '_payu_plugin_version', PAYU_PLUGIN_VERSION );
		add_option( 'woocommerce_payulistbanks_settings', [ 'enabled' => 'yes' ] );
		add_option( 'woocommerce_payucreditcard_settings', [ 'enabled' => 'yes' ] );
		add_option( 'payu_settings_option_name', [ 'global_default_on_hold_status' => 'on-hold' ] );
		add_option( 'woocommerce_payuinstallments_settings', [
			'enabled'                        => 'no',
			'credit_widget_on_listings'      => 'yes',
			'credit_widget_on_product_page'  => 'yes',
			'credit_widget_on_cart_page'     => 'yes',
			'credit_widget_on_checkout_page' => 'yes'
		] );
	}
}

function handle_plugin_update() {
	if ( PAYU_PLUGIN_VERSION !== get_option( '_payu_plugin_version' ) ) {
		update_option( '_payu_plugin_version', PAYU_PLUGIN_VERSION );
		$defaultInstallmentsSettings = [
			'enabled'                        => 'no',
			'credit_widget_on_listings'      => 'yes',
			'credit_widget_on_product_page'  => 'yes',
			'credit_widget_on_cart_page'     => 'yes',
			'credit_widget_on_checkout_page' => 'yes'
		];
		$payuInstallmentsSettings    = get_option( 'woocommerce_payuinstallments_settings' );
		if ( empty( $payuInstallmentsSettings ) ) {
			add_option( 'woocommerce_payuinstallments_settings', $defaultInstallmentsSettings );
		} else {
			$mergedInstallmentsSettings = array_merge( $defaultInstallmentsSettings, $payuInstallmentsSettings );
			update_option( 'woocommerce_payuinstallments_settings', $mergedInstallmentsSettings );
		}
	}
}

function move_old_payu_settings() {
	if ( $old_payu = get_option( 'woocommerce_payu_settings' ) ) {
		unset( $old_payu['payu_feedback'] );

		$global   = [];
		$standard = [];
		foreach ( $old_payu as $key => $value ) {
			if ( ! in_array( $key, [ 'enabled', 'title', 'sandbox' ] ) ) {
				$global[ 'global_' . $key ] = $value;
			}
		}
		$global['global_default_on_hold_status'] = 'on-hold';
		update_option( 'payu_settings_option_name', $global );
		foreach ( $old_payu as $key => $value ) {
			if ( ! in_array( $key, [ 'enabled', 'title', 'sandbox', 'description', 'enable_for_shipping' ] ) ) {
				$standard[ $key ] = '';
			} else {
				$standard[ $key ] = $value;
			}
		}
		$standard['use_global'] = 'yes';
		update_option( 'woocommerce_payustandard_settings', $standard );
		update_option( '_payu_plugin_version', PAYU_PLUGIN_VERSION );
		delete_option( 'woocommerce_payu_settings' );
	}
}

function payu_filter_woocommerce_valid_order_statuses_for_payment(): array {
	return [ 'pending', 'failed', 'on-hold', PAYU_PLUGIN_STATUS_WAITING ];
}

function payu_filter_woocommerce_valid_order_statuses_for_payment_complete( array $statuses ): array {
	$statuses[] = PAYU_PLUGIN_STATUS_WAITING;

	return $statuses;
}

function add_payu_gateways( array $gateways ): array {
	$gateways[] = WC_Gateway_PayuStandard::class;
	$gateways[] = WC_Gateway_PayuCreditCard::class;
	$gateways[] = WC_Gateway_PayuPaypo::class;
	$gateways[] = WC_Gateway_PayuKlarna::class;
	$gateways[] = WC_Gateway_PayuTwistoPl::class;
	$gateways[] = WC_Gateway_PayuInstallments::class;
	$gateways[] = WC_Gateway_PayuBlik::class;
	$gateways[] = WC_Gateway_PayuListBanks::class;
	$gateways[] = WC_Gateway_PayuSecureForm::class;

	return $gateways;
}

function add_payu_order_status_to_email_notifications( array $actions ): array {
	$actions[] = 'woocommerce_order_status_' . PAYU_PLUGIN_STATUS_WAITING . '_to_processing';
	$actions[] = 'woocommerce_order_status_pending_to_' . PAYU_PLUGIN_STATUS_WAITING;
	$actions[] = 'woocommerce_order_status_failed_to_' . PAYU_PLUGIN_STATUS_WAITING;
	$actions[] = 'woocommerce_order_status_cancelled_to_' . PAYU_PLUGIN_STATUS_WAITING;

	return $actions;
}

function add_payu_order_status_to_email_notifications_trigger( array $classes ): array {
	if ( isset( $classes['WC_Email_Customer_Processing_Order'] ) ) {
		add_action( 'woocommerce_order_status_' . PAYU_PLUGIN_STATUS_WAITING . '_to_processing_notification', [
			$classes['WC_Email_Customer_Processing_Order'],
			'trigger'
		], 10, 2 );
	}

	if ( isset( $classes['WC_Email_New_Order'] ) ) {
		add_action( 'woocommerce_order_status_pending_to_' . PAYU_PLUGIN_STATUS_WAITING . '_notification', [
			$classes['WC_Email_New_Order'],
			'trigger'
		], 10, 2 );
		add_action( 'woocommerce_order_status_failed_to_' . PAYU_PLUGIN_STATUS_WAITING . '_notification', [
			$classes['WC_Email_New_Order'],
			'trigger'
		], 10, 2 );
		add_action( 'woocommerce_order_status_cancelled_to_' . PAYU_PLUGIN_STATUS_WAITING . '_notification', [
			$classes['WC_Email_New_Order'],
			'trigger'
		], 10, 2 );
	}

	return $classes;
}

function plugin_row_meta( $links, $plugin_file ) {
	if ( strpos( $plugin_file, 'woo-payu-payment-gateway' ) === false ) {
		return $links;
	}
	$row_meta = array(
		'docs' => '<a href="' . esc_url( 'https://github.com/PayU-EMEA/woo-payu-payment-gateway' ) . '">' . esc_html__( 'Docs', 'woo-payu-payment-gateway' ) . '</a>'
	);

	return array_merge( $links, $row_meta );
}

//enqueue assets
function enqueue_payu_admin_assets() {
	wp_enqueue_script( 'payu-admin', plugins_url( '/assets/js/payu-admin.js', PAYU_PLUGIN_FILE ), [ 'jquery' ], PAYU_PLUGIN_VERSION );
	wp_enqueue_style( 'payu-admin', plugins_url( '/assets/css/payu-admin.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION );
}

if ( is_admin() ) {
	add_action( 'admin_enqueue_scripts', 'enqueue_payu_admin_assets' );
}

function get_installment_option( $option ) {

	$paymentGateways = WC()->payment_gateways()->payment_gateways();
	$result          = null;

	if ( array_key_exists( 'payuinstallments', $paymentGateways ) ) {

		/**
		 * @var  $installmentsGateway WC_Gateway_PayuInstallments
		 */
		$installmentsGateway = $paymentGateways['payuinstallments'];

		switch ( $option ) {
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

function is_installments_widget_available_for_feature( $featureName ) {
	return ! empty( get_option( 'woocommerce_payuinstallments_settings' ) ) &&
	       get_option( 'woocommerce_payuinstallments_settings' )['enabled'] === 'yes' &&
	       get_option( 'woocommerce_payuinstallments_settings' )[ $featureName ] === 'yes';
}

if ( is_installments_widget_available_for_feature( 'credit_widget_on_listings' ) ) {
	add_action( 'woocommerce_after_shop_loop_item', 'installments_mini' );
}

if ( is_installments_widget_available_for_feature( 'credit_widget_on_product_page' ) ) {
	add_action( 'woocommerce_before_add_to_cart_form', 'installments_mini' );
}

function installments_mini() {
	if ( get_woocommerce_currency() !== 'PLN' ) {
		return;
	}
	$product = wc_get_product();
	if ( ! $product ) {
		return;
	}

	$price     = wc_get_price_including_tax( $product );
	$productId = $product->get_id();

	$posId     = get_installment_option( 'pos_id' );
	$widgetKey = get_installment_option( 'widget_key' );

	wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );

	?>
    <div>
        <span id="installment-mini-<?php echo esc_html( $productId ) ?>"></span>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var value = <?php echo esc_html( $price )?>;
                var options = {
                    creditAmount: value,
                    posId: '<?php echo esc_html( $posId )?>',
                    key: '<?php echo esc_html( $widgetKey )?>',
                    showLongDescription: true
                };
                OpenPayU.Installments.miniInstallment('#installment-mini-<?php echo esc_html( $productId )?>', options);
            });
        </script>
    </div>
	<?php
}


if ( is_installments_widget_available_for_feature( 'credit_widget_on_cart_page' ) ) {
	add_action( 'woocommerce_cart_totals_after_order_total', 'installments_mini_cart' );
}

function is_shipping_method_in_supported_methods_set( $chosenShippingMethod, $availableShippingMethods ) {
	if ( empty( $availableShippingMethods ) ) {
		return true;
	}
	if ( ! empty( $chosenShippingMethod ) ) {
		foreach ( $availableShippingMethods as $supportedShippingMethod ) {
			if ( strpos( $chosenShippingMethod, $supportedShippingMethod ) === 0 ) {
				return true;
			}
		}
	}

	return false;
}

add_action( 'wc_ajax_payu_installments_get_cart_total', 'installments_get_cart_total' );
function installments_get_cart_total() {
	$price = WC()->cart->get_total( '' );
	echo $price;
	wp_die();
}

function installments_mini_cart() {
	if ( get_woocommerce_currency() !== 'PLN' ) {
		return;
	}
	$chosen_shipping_methods             = WC()->session->get( 'chosen_shipping_methods' );
	$chosenShippingMethod                = is_array( $chosen_shipping_methods ) && count( $chosen_shipping_methods ) > 0
		? $chosen_shipping_methods[0] : null;
	$supportedInstallmentShippingMethods = get_installment_option( 'enable_for_shipping' );
	if ( ! is_shipping_method_in_supported_methods_set( $chosenShippingMethod, $supportedInstallmentShippingMethods ) ) {
		return;
	}

	$price = WC()->cart->get_total( '' );

	$posId     = get_installment_option( 'pos_id' );
	$widgetKey = get_installment_option( 'widget_key' );

	wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );

	?>
    <tr>
        <td></td>
        <td>
            <span id="installment-mini-cart"></span>
            <script type="text/javascript">
                var priceTotal = <?php echo esc_html( $price )?>;

                function showInstallmentsWidget() {
                    if (window.OpenPayU) {
                        var options = {
                            creditAmount: priceTotal,
                            posId: '<?php echo esc_html( $posId )?>',
                            key: '<?php echo esc_html( $widgetKey )?>',
                            showLongDescription: true
                        };
                        OpenPayU.Installments.miniInstallment('#installment-mini-cart', options);
                    }
                }

                document.addEventListener("DOMContentLoaded", showInstallmentsWidget);
                jQuery(document.body).on('updated_cart_totals', function () {
                    var data = {
                        'action': 'installments_get_cart_total'
                    };
                    jQuery.post(woocommerce_params.wc_ajax_url.toString().replace('%%endpoint%%', 'payu_installments_get_cart_total'), data, function (response) {
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

if ( is_installments_widget_available_for_feature( 'credit_widget_on_listings' ) ) {
	add_filter( 'woocommerce_blocks_product_grid_item_html', 'installments_mini_aware_product_block', 10, 3 );
}

function installments_mini_aware_product_block( $html, $data, $product ) {
	if ( has_block( 'woocommerce/cart' ) || get_woocommerce_currency() !== 'PLN' ) {
		return $html;
	}
	$price     = wc_get_price_including_tax( $product );
	$productId = $product->get_id();

	$posId     = get_installment_option( 'pos_id' );
	$widgetKey = get_installment_option( 'widget_key' );

	wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );

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

add_action( 'woocommerce_view_order', 'view_order' );
function view_order( $order_id ) {
	wp_enqueue_style( 'payu-gateway', plugins_url( '/assets/css/payu-gateway.css', PAYU_PLUGIN_FILE ),
		[], PAYU_PLUGIN_VERSION );

	$order         = wc_get_order( $order_id );
	$payu_gateways = WC_Payu_Gateways::gateways_list();
	if ( in_array( $order->get_status(), [ 'on-hold', 'pending', 'failed' ] ) ) {
		if ( isset($payu_gateways[ $order->get_payment_method() ]) && isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
			$pay_now_url = add_query_arg( [
				'pay_for_order' => 'true',
				'key'           => $order->get_order_key()
			], wc_get_endpoint_url( 'order-pay', $order->get_id(), wc_get_checkout_url() ) );

			?>
            <a href="<?php echo esc_url( $pay_now_url ) ?>"
               class="autonomy-payu-button"><?php esc_html_e( 'Pay with', 'woo-payu-payment-gateway' ); ?>
                <img src="<?php echo esc_url( plugins_url( '/assets/images/logo-payu.svg', PAYU_PLUGIN_FILE ) ) ?>"/>
            </a>
			<?php
		}
	}
}

// Register new status
function register_waiting_payu_order_status() {
	register_post_status( 'wc-' . PAYU_PLUGIN_STATUS_WAITING,
		[
			'label'                     => __( 'Awaiting receipt of payment', 'woo-payu-payment-gateway' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
		]
	);
}

function woocommerce_payu_is_wmpl_active_and_configure(): bool {
	global $woocommerce_wpml;

	return $woocommerce_wpml
	       && property_exists( $woocommerce_wpml, 'multi_currency' )
	       && $woocommerce_wpml->multi_currency
	       && count( $woocommerce_wpml->multi_currency->get_currency_codes() ) > 1;
}

function woocommerce_payu_is_currency_custom_config(): bool {
	return apply_filters( 'woocommerce_payu_multicurrency_active', false )
	       && count( apply_filters( 'woocommerce_payu_get_currency_codes', [] ) ) > 1;
}

function woocommerce_payu_get_currencies(): array {
	global $woocommerce_wpml;

	$currencies = [];

	if ( woocommerce_payu_is_wmpl_active_and_configure() ) {
		$currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
	} elseif ( woocommerce_payu_is_currency_custom_config() ) {
		$currencies = apply_filters( 'woocommerce_payu_get_currency_codes', [] );
	}

	return $currencies;
}

add_action( 'init', 'register_waiting_payu_order_status' );

// Add to list of WC Order statuses
function add_waiting_payu_to_order_statuses( $order_statuses ) {
	$order_statuses[ 'wc-' . PAYU_PLUGIN_STATUS_WAITING ] = __( 'Awaiting receipt of payment', 'woo-payu-payment-gateway' );

	return $order_statuses;
}

add_filter( 'wc_order_statuses', 'add_waiting_payu_to_order_statuses' );

//remove pay button
function filter_woocommerce_my_account_my_orders_actions( $actions, $order ) {
	// Get status
	$order_status = $order->get_status();

	if ( in_array( $order_status,
		[
			'failed',
			PAYU_PLUGIN_STATUS_WAITING,
			get_option( 'payu_settings_option_name' )['global_default_on_hold_status']
		] ) ) {
		$payu_gateways = WC_Payu_Gateways::gateways_list();
		if ( isset($payu_gateways[ $order->get_payment_method() ]) && isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
			$actions['repayu'] = [
				'name' => __( 'Pay with PayU', 'woo-payu-payment-gateway' ),
				'url'  => wc_get_endpoint_url( 'order-pay', $order->get_id(),
						wc_get_checkout_url() ) . '?pay_for_order=true&key=' . $order->get_order_key()
			];
			unset( $actions['pay'] );
		}

	}

	return $actions;
}

add_filter( 'woocommerce_my_account_my_orders_actions', 'filter_woocommerce_my_account_my_orders_actions', 10, 2 );
add_action( 'woocommerce_order_item_add_action_buttons', 'wc_order_item_add_action_buttons_callback', 10, 1 );

function wc_order_item_add_action_buttons_callback( $order ) {
	$payu_gateways   = WC_Payu_Gateways::gateways_list();
	$payuOrderStatus = $order->get_meta( '_payu_order_status', false, '' );

	if ( isset($payu_gateways[ $order->get_payment_method() ]) && ! isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) && $payuOrderStatus ) {
		$payu_statuses = WC_Payu_Gateways::clean_payu_statuses( $payuOrderStatus );
		if ( ( ! in_array( OpenPayuOrderStatus::STATUS_COMPLETED,
					$payu_statuses ) && ! in_array( OpenPayuOrderStatus::STATUS_CANCELED,
					$payu_statuses ) ) && in_array( OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION,
				$payu_statuses ) ) {
			$url_receive = add_query_arg( [
				'post'            => $order->get_id(),
				'action'          => 'edit',
				'receive-payment' => 1
			], admin_url( 'post.php' ) );
			$url_discard = add_query_arg( [
				'post'            => $order->get_id(),
				'action'          => 'edit',
				'discard-payment' => 1
			], admin_url( 'post.php' ) );
			?>
            <a href="<?php echo esc_url( $url_receive ) ?>" type="button"
               class="button receive-payment"><?php esc_html_e( 'Receive payment', 'woo-payu-payment-gateway' ) ?></a>
            <a href="<?php echo esc_url( $url_discard ) ?>" type="button"
               class="button discard-payment"><?php esc_html_e( 'Discard payment', 'woo-payu-payment-gateway' ) ?></a>
			<?php
			$url_return = add_query_arg( [
				'post'   => $order->get_id(),
				'action' => 'edit',
			], admin_url( 'post.php' ) );
			if ( is_admin() && ( isset( $_GET['receive-payment'] ) || isset( $_GET['discard-payment'] ) ) ) {
				global $current_user;
				wp_get_current_user();

				if ( isset( $_GET['receive-payment'] ) && ! isset( $_GET['discard-payment'] ) ) {
					$orderId             = $order->get_transaction_id();
					$status_update       = [
						"orderId"     => $orderId,
						"orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
					];
					$payment_method_name = $order->get_payment_method();
					$payment_init        = WC_Payu_Gateways::gateways_list()[ $payment_method_name ]['api'];
					$payment             = new $payment_init;
					$payment->init_OpenPayU( $order->get_currency() );
					OpenPayU_Order::statusUpdate( $status_update );
					$order->add_order_note( sprintf( __( '[PayU] User %s accepted payment', 'woo-payu-payment-gateway' ),
						$current_user->user_login ) );
					wp_redirect( $url_return );
				}
				if ( ! isset( $_GET['receive-payment'] ) && isset( $_GET['discard-payment'] ) ) {
					$payment_method_name = $order->get_payment_method();
					$payment_init        = WC_Payu_Gateways::gateways_list()[ $payment_method_name ]['api'];
					$payment             = new $payment_init;
					$payment->init_OpenPayU( $order->get_currency() );
					$orderId = $order->get_transaction_id();
					OpenPayU_Order::cancel( $orderId );
					$order->add_order_note( sprintf( __( '[PayU] User %s rejected payment', 'woo-payu-payment-gateway' ),
						$current_user->user_login ) );
					wp_redirect( $url_return );
				}
			}
		}
	}
}
