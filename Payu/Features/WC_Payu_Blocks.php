<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Features;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Payu\PaymentGateway\Blocks\CreditWidget\CartCreditWidgetBlock;
use Payu\PaymentGateway\Blocks\CreditWidget\CheckoutCreditWidgetBlock;
use Payu\PaymentGateway\Blocks\PayuApplePayBlock;
use Payu\PaymentGateway\Blocks\PayuBlikBlock;
use Payu\PaymentGateway\Blocks\PayuCreditCardBlock;
use Payu\PaymentGateway\Blocks\PayuGooglePayBlock;
use Payu\PaymentGateway\Blocks\PayuInstallmentsBlock;
use Payu\PaymentGateway\Blocks\PayuKlarnaBlock;
use Payu\PaymentGateway\Blocks\PayuListBanksBlock;
use Payu\PaymentGateway\Blocks\PayuPaypoBlock;
use Payu\PaymentGateway\Blocks\PayuPragmaBlock;
use Payu\PaymentGateway\Blocks\PayuSecureFormBlock;
use Payu\PaymentGateway\Blocks\PayuStandardBlock;
use Payu\PaymentGateway\Blocks\PayuTwistoPlBlock;
use Payu\PaymentGateway\Blocks\PayuTwistoSliceBlock;

class WC_Payu_Blocks {
	public static function init(): void {
		new self();
	}

	public function __construct() {
		add_action( 'woocommerce_blocks_loaded', [ $this, 'on_woocommerce_blocks_loaded' ] );
	}

	public function on_woocommerce_blocks_loaded(): void {
		$this->init_payu_blocks();
		$this->init_credit_widget_blocks();
	}

	private function init_payu_blocks(): void {
		if ( class_exists( \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new PayuStandardBlock() );
					$payment_method_registry->register( new PayuListBanksBlock() );
					$payment_method_registry->register( new PayuCreditCardBlock() );
					$payment_method_registry->register( new PayuSecureFormBlock() );
					$payment_method_registry->register( new PayuGooglePayBlock() );
					$payment_method_registry->register( new PayuApplePayBlock() );
					$payment_method_registry->register( new PayuPaypoBlock() );
					$payment_method_registry->register( new PayuKlarnaBlock() );
					$payment_method_registry->register( new PayuTwistoPlBlock() );
					$payment_method_registry->register( new PayuTwistoSliceBlock() );
					$payment_method_registry->register( new PayuInstallmentsBlock() );
					$payment_method_registry->register( new PayuBlikBlock() );
					$payment_method_registry->register( new PayuPragmaBlock() );
				}
			);
		}
	}

	private function init_credit_widget_blocks(): void {
		if ( interface_exists( \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface::class ) ) {
			add_action( 'woocommerce_blocks_cart_block_registration',
				function ( IntegrationRegistry $integration_registry ) {
					$integration_registry->register( new CartCreditWidgetBlock() );
				}
			);
			add_action( 'woocommerce_blocks_checkout_block_registration',
				function ( IntegrationRegistry $integration_registry ) {
					$integration_registry->register( new CheckoutCreditWidgetBlock() );
				}
			);
		}
	}
}
