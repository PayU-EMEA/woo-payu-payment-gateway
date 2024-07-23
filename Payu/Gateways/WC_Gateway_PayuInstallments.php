<?php

namespace Payu\PaymentGateway\Gateways;

class WC_Gateway_PayuInstallments extends WC_Payu_Gateways {
	protected string $paytype = 'ai';

	function __construct() {
		parent::__construct( 'payuinstallments' );
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() || ! parent::is_available() ) {
			return false;
		}

		if ( $this->get_option('credit_widget_on_checkout_page', 'no') === 'yes' &&
		     get_woocommerce_currency() === 'PLN' &&
		     $this->is_checkout_page()
		) {
			wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );
		}

		return true;
	}

	// Additional data for Blocks
	public function get_additional_data(): array {
		return [
			'widgetOnCheckout' => $this->get_option('credit_widget_on_checkout_page', 'no') === 'yes',
			'posId'            => $this->pos_id,
			'widgetKey'        => $this->pos_widget_key,
			'total'            => $this->getTotal()
		];
	}

	public function get_description(): string {
		wp_enqueue_style( 'payu-installments-widget', plugins_url( '/assets/css/payu-installments-widget.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION );

		wp_enqueue_script( 'payu-installments-widget-checkout', plugins_url( '/assets/js/payu-installments-widget-checkout.js', PAYU_PLUGIN_FILE ), [
			'jquery',
			'payu-installments-widget'
		], PAYU_PLUGIN_VERSION );
		$posId      = $this->pos_id;
		$widgetKey  = $this->pos_widget_key;
		$priceTotal = WC()->cart->get_total( '' );

		return
			$this->description .
			'<script type="text/javascript">' .
			'var PayUInstallmentsWidgetCartData = {' .
			'	priceTotal: ' . $priceTotal . ',' .
			'   posId: \'' . $posId . '\',' .
			'   widgetKey: \'' . $widgetKey . '\'' .
			'}' .
			'</script>';
	}

	protected function get_additional_gateway_fields(): array {
		return [
			'credit_widget_on_listings'      => [
				'title'   => __( 'Installments widget', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enabled on product listings', 'woo-payu-payment-gateway' ),
				'default' => 'yes'
			],
			'credit_widget_on_product_page'  => [
				'title'   => __( 'Installments widget', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enabled on product page', 'woo-payu-payment-gateway' ),
				'default' => 'yes'
			],
			'credit_widget_on_cart_page'     => [
				'title'   => __( 'Installments widget', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enabled on cart page', 'woo-payu-payment-gateway' ),
				'default' => 'yes'
			],
			'credit_widget_on_checkout_page' => [
				'title'   => __( 'Installments widget', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enabled on checkout page', 'woo-payu-payment-gateway' ),
				'default' => 'yes'
			],
		];
	}

	private function is_checkout_page(): bool {
		return is_checkout() || has_block( 'woocommerce/checkout' );
	}
}
