<?php

namespace Payu\PaymentGateway\Blocks\CreditWidget;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

abstract class CreditWidgetBlock implements IntegrationInterface {

	private string $handle;
	private string $name = 'credit-widget-block';

	public function get_name(): string {
		return $this->name;
	}

	public function initialize(): void {
		$scriptPath   = 'build/js/creditwidget.js';
		$asset_path   = WC_PAYU_PLUGIN_PATH . 'build/js/creditwidget.asset.php';
		$version      = PAYU_PLUGIN_VERSION;
		$dependencies = [];

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = $asset['version'] ?? $version;
			$dependencies = $asset['dependencies'] ?? $dependencies;
		}

		$this->handle = $this->page . '-' . $this->name;
		$script_url   = WC_PAYU_PLUGIN_URL . $scriptPath;

		wp_register_script(
			$this->handle,
			$script_url,
			$dependencies,
			$version,
			true
		);
	}

	public function get_script_handles(): array {
		return [ $this->handle ];
	}

	public function get_editor_script_handles(): array {
		return [ $this->handle ];
	}

	function get_script_data(): array {
		return [
			'widgetEnabledOnPage' => $this->widget_on_page_enabled(),
			'posId'               => get_installment_option( 'pos_id' ),
			'widgetKey'           => get_installment_option( 'widget_key' ),
			'excludedPaytypes'    => get_credit_widget_excluded_paytypes(),
			'lang'                => get_site_language(),
			'currency'            => get_woocommerce_currency()
		];

	}

	private function widget_on_page_enabled(): bool {
        if ( ! $this->is_widget_enabled_in_settings() || ! is_any_credit_paymethod_available() ) {
            return false;
        }
        wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js' );

        return true;
	}

    private function is_widget_enabled_in_settings(): bool {
        $payu_settings = get_option( 'payu_settings_option_name', [] );
        $setting_name  = 'credit_widget_on_' . $this->page . '_page';

        return isset( $payu_settings[ $setting_name ] ) && $payu_settings[ $setting_name ] === 'yes';
    }
}
