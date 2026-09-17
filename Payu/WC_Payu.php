<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway;

use OpenPayuOrderStatus;
use Payu\PaymentGateway\Features\WC_Payu_Blocks;
use Payu\PaymentGateway\Features\WC_Payu_Receive_Discard_Payment;
use Payu\PaymentGateway\Features\WC_Payu_Repay_In_Order_Actions;
use Payu\PaymentGateway\Features\WC_Payu_Status_Retrieval_On_Thank_You;
use Payu\PaymentGateway\Features\WC_Payu_Waiting_Payu_Order_Status;

class WC_Payu {
	private const TEMPLATE_PATH = WC_PAYU_PLUGIN_PATH . 'templates/';
	private const PAYU_STATUSES_META_KEY = '_payu_order_status';

	public static function init(): void {
		WC_Payu_Blocks::init();
		WC_Payu_Waiting_Payu_Order_Status::init();
		WC_Payu_Receive_Discard_Payment::init();
		WC_Payu_Repay_In_Order_Actions::init();
		WC_Payu_Status_Retrieval_On_Thank_You::init();
	}

	public static function payu_status_available_in_wc_order( string $status, \WC_Order $order ): bool {
		$payu_statuses = $order->get_meta( self::PAYU_STATUSES_META_KEY, false, '' );
		$statuses      = [];

		foreach ( $payu_statuses as $payu_status ) {
			$statuses[] = explode( '|', $payu_status->value )[0];
		}

		return in_array( $status, $statuses, true );
	}

	public static function payu_status_add_to_wc_order( string $status, \WC_Order $order ): void {
		$payu_statuses = $order->get_meta( self::PAYU_STATUSES_META_KEY, false, '' );

		foreach ( $payu_statuses as $payu_status ) {
			if ( $status === $payu_status->value ) {
				return;
			}
		}

		$order->add_meta_data( self::PAYU_STATUSES_META_KEY, $status );
		$order->save_meta_data();
	}

	public static function payu_status_get_completed_from_wc_order( \WC_Order $order ): ?string {
		$payu_statuses = $order->get_meta( self::PAYU_STATUSES_META_KEY, false, '');

		foreach ( $payu_statuses as $payu_status ) {
			$ps = explode( '|', $payu_status->value );
			if ( $ps[0] === OpenPayuOrderStatus::STATUS_COMPLETED ) {
				return $ps[1];
			}
		}

		return null;
	}

	public static function template(string $name, array $params = []): void {
		extract( $params );
		include self::TEMPLATE_PATH . $name . '.php';
	}
}
