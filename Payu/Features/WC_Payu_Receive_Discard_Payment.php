<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Features;

use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayuOrderStatus;
use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;
use WC_Order;
use WC_Order_Refund;

class WC_Payu_Receive_Discard_Payment {
	public static function init(): void {
		new self();
	}

	public function __construct() {
		add_action( 'woocommerce_order_item_add_action_buttons', [ $this, 'add_action_buttons' ], 10, 1 );
	}

	/**
	 * @param bool|WC_Order|WC_Order_Refund $order
	 */
	public function add_action_buttons( $order ): void {
		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		$payu_gateways   = WC_Payu_Gateways::gateways_list();
		$payuOrderStatus = $order->get_meta( '_payu_order_status', false, '' );

		if ( isset( $payu_gateways[ $order->get_payment_method() ] ) && ! isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) && $payuOrderStatus ) {
			$payu_statuses = WC_Payu_Gateways::clean_payu_statuses( $payuOrderStatus );

			if ( ! in_array( OpenPayuOrderStatus::STATUS_COMPLETED, $payu_statuses, true )
			     && in_array( OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION, $payu_statuses, true )
			) {
				$this->render_buttons( $order );

				$url_return = add_query_arg( [
					'post'   => $order->get_id(),
					'action' => 'edit',
				], admin_url( 'post.php' ) );

				if ( ( isset( $_GET['receive-payment'] ) || isset( $_GET['discard-payment'] ) ) && is_admin() ) {
					$user_nickname = wp_get_current_user()->nickname;

					if ( isset( $_GET['receive-payment'] ) && ! isset( $_GET['discard-payment'] ) ) {
						$orderId             = $order->get_transaction_id();
						$status_update       = [
							'orderId'     => $orderId,
							'orderStatus' => OpenPayuOrderStatus::STATUS_COMPLETED
						];
						$payment_method_name = $order->get_payment_method();
						$payment_init        = WC_Payu_Gateways::gateways_list()[ $payment_method_name ]['class'];
						$payment             = new $payment_init;
						$payment->init_OpenPayU( $order->get_currency() );
						try {
							OpenPayU_Order::statusUpdate( $status_update );
							$order->add_order_note(
								sprintf( __( '[PayU] User %s accepted payment', 'woo-payu-payment-gateway' ), $user_nickname )
							);
						} catch ( OpenPayU_Exception $e ) {
							$order->add_order_note(
								sprintf( __( '[PayU] Error "%s" occurred during accepted payment', 'woo-payu-payment-gateway' ), $e->getMessage() )
							);
						}
						wp_redirect( $url_return );
					}

					if ( ! isset( $_GET['receive-payment'] ) && isset( $_GET['discard-payment'] ) ) {
						$payment_method_name = $order->get_payment_method();
						$payment_init        = WC_Payu_Gateways::gateways_list()[ $payment_method_name ]['class'];
						$payment             = new $payment_init;
						$payment->init_OpenPayU( $order->get_currency() );
						$orderId = $order->get_transaction_id();
						try {
							OpenPayU_Order::cancel( $orderId );
							$order->add_order_note(
								sprintf( __( '[PayU] User %s rejected payment', 'woo-payu-payment-gateway' ), $user_nickname )
							);
						} catch ( OpenPayU_Exception $e ) {
							$order->add_order_note(
								sprintf( __( '[PayU] Error "%s" occurred during rejected payment', 'woo-payu-payment-gateway' ), $e->getMessage() )
							);
						}
						wp_redirect( $url_return );
					}
				}
			}
		}
	}

	private function render_buttons( WC_Order $order ): void {
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

		printf( '<a href="%s" type="button" class="button receive-payment">%s</a><a href="%s" type="button" class="button discard-payment">%s</a>',
			esc_url( $url_receive ),
			esc_html__( 'Receive payment', 'woo-payu-payment-gateway' ),
			esc_url( $url_discard ),
			esc_html__( 'Discard payment', 'woo-payu-payment-gateway' )
		);
	}
}
