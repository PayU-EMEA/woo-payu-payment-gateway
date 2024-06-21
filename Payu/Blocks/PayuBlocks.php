<?php

namespace Payu\PaymentGateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Payu\PaymentGateway\Gateways\WC_PayuGateway;

abstract class PayuBlocks extends AbstractPaymentMethodType {

	protected WC_PayuGateway $payment_method;

	public function initialize(): void {
		$payment_gateways = WC()->payment_gateways();

		if ( in_array( $this->name, $payment_gateways->get_payment_gateway_ids() ) ) {
			$method = $payment_gateways->payment_gateways()[ $this->name ];
			if ( $method instanceof WC_PayuGateway ) {
				$this->payment_method = $payment_gateways->payment_gateways()[ $this->name ];
			} else {
				die( 'Plugin implementing error. Please contact with PayU' );
			}
		}
	}

	public function is_active(): bool {
		return $this->payment_method->is_enabled();
	}

	public function get_payment_method_script_handles(): array {
		$asset_path   = WC_PAYU_PLUGIN_PATH . 'build/js/' . $this->name . '.asset.php';
		$version      = PAYU_PLUGIN_VERSION;
		$dependencies = [];

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = $asset['version'] ?? $version;
			$dependencies = $asset['dependencies'] ?? $dependencies;
		}

		$handle = $this->name . '-block';

		wp_register_script(
			$handle,
			WC_PAYU_PLUGIN_URL . 'build/js/' . $this->name . '.js',
			$dependencies,
			$version,
			true
		);

		wp_set_script_translations( $handle, 'woo-payu-payment-gateway' );

		return [ $handle ];
	}

	public function get_payment_method_data(): array {
		return [
			'available'      => $this->is_active(),
			'showTermsInfo'  => $this->payment_method->is_payu_show_terms_info(),
			'title'          => $this->payment_method->get_payu_method_title(),
			'description'    => $this->payment_method->get_payu_method_description(),
			'icon'           => $this->payment_method->get_payu_method_icon(),
			'additionalData' => $this->payment_method->get_additional_data()
		];
	}
}
