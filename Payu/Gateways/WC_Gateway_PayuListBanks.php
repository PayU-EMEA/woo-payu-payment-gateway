<?php

namespace Payu\PaymentGateway\Gateways;

use OpenPayU_Result;

class WC_Gateway_PayuListBanks extends WC_Payu_Gateways {
	private array $unset_banks = [];

	function __construct() {
		parent::__construct( 'payulistbanks' );
	}

	public function is_available(): bool {
		if ( ! $this->try_retrieve_banks() ) {
			return false;
		}

		return parent::is_available();
	}

	protected function try_retrieve_banks(): bool {
		$response = $this->payu_get_paymethods();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$payMethods = $response->getResponse();

			return ! empty( $payMethods->payByLinks );
		}

		return false;
	}

	public function payment_fields(): void {
		parent::payment_fields();

		$response = $this->payu_get_paymethods();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$this->retrieve_methods( $response );
			$this->agreements_field();
		}
	}

	public function get_additional_data(): array {
		$paymethods = [];

		$response = $this->payu_get_paymethods();

		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' && $response->getResponse()->payByLinks ) {
			$paymethods = $this->get_pay_methods( $response->getResponse()->payByLinks );
		}

		return [
			'paymethods' => $paymethods
		];
	}

	private function retrieve_methods( OpenPayU_Result $response ): void {
		$payMethods = $response->getResponse();
		?>
        <script>
            jQuery(document).ready(function () {
                if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments() && jQuery(".payu-list-banks").is(":visible")) {
                    jQuery(".payu-bank-jp").remove();
                }
            })
        </script>
        <div class="pbl-container">
            <ul class="payu-list-banks">
				<?php if ( $payMethods->payByLinks ):
					$payByLinks = $this->get_pay_methods( $payMethods->payByLinks );
					if ( $payByLinks ):
						foreach ( $payByLinks as $key => $value ):
							?>
                            <li class="payu-bank payu-bank-<?php echo esc_attr( $key . ' ' . $value['active'] ) ?>"
                                title="<?php echo esc_attr( $value['name'] ) ?>">
                                <label>
                                    <input type="radio"
                                           value="<?php if ( $value['active'] === 'payu-active' )
										       echo esc_attr( $key ) ?>"
                                           name="selected-bank"/>
                                    <div><img src="<?php echo esc_url( $value['brandImageUrl'] ); ?>"></div>
                                </label>
                            </li>
						<?php
						endforeach;
					endif;
				endif;
				?>
            </ul>
            <ul class="pbl-error woocommerce-error" role="alert">
                <li><?php esc_html_e( 'Choose payment method.', 'woo-payu-payment-gateway' ) ?></li>
            </ul>
        </div>

		<?php
	}

	function get_pay_methods( array $payMethods ): array {
		$sort               = $this->get_option( 'custom_order', '' );
		$result_methods     = [];
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( $available_gateways ) {
			foreach ( $available_gateways as $available_gateway => $data ) {
				if ( $data->enabled !== 'yes' ) {
					continue;
				}

				switch ( $available_gateway ) {
					case 'payucreditcard':
					case 'payusecureform':
						$this->unset_banks[] = 'c';
						break;
					case 'payublik':
						$this->unset_banks[] = 'blik';
						break;
					case 'payuinstallments':
						$this->unset_banks[] = 'ai';
						break;
					case 'payuklarna':
						$this->unset_banks[] = 'dpkl';
						break;
					case 'payupaypo':
						$this->unset_banks[] = 'dpp';
						break;
					case 'payutwistopl':
						$this->unset_banks[] = 'dpt';
						break;
				}
			}
		}

		$show_inactive = $this->get_option( 'show_inactive_methods', 'no' ) === 'yes';

		foreach ( $payMethods as $payMethod ) {
			if ( ! in_array( $payMethod->value, $this->unset_banks ) ) {
				if ( $show_inactive && $payMethod->value !== 't' ) {
					$show_method = true;
					if ( $payMethod->status !== 'ENABLED' ) {
						$show_method = false;
					} else {
						if ( ! $this->check_min_max( $payMethod ) ) {
							$show_method = false;
						}
					}
					$result_methods[ $payMethod->value ] = [
						'paytype'       => $payMethod->value,
                        'brandImageUrl' => $payMethod->brandImageUrl,
						'name'          => $payMethod->name,
						'active'        => $show_method ? 'payu-active' : 'payu-inactive'
					];
				} else {
					if ( $payMethod->status === 'ENABLED' ) {
						$can_be_use = true;
						if ( ! $this->check_min_max( $payMethod ) ) {
							$can_be_use = false;
						}
						if ( $can_be_use ) {
							$result_methods[ $payMethod->value ] = [
								'paytype'       => $payMethod->value,
								'brandImageUrl' => $payMethod->brandImageUrl,
								'name'          => $payMethod->name,
								'active'        => 'payu-active',
							];
						}
					}
				}
			}
		}

		if ( ! $sort ) {
			$first_paytypes = [ 'c', 'ap', 'jp', 'vc' ];
			$last_paytypes  = [ 'b', 'pt', 'bt' ];
		} else {
			$first_paytypes = explode( ',', str_replace( ' ', '', $sort ) );
			$last_paytypes  = [];
		}

		list( $first, $result_methods ) = $this->extract_paytypes( $result_methods, $first_paytypes );
		list( $last, $result_methods ) = $this->extract_paytypes( $result_methods, $last_paytypes );

		return array_merge( $first, $result_methods, $last );
	}

	private function extract_paytypes( array $result_methods, array $paytypes ): array {
		$extracted = [];
		foreach ( $paytypes as $item ) {
			if ( array_key_exists( $item, $result_methods ) ) {
				$extracted[ $item ] = [
					'paytype'       => $result_methods[ $item ]['paytype'],
					'brandImageUrl' => $result_methods[ $item ]['brandImageUrl'],
					'name'          => $result_methods[ $item ]['name'],
					'active'        => $result_methods[ $item ]['active'],
				];
				unset( $result_methods[ $item ] );
			}
		}

		return [ $extracted, $result_methods ];
	}


	protected function get_payu_pay_method(): array {
		$selected_method = sanitize_text_field( $_POST['selected-bank'] );

		return $this->get_payu_pay_method_array( 'PBL', $selected_method );
	}

	protected function get_additional_gateway_fields(): array {
		return [
			'custom_order'          => [
				'title'       => __( 'Custom order:', 'woo-payu-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Custom order, separate payment methods with commas', 'woo-payu-payment-gateway' ),
				'placeholder' => __( 'Custom order, separate payment methods with commas', 'woo-payu-payment-gateway' ),
				'desc_tip'    => true
			],
			'show_inactive_methods' => [
				'title'       => __( 'Inactive methods', 'woo-payu-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Show inactive payment methods as grayed out', 'woo-payu-payment-gateway' ),
				'label'       => __( 'Show as grayed out', 'woo-payu-payment-gateway' ),
				'desc_tip'    => true
			]
		];
	}
}
