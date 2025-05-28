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

		if ( $this->widget_on_checkout_enabled() && $this->is_checkout_page() ) {
			wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );
		}

		return true;
	}

	// Additional data for Blocks
	public function get_additional_data(): array {
		return [
			'widgetOnCheckout' => $this->widget_on_checkout_enabled(),
			'posId'            => $this->pos_id,
			'widgetKey'        => $this->pos_widget_key,
			'excludedPaytypes' => $this->get_credit_widget_excluded_paytypes(),
			'lang'             => $this->getLanguage(),
            'currency'         => get_woocommerce_currency(),
			'total'            => $this->getTotal()
		];
	}

    private function widget_on_checkout_enabled() {
        $payuSettings = get_option('payu_settings_option_name');
        if ( ! empty($payuSettings) && isset($payuSettings['credit_widget_on_checkout_page']) ) {
            return $payuSettings['credit_widget_on_checkout_page'] === 'yes';
        } else {
            return false;
        }
    }

	private function is_checkout_page(): bool {
		return is_checkout() || has_block( 'woocommerce/checkout' );
	}

    private function get_credit_widget_excluded_paytypes(): array {
        $payuSettings = get_option('payu_settings_option_name');
        if ( ! empty($payuSettings) && isset($payuSettings['credit_widget_excluded_paytypes']) ) {
            return $payuSettings['credit_widget_excluded_paytypes'];
        } else {
            return [];
        }
    }
}
