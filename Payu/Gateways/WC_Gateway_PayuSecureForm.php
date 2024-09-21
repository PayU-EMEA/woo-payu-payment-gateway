<?php

namespace Payu\PaymentGateway\Gateways;

use OpenPayU_Result;

class WC_Gateway_PayuSecureForm extends WC_Payu_Gateways {
	protected string $paytype = 'c';
	private string $payu_sdk_url;

	function __construct() {
		parent::__construct( 'payusecureform' );

		$this->payu_sdk_url = $this->sandbox ? 'https://secure.snd.payu.com/javascript/sdk' : 'https://secure.payu.com/javascript/sdk';

		if ( $this->is_enabled() ) {
			$this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/card-visa-mc.svg', PAYU_PLUGIN_FILE ) );

			add_action( 'wp_enqueue_scripts', [ $this, 'include_payu_sf_scripts' ] );

			//refresh card iframe after checkout change
			if ( ! is_admin() ) {
				add_action( 'wp_footer', [ $this, 'minicart_checkout_refresh_script' ] );
			}
		}
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}

	// Additional data for Blocks
	public function get_additional_data(): array {
		return [
			'posId'  => $this->pos_id,
			'sdkUrl' => $this->payu_sdk_url,
			'lang'   => explode( '_', get_locale() )[0]
		];
	}

	function minicart_checkout_refresh_script(): void {
		if ( is_checkout() || is_wc_endpoint_url() ) :
			?>
            <script type="text/javascript">
                (function ($) {
                    $(document.body).on('change', '#shipping_method input', function () {
                        $(document.body).trigger('update_checkout').trigger('wc_fragment_refresh');
                        sf_init();
                    });
                    $(document).ready(function () {
                        if ($('form#order_review').length > 0) {
                            sf_init();
                        }
                    });
                })(jQuery);

            </script>
		<?php
		endif;
	}

	public function payment_fields(): void {
		parent::payment_fields();

		$response = $this->payu_get_paymethods();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$this->retrieve_methods( $response );
			$this->agreements_field();
			echo '<script>try{sf_init();}catch(e){}</script>';
		}
	}

	private function retrieve_methods( OpenPayU_Result $response ): void {
		$payMethods = $response->getResponse();
		if ( $payMethods->payByLinks ) {
			$payByLinks = $this->process_pay_methods( $payMethods->payByLinks );
			if ( $payByLinks ) {
				?>
                <div class="card-container" id="payu-card-container"
                     data-payu-posid="<?php echo esc_attr( $this->pos_id ) ?>"
                     data-lang="<?php echo esc_attr( explode( '_', get_locale() )[0] ) ?>">
                    <div class="payu-sf-technical-error"
                         data-type="technical"><?php esc_html_e( 'The card could not be sent', 'woo-payu-payment-gateway' ) ?></div>
                    <label for="payu-card-number"><?php esc_html_e( 'Card number', 'woo-payu-payment-gateway' ) ?></label>
                    <div class="payu-card-form" id="payu-card-number"></div>
                    <div class="payu-sf-validation-error" data-type="number"></div>

                    <div class="card-details clearfix">
                        <div class="expiration">
                            <label for="payu-card-date"><?php esc_html_e( 'Expire date', 'woo-payu-payment-gateway' ) ?></label>
                            <div class="payu-card-form" id="payu-card-date"></div>
                            <div class="payu-sf-validation-error" data-type="date"></div>
                        </div>

                        <div class="cvv">
                            <label for="payu-card-cvv"><?php esc_html_e( 'CVV', 'woo-payu-payment-gateway' ) ?></label>
                            <div class="payu-card-form" id="payu-card-cvv"></div>
                            <div class="payu-sf-validation-error" data-type="cvv"></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="payu_sf_token" value=""/>
                <input type="hidden" name="payu_browser[screenWidth]" value=""/>
                <input type="hidden" name="payu_browser[javaEnabled]" value=""/>
                <input type="hidden" name="payu_browser[timezoneOffset]" value=""/>
                <input type="hidden" name="payu_browser[screenHeight]" value=""/>
                <input type="hidden" name="payu_browser[userAgent]" value=""/>
                <input type="hidden" name="payu_browser[colorDepth]" value=""/>
                <input type="hidden" name="payu_browser[language]" value=""/>
				<?php
			}
		}
	}

	protected function get_payu_pay_method(): array {
		$token = sanitize_text_field( $_POST['payu_sf_token'] );

		return $this->get_payu_pay_method_array( 'CARD_TOKEN', $token );
	}

	public function include_payu_sf_scripts(): void {
		$payu_sdk_url = $this->sandbox ? 'https://secure.snd.payu.com/javascript/sdk' : 'https://secure.payu.com/javascript/sdk';
		wp_enqueue_script( 'payu-sfsdk', $payu_sdk_url, [], null );
		wp_enqueue_script( 'payu-promise-polyfill', plugins_url( '/assets/js/es6-promise.auto.min.js', PAYU_PLUGIN_FILE ), [], null );
		wp_enqueue_script( 'payu-sf-init', plugins_url( '/assets/js/sf-init.js', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION,
			true );
	}
}
