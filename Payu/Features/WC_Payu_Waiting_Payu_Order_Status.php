<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Features;

class WC_Payu_Waiting_Payu_Order_Status {

	public const PAYU_PLUGIN_STATUS_WAITING = 'payu-waiting';

	public static function init(): void {
		new self();
	}

	public function __construct() {
		add_action( 'init', [ $this, 'register_order_status' ] );
		add_filter( 'wc_order_statuses', [ $this, 'add_to_order_statuses' ] );
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', [
			$this,
			'add_valid_order_statuses_for_payment_complete'
		], 10, 2 );
		add_filter( 'woocommerce_email_actions', [ $this, 'add_status_to_email_notifications' ] );
		add_filter( 'woocommerce_email_classes', [ $this, 'add_status_to_email_notifications_trigger' ] );
		if ( isset( $_GET['pay_for_order'], $_GET['key'] ) && ! is_admin() ) {
			add_filter( 'woocommerce_valid_order_statuses_for_payment',
				[ $this, 'valid_order_statuses_for_payment' ], 10, 2 );
		}
	}

	public function register_order_status(): void {
		register_post_status( 'wc-' . self::PAYU_PLUGIN_STATUS_WAITING,
			[
				'label'                     => __( 'Awaiting receipt of payment', 'woo-payu-payment-gateway' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
			]
		);
	}

	public function add_to_order_statuses( array $order_statuses ): array {
		$order_statuses[ 'wc-' . self::PAYU_PLUGIN_STATUS_WAITING ] = __( 'Awaiting receipt of payment', 'woo-payu-payment-gateway' );

		return $order_statuses;
	}

	public function add_valid_order_statuses_for_payment_complete( array $statuses ): array {
		$statuses[] = self::PAYU_PLUGIN_STATUS_WAITING;

		return $statuses;
	}

	public function add_status_to_email_notifications( array $actions ): array {
		$actions[] = 'woocommerce_order_status_' . self::PAYU_PLUGIN_STATUS_WAITING . '_to_processing';
		$actions[] = 'woocommerce_order_status_pending_to_' . self::PAYU_PLUGIN_STATUS_WAITING;
		$actions[] = 'woocommerce_order_status_failed_to_' . self::PAYU_PLUGIN_STATUS_WAITING;
		$actions[] = 'woocommerce_order_status_cancelled_to_' . self::PAYU_PLUGIN_STATUS_WAITING;

		return $actions;
	}

	public function add_status_to_email_notifications_trigger( array $classes ): array {
		if ( isset( $classes['WC_Email_Customer_Processing_Order'] ) ) {
			add_action( 'woocommerce_order_status_' . self::PAYU_PLUGIN_STATUS_WAITING . '_to_processing_notification', [
				$classes['WC_Email_Customer_Processing_Order'],
				'trigger'
			], 10, 2 );
		}

		if ( isset( $classes['WC_Email_New_Order'] ) ) {
			add_action( 'woocommerce_order_status_pending_to_' . self::PAYU_PLUGIN_STATUS_WAITING . '_notification', [
				$classes['WC_Email_New_Order'],
				'trigger'
			], 10, 2 );
			add_action( 'woocommerce_order_status_failed_to_' . self::PAYU_PLUGIN_STATUS_WAITING . '_notification', [
				$classes['WC_Email_New_Order'],
				'trigger'
			], 10, 2 );
			add_action( 'woocommerce_order_status_cancelled_to_' . self::PAYU_PLUGIN_STATUS_WAITING . '_notification', [
				$classes['WC_Email_New_Order'],
				'trigger'
			], 10, 2 );
		}

		return $classes;
	}

	public function valid_order_statuses_for_payment(): array {
		return [ 'pending', 'failed', 'on-hold', self::PAYU_PLUGIN_STATUS_WAITING ];
	}
}
