<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Features;

use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;
use WC_Order;
use WC_Order_Refund;

class WC_Payu_Repay_In_Order_Actions {
    public static function init(): void {
        new self();
    }

    public function __construct() {
        if ( isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
            add_filter( 'woocommerce_my_account_my_orders_actions', [ $this, 'add_action' ], 10, 2 );
            add_action( 'woocommerce_view_order', [ $this, 'view_order' ] );
        }
    }

    /**
     * @param bool|WC_Order|WC_Order_Refund $order
     */
    public function add_action( array $actions, $order ): array {
        if ( ! ( $order instanceof WC_Order ) ) {
            return $actions;
        }

        $order_status  = $order->get_status();
        $payu_gateways = WC_Payu_Gateways::gateways_list();

        if ( isset( $payu_gateways[ $order->get_payment_method() ] ) && in_array( $order_status, [
                        WC_Payu_Waiting_Payu_Order_Status::PAYU_PLUGIN_STATUS_WAITING,
                        get_option( 'payu_settings_option_name' )['global_default_on_hold_status']
                ], true ) ) {
            unset( $actions['pay'] );
            $actions = array_merge(
                    [
                            'pay' => [
                                    'name' => __( 'Pay with PayU', 'woo-payu-payment-gateway' ),
                                    'url'  => wc_get_endpoint_url( 'order-pay', $order->get_id(), wc_get_checkout_url() ) . '?pay_for_order=true&key=' . $order->get_order_key()
                            ]
                    ],
                    $actions
            );
        }

        return $actions;
    }

    public function view_order( int $order_id ): void {
        wp_enqueue_style( 'payu-gateway', plugins_url( '/assets/css/payu-gateway.css',
                PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION );

        $order         = wc_get_order( $order_id );
        $payu_gateways = WC_Payu_Gateways::gateways_list();
        if ( in_array( $order->get_status(), [
                        'on-hold',
                        'pending',
                        'failed'
                ] ) &&
             isset( $payu_gateways[ $order->get_payment_method() ], get_option( 'payu_settings_option_name' )['global_repayment'] )
        ) {
            $pay_now_url = add_query_arg( [
                    'pay_for_order' => 'true',
                    'key'           => $order->get_order_key()
            ], wc_get_endpoint_url( 'order-pay', $order->get_id(), wc_get_checkout_url() ) );

            ?>
            <a href="<?php echo esc_url( $pay_now_url ) ?>"
               class="autonomy-payu-button"><?php esc_html_e( 'Pay with', 'woo-payu-payment-gateway' ); ?>
                <img alt="PayU"
                     src="<?php echo esc_url( plugins_url( '/assets/images/logo-payu.svg', PAYU_PLUGIN_FILE ) ) ?>"/>
            </a>
            <?php
        }
    }
}
