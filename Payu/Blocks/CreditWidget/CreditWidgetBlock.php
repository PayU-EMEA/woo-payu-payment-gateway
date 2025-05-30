<?php

namespace Payu\PaymentGateway\Blocks\CreditWidget;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

abstract class CreditWidgetBlock implements IntegrationInterface {

    private $handle;
    private $name = 'credit-widget-block';

    public function get_name(): string {
        return $this->name;
    }

    public function initialize(): void {
        $scriptPath = 'build/js/creditwidget.js';
        $asset_path   = WC_PAYU_PLUGIN_PATH . 'build/js/creditwidget.asset.php';
        $version = PAYU_PLUGIN_VERSION;
        $dependencies = [];

        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = $asset['version'] ?? $version;
            $dependencies = $asset['dependencies'] ?? $dependencies;
        }

        $this->handle = $this->page . '-' . $this->name;
        $scriptUrl = WC_PAYU_PLUGIN_URL . $scriptPath;

        wp_register_script(
            $this->handle,
            $scriptUrl,
            $dependencies,
            $version,
            true
        );
        wp_enqueue_style( 'payu-installments-widget', plugins_url( '/assets/css/payu-installments-widget-block.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION );
        wp_enqueue_script( 'payu-installments-widget', 'https://static.payu.com/res/v2/widget-mini-installments.js', [], PAYU_PLUGIN_VERSION );
    }

    public function get_script_handles(): array {
        return [$this->handle];
    }

    public function get_editor_script_handles(): array {
        return [$this->handle];
    }

    function get_script_data() {
        return [
            'widgetEnabledOnPage' => $this->widget_on_page_enabled(),
            'posId'            => get_installment_option( 'pos_id' ),
            'widgetKey'        => get_installment_option( 'widget_key' ),
            'excludedPaytypes' => get_credit_widget_excluded_paytypes(),
            'lang'             => getLanguage(),
            'currency'         => get_woocommerce_currency(),
            'total'            => WC()->cart->get_total( '' )
        ];

    }

    private function widget_on_page_enabled() {
        $payuSettings = get_option('payu_settings_option_name');
        $settingName = 'credit_widget_on_' . $this->page . '_page';
        if ( ! empty($payuSettings) && isset($payuSettings[$settingName]) ) {
            return $payuSettings[$settingName] === 'yes';
        } else {
            return false;
        }
    }

    private function getTotal(): float {
        if (WC()->cart && 0 !== count(WC()->cart->get_cart_contents())) {
            return
                WC()->cart->get_cart_contents_total() +
                WC()->cart->get_cart_contents_tax() +
                WC()->cart->get_shipping_total() +
                WC()->cart->get_shipping_tax();
        }
        return 0;
    }
}
