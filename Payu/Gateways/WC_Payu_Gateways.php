<?php

namespace Payu\PaymentGateway\Gateways;

use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayU_Refund;
use OpenPayU_Result;
use OpenPayU_Retrieve;
use OpenPayuOrderStatus;
use Payu\PaymentGateway\Cache\OauthCache;
use Payu\PaymentGateway\Settings\PayuSettings;
use WC_Data_Store;
use WC_Order;
use WC_Order_Item_Product;
use WC_Payment_Gateway;
use WC_Shipping_Zone;

abstract class WC_Payu_Gateways extends WC_Payment_Gateway implements WC_PayuGateway {
	public static $paymethods = [];

	public string $pos_id = '';
	public string $pos_widget_key = '';
	public $enable_for_shipping;
	public $enable_for_virtual;

	protected string $paytype = '';

	protected bool $sandbox;

	private $order_total = null;

	const CONDITION_PL = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_pl.pdf';
	const CONDITION_EN = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_pl_en.pdf';
	const CONDITION_CS = 'http://static.payu.com/sites/terms/files/payu_terms_of_service_single_transaction_cs.pdf';
	const PRIVACY_PL = 'https://static.payu.com/sites/terms/files/payu_privacy_policy_pl_pl.pdf';
	const PRIVACY_EN = 'https://static.payu.com/sites/terms/files/payu_privacy_policy_en_en.pdf';
	const PRIVACY_CS = 'https://static.payu.com/sites/terms/files/payu_privacy_policy_cs.pdf';

	function __construct( string $id ) {
		$this->id                 = $id;
		$this->method_title       = $this->gateway_data( 'name' );
		$this->method_description = __( 'Official PayU payment gateway for WooCommerce.', 'woo-payu-payment-gateway' );
		$this->has_fields         = false;
		$this->supports           = [ 'products', 'refunds' ];

		$this->init_form_fields();
		$this->init_settings();

		$this->icon                = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/logo-payu.svg', PAYU_PLUGIN_FILE ) );
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description', ' ' );
		$this->sandbox             = filter_var( $this->get_option( 'sandbox', false ), FILTER_VALIDATE_BOOLEAN );
		$this->enable_for_shipping = $this->get_option( 'enable_for_shipping', [] );
		$this->enable_for_virtual  = $this->get_option( 'enable_for_virtual', 'no' ) === 'yes';

		if ( ! is_admin() && isset( $_GET['pay_for_order'], $_GET['key'] ) ) {
			$order_id = wc_get_order_id_by_order_key( $_GET['key'] );
			if ( $order_id !== 0 ) {
				$order             = wc_get_order( $order_id );
				$this->order_total = $order->get_total();
			}
		}

		if ( ! is_admin() ) {
			$this->init_OpenPayU();
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_payu_gateway_assets' ] );

		// Saving hook
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		// Payment listener/API hook
		add_action( 'woocommerce_api_wc_gateway_' . $this->id, [ $this, 'gateway_ipn' ] );
	}

	public function is_enabled(): bool {
		return filter_var( $this->enabled ?? false, FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payu_method_title(): string {
		return $this->get_title();
	}

	public function get_payu_method_description(): string {
		return $this->description;
	}

	public function get_payu_method_icon(): string {
		return $this->icon;
	}

	public function get_additional_data(): array {
		return [];
	}

	public function get_terms_links(): array {
		return [
			'condition' => $this->get_condition_url(),
			'privacy'   => $this->get_privacy_policy_url()
		];
	}

	public function enqueue_payu_gateway_assets() {
		wp_enqueue_script( 'payu-gateway', plugins_url( '/assets/js/payu-gateway.js', PAYU_PLUGIN_FILE ),
			[ 'jquery' ], PAYU_PLUGIN_VERSION, true );
		wp_enqueue_style( 'payu-gateway', plugins_url( '/assets/css/payu-gateway.css', PAYU_PLUGIN_FILE ),
			[], PAYU_PLUGIN_VERSION );
	}

	/**
	 * @return OpenPayU_Result
	 */
	protected function payu_get_paymethods() {
		$this->init_OpenPayU();
		if ( isset( static::$paymethods[ $this->pos_id ] ) ) {
			return static::$paymethods[ $this->pos_id ];
		} else {
			try {
				static::$paymethods[ $this->pos_id ] = OpenPayU_Retrieve::payMethods();

				return static::$paymethods[ $this->pos_id ];
			} catch ( OpenPayU_Exception $e ) {
				unset( $e );
			}
		}
	}

	protected function get_condition_url(): string {
		$language = get_locale();
		switch ( $language ) {
			case 'pl_PL':
				return self::CONDITION_PL;
			case 'cs_CZ':
				return self::CONDITION_CS;
			default:
				return self::CONDITION_EN;
		}
	}

	protected function get_privacy_policy_url(): string {
		$language = get_locale();
		switch ( $language ) {
			case 'pl_PL':
				return self::PRIVACY_PL;
			case 'cs_CZ':
				return self::PRIVACY_CS;
			default:
				return self::PRIVACY_EN;
		}
	}

	/**
	 * @return void
	 */
	protected function agreements_field(): void {
		echo '<div class="payu-accept-conditions">';
		echo '<div class="payu-conditions-description"><div>' . __( 'Payment is processed by PayU SA; The recipient\'s data, the payment title and the amount are provided to PayU SA by the recipient;',
				'woo-payu-payment-gateway' ) . ' <span class="payu-read-more">' . __( 'read more',
				'woo-payu-payment-gateway' ) . '</span> <span class="payu-more-hidden">' . __( 'The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees.',
				'woo-payu-payment-gateway' ) . '</span>';
		echo '</div><div>';
		printf( __( 'By paying you accept <a href="%s" target="_blank">"PayU Payment Terms".</a>',
			'woo-payu-payment-gateway' ),
			esc_url( $this->get_condition_url() ) );
		echo '</div><div>';
		echo __( 'The controller of your personal data is PayU S.A. with its registered office in Poznan (60-166), at Grunwaldzka Street 186 ("PayU").',
				'woo-payu-payment-gateway' ) . ' <span class="payu-read-more">' . __( 'read more',
				'woo-payu-payment-gateway' ) . '</span> <span class="payu-more-hidden">';
		echo __( 'Your personal data will be processed for purposes of processing  payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.',
				'woo-payu-payment-gateway' ) . '<br />';
		echo __( 'The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.',
				'woo-payu-payment-gateway' ) . '<br />';
		echo __( 'You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click ',
			'woo-payu-payment-gateway' );
		printf( __( '<a href="%s" target="_blank">PayU privacy policy</a>', 'woo-payu-payment-gateway' ), esc_url( $this->get_privacy_policy_url() ) );
		echo '</span></div>';
		echo '</div>';
		echo '</div>';
	}

	public static function gateways_list(): array {
		return [
			'payustandard'     => [
				'name'                => __( 'PayU - standard', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Online payment by PayU', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to a payment method selection page.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuStandard'
			],
			'payulistbanks'    => [
				'name'                => __( 'PayU - list banks', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Online payment by PayU', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'Choose payment method.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuListBanks'
			],
			'payucreditcard'   => [
				'name'                => __( 'PayU - credit card', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Card payment with PayU', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to a card form.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuCreditCard'
			],
			'payusecureform'   => [
				'name'                => __( 'PayU - secure form', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Card payment with PayU', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You may be redirected to a payment confirmation page.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuSecureForm'
			],
			'payublik'         => [
				'name'                => __( 'PayU - Blik', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Blik', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to BLIK.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuBlik'
			],
			'payuinstallments' => [
				'name'                => __( 'PayU - installments', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'PayU installments', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to an installment payment application.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuInstallments'
			],
			'payuklarna'       => [
				'name'                => __( 'PayU - Klarna', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Pay later with Klarna', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to the payment method page.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuKlarna'
			],
			'payupaypo'        => [
				'name'                => __( 'PayU - PayPo', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Pay later with PayPo', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to the payment method page.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuPaypo'
			],
			'payutwistopl'     => [
				'name'                => __( 'PayU - Twisto', 'woo-payu-payment-gateway' ),
				'front_name'          => __( 'Pay later with Twisto', 'woo-payu-payment-gateway' ),
				'default_description' => __( 'You will be redirected to the payment method page.', 'woo-payu-payment-gateway' ),
				'api'                 => 'WC_Gateway_PayuTwistoPl'
			],
		];
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function gateway_data( $field ) {
		$names = self::gateways_list();

		return $names[ $this->id ][ $field ];
	}

	function init_form_fields() {
		$currencies = woocommerce_payu_get_currencies();

		$this->form_fields = array_merge(
			$this->get_form_fields_basic(),
			$this->get_form_field_config( $currencies ),
			$this->get_form_field_info(),
			$this->get_additional_gateway_fields()
		);
	}

	protected function get_additional_gateway_fields(): array {
		return [];
	}

	private function get_form_fields_basic(): array {
		return [
			'enabled'    => [
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'label'       => __( 'Enable PayU payment method', 'woo-payu-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'If you do not already have PayU merchant account, <a href="https://poland.payu.com/en/how-to-activate-payu/" target="_blank" rel="nofollow">please register in Production</a> or <a href="https://secure.snd.payu.com/boarding/#/registerSandbox/?lang=en" target="_blank" rel="nofollow">please register in Sandbox</a>.',
					'woo-payu-payment-gateway' ),
				'default'     => 'no',
			],
			'title'      => [
				'title'       => __( 'Title', 'woo-payu-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Title of PayU Payment Gateway that users sees on Checkout page.', 'woo-payu-payment-gateway' ),
				'default'     => self::gateways_list()[ $this->id ]['front_name'],
				'desc_tip'    => true
			],
			'sandbox'    => [
				'title'   => __( 'Sandbox mode', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Use sandbox environment.', 'woo-payu-payment-gateway' ),
				'default' => 'no'
			],
			'use_global' => [
				'title'             => __( 'Use global values', 'woo-payu-payment-gateway' ),
				'type'              => 'checkbox',
				'label'             => __( 'Use global values.', 'woo-payu-payment-gateway' ),
				'default'           => 'yes',
				'custom_attributes' => [ 'data-toggle-global' => '1' ]
			]
		];
	}

	private function get_form_field_config( array $currencies = [] ): array {
		if ( count( $currencies ) < 2 ) {
			$currencies = [ '' ];
		}
		$config       = [];
		$payuSettings = get_option( 'payu_settings_option_name', [] );

		foreach ( $currencies as $code ) {
			$idSuffix   = ( $code ? '_' : '' ) . $code;
			$namePrefix = $code . ( $code ? ' - ' : '' );
			$fields     = PayuSettings::payu_fields();
			$settings   = [];
			foreach ( $fields as $field => $desc ) {
				$field              = $field . $idSuffix;
				$settings[ $field ] = [
					'title'             => $namePrefix . $desc['label'],
					'type'              => 'text',
					'description'       => $namePrefix . $desc['description'],
					'desc_tip'          => true,
					'custom_attributes' => [
						'data-global'  => 'can-be-global',
						'global-value' => $payuSettings[ 'global_' . $field ] ?? '',
						'local-value'  => $payuSettings[ $field ] ?? ''
					],
				];
			}
			$config += $settings;
		}

		return $config;
	}

	private function get_form_field_info(): array {
		return [
			'description'         => [
				'title'       => __( 'Description', 'woo-payu-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Description of PayU Payment Gateway that users sees on Checkout page.', 'woo-payu-payment-gateway' ),
				'default'     => self::gateways_list()[ $this->id ]['default_description'],
				'desc_tip'    => true
			],
			'enable_for_shipping' => [
				'title'             => __( 'Enable for shipping methods', 'woo-payu-payment-gateway' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If PayU is only available for certain methods, set it up here. Leave blank to enable for all methods.',
					'woo-payu-payment-gateway' ),
				'options'           => $this->getShippingMethods(),
				'desc_tip'          => true,
				'custom_attributes' => [
					'data-placeholder' => __( 'Select shipping methods', 'woo-payu-payment-gateway' ),
				],
			],
			'enable_for_virtual'  => array(
				'title'   => __( 'Virtual orders', 'woo-payu-payment-gateway' ),
				'label'   => __( 'Enable for virtual orders', 'woo-payu-payment-gateway' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		];
	}

	/**
	 * @return array
	 * @throws
	 */
	private function getShippingMethods() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return [];
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = [];
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = [];

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method',
				'woocommerce' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ),
						$shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ),
						$zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ),
						$option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 * Copy from COD module
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}

			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			global $wp;
			if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'],
					'/payment_gateways' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @throws
	 */
	public function init_OpenPayU( string $currency = null ): void {
		$isSandbox = 'yes' === $this->get_option( 'sandbox' );

		if ( woocommerce_payu_is_wmpl_active_and_configure() || woocommerce_payu_is_currency_custom_config() ) {
			$optionSuffix = '_' . ( null !== $currency ? $currency : get_woocommerce_currency() );
		} else {
			$optionSuffix = '';
		}

		$optionPrefix = $isSandbox ? 'sandbox_' : '';
		$payuSettings = get_option( 'payu_settings_option_name', [] );

		OpenPayU_Configuration::setEnvironment( $isSandbox ? 'sandbox' : 'secure' );
		if ( $this->get_option( 'use_global', 'yes' ) === 'yes' ) {
			$this->pos_id         = $payuSettings[ 'global_' . $optionPrefix . 'pos_id' . $optionSuffix ] ?? '';
			$client_secret        = $payuSettings[ 'global_' . $optionPrefix . 'client_secret' . $optionSuffix ] ?? '';
			$this->pos_widget_key = substr( $client_secret, 0, 2 );
			OpenPayU_Configuration::setMerchantPosId( $this->pos_id );
			OpenPayU_Configuration::setSignatureKey( $payuSettings[ 'global_' . $optionPrefix . 'md5' . $optionSuffix ] ?? '' );
			OpenPayU_Configuration::setOauthClientId( $payuSettings[ 'global_' . $optionPrefix . 'client_id' . $optionSuffix ] ?? '' );
			OpenPayU_Configuration::setOauthClientSecret( $client_secret );
		} else {
			$this->pos_id         = $this->get_option( $optionPrefix . 'pos_id' . $optionSuffix, '' );
			$client_secret        = $this->get_option( $optionPrefix . 'client_secret' . $optionSuffix, '' );
			$this->pos_widget_key = substr( $client_secret, 0, 2 );
			OpenPayU_Configuration::setMerchantPosId( $this->pos_id );
			OpenPayU_Configuration::setSignatureKey( $this->get_option( $optionPrefix . 'md5' . $optionSuffix, '' ) );
			OpenPayU_Configuration::setOauthClientId( $this->get_option( $optionPrefix . 'client_id' . $optionSuffix, '' ) );
			OpenPayU_Configuration::setOauthClientSecret( $client_secret );
		}

		OpenPayU_Configuration::setOauthTokenCache( new OauthCache() );
		OpenPayU_Configuration::setSender( 'Wordpress ver ' . get_bloginfo( 'version' ) . ' / WooCommerce ver ' . WC()->version . ' / Plugin ver ' . PAYU_PLUGIN_VERSION );
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 * Copy from COD module
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$order          = null;
		$needs_shipping = false;

		if ( is_page( wc_get_page_id( 'checkout' ) ) && get_query_var( 'order-pay' ) > 0 ) {
			$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

			if ( $order && 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		} elseif ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		}

		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		if ( ! empty( $this->enable_for_shipping ) && $needs_shipping ) {
			$order_shipping_items = is_object( $order ) ? $order->get_shipping_methods() : false;

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( WC()->session->get( 'chosen_shipping_methods' ) );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	protected function process_pay_methods( array $payMethods ): bool {
		foreach ( $payMethods as $payMethod ) {
			if ( $this->check_min_max( $payMethod, $this->paytype ) ) {
				return true;
			}
		}

		return false;
	}

	protected function try_retrieve_banks(): bool {
		$response = $this->payu_get_paymethods();
		if ( isset( $response ) && $response->getStatus() === 'SUCCESS' ) {
			$payMethods = $response->getResponse();

			return $payMethods->payByLinks && $this->process_pay_methods( $payMethods->payByLinks );
		}

		return false;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 * Copy from COD
	 *
	 * @param array $order_shipping_items Array of WC_Order_Item_Shipping objects.
	 *
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = [];

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 * Copy from COD
	 *
	 * @param array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 *
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = [];

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 *
	 * @param array $rate_ids Rate ids to check.
	 *
	 * @return array
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_shipping, $rate_ids ),
			array_intersect( $this->enable_for_shipping,
				array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$this->init_OpenPayU();

		$orderData = [
			'continueUrl'   => $this->get_return_url( $order ),
			'notifyUrl'     => add_query_arg( 'wc-api', $this->gateway_data( 'api' ), home_url( '/' ) ),
			'customerIp'    => $this->getIP(),
			'merchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
			'description'   => get_bloginfo( 'name' ) . ' #' . $order->get_order_number(),
			'currencyCode'  => get_woocommerce_currency(),
			'totalAmount'   => $this->toAmount( $order->get_total() ),
			'extOrderId'    => uniqid( $order_id . '_', true ),
			'products'      => $this->getProducts( $order ),
			'buyer'         => $this->getBuyer( $order ),
		];

		if ( $this->id !== 'payustandard' ) {
			$orderData['payMethods'] = $this->get_payu_pay_method();
		}

		$threeDsAuthentication = $this->getThreeDsAuthentication( $order, $orderData );
		if ( $threeDsAuthentication !== false ) {
			$orderData['threeDsAuthentication'] = $threeDsAuthentication;
		}

		try {
			$response = OpenPayU_Order::create( $orderData );

			if ( $response->getStatus() === OpenPayU_Order::SUCCESS || $response->getStatus() === 'WARNING_CONTINUE_3DS' ) {

				WC()->cart->empty_cart();

				//add link to email
				if ( isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
					add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 3 );
				}
				$order->set_transaction_id( $response->getResponse()->orderId );
				$order->update_status( get_option( 'payu_settings_option_name' )['global_default_on_hold_status'],
					__( 'Awaiting PayU payment.', 'woo-payu-payment-gateway' ) );

				$redirect = $this->get_return_url( $order );
				if ( $response->getResponse()->redirectUri ) {
					$redirect = $response->getResponse()->redirectUri;
				}
				$result = [
					'result'   => 'success',
					'redirect' => $redirect
				];

				return $result;
			} else {
				throw new \Exception( __( 'Payment error. Status code: ', 'woo-payu-payment-gateway' ) . $response->getStatus() );
			}
		} catch ( \OpenPayU_Exception $e ) {
			throw new \Exception( __( 'Payment error: ', 'woo-payu-payment-gateway' ) . $e->getMessage() . ' (' . $e->getCode() . ')' );
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	private function getProducts( $order ) {
		$products = [];
		$i        = 0;

		/** @var WC_Order_Item_Product $item */
		foreach ( $order->get_items() as $item ) {
			$quantity = $item->get_quantity();
			$name     = $item->get_name();

			if ( fmod( $quantity, 1 ) !== 0.0 ) {
				$quantity = ceil( $quantity );
				$name     = '[' . round( $item->get_quantity(), wc_get_rounding_precision() ) . '] ' . $name;
			}

			if ( $quantity === 0 ) {
				$quantity = 1;
			}

			$name = mb_substr( $name, 0, 255 );

			$products[ $i ] = [
				'name'      => $name,
				'unitPrice' => $this->toAmount( $order->get_item_total( $item, true ) ),
				'quantity'  => $quantity,
			];

			if ( $item->get_product()->is_virtual() ) {
				$products[ $i ]['virtual'] = true;
			}

			$i ++;
		}

		if ( ! empty( $order->get_shipping_methods() ) ) {
			$products[] = [
				'name'      => mb_substr( 'Shipment' . ' [' . $order->get_shipping_method() . ']', 0, 255 ),
				'unitPrice' => $this->toAmount( round( $order->get_shipping_total(), wc_get_rounding_precision() ) + round( $order->get_shipping_tax(), wc_get_rounding_precision() ) ),
				'quantity'  => 1,
			];
		}

		if ( $order->get_total_discount( false ) !== 0.0 ) {
			$products[] = [
				'name'      => 'Discount',
				'unitPrice' => $this->toAmount( $order->get_total_discount( false ) ) * - 1,
				'quantity'  => 1,
			];
		}

		return $products;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	private function getBuyer( $order ) {
		$billingData = $order->get_address( 'billing' );

		$buyer = [
			'email'    => $billingData['email'],
			'phone'    => $billingData['phone'],
			'language' => $this->getLanguage(),
		];

		if ( $order->get_billing_first_name() ) {
			$buyer['firstName'] = $order->get_billing_first_name();
		}

		if ( $order->get_billing_last_name() ) {
			$buyer['lastName'] = $order->get_billing_last_name();
		}

		if ( ! empty( $order->get_shipping_methods() ) ) {
			$shippingData = $order->get_address( 'shipping' );

			$buyer['delivery'] = [
				'street'     => $shippingData['address_1'] . ( $shippingData['address_2'] ? ' ' . $shippingData['address_2'] : '' ),
				'postalCode' => $shippingData['postcode'],
				'city'       => $shippingData['city']
			];

			if ( strlen( $shippingData['country'] ) === 2 ) {
				$buyer['delivery']['countryCode'] = $shippingData['country'];
			}

		}

		return $buyer;
	}

	/**
	 * @param WC_Order $order
	 * @param array $orderData
	 *
	 * @return array | false
	 */
	private function getThreeDsAuthentication( $order, $orderData ) {
		if ( ! isset( $orderData['payMethods'] )
		     || $orderData['payMethods']['payMethod']['type'] === 'CARD_TOKEN'
		     || $orderData['payMethods']['payMethod']['value'] === 'c'
		     || $orderData['payMethods']['payMethod']['value'] === 'ap'
		     || $orderData['payMethods']['payMethod']['value'] === 'jp'
		     || $orderData['payMethods']['payMethod']['value'] === 'ma'
		     || $orderData['payMethods']['payMethod']['value'] === 'vc'
		) {

			$billingData           = $order->get_address( 'billing' );
			$threeDsAuthentication = false;

			$names = [];
			if ( ! empty( $order->get_billing_first_name() ) ) {
				$names[] = $order->get_billing_first_name();
			}
			if ( ! empty( $order->get_billing_last_name() ) ) {
				$names[] = $order->get_billing_last_name();
			}
			$name = trim( implode( ' ', $names ) );

			$address     = $billingData['address_1'] . ( $billingData['address_2'] ? ' ' . $billingData['address_2'] : '' );
			$postalCode  = $billingData['postcode'];
			$city        = $billingData['city'];
			$countryCode = $billingData['countryCode'] ?? '';

			$isBillingAddress = ! empty( $address ) || ! empty( $postalCode ) || ! empty( $city ) || ( ! empty( $countryCode ) && strlen( $countryCode ) === 2 );

			if ( ! empty( $name ) || $isBillingAddress ) {
				$threeDsAuthentication = [
					'cardholder' => []
				];

				if ( ! empty( $name ) ) {
					$threeDsAuthentication['cardholder']['name'] = mb_substr( $name, 0, 45 );
				}

				if ( $isBillingAddress ) {
					$threeDsAuthentication['cardholder']['billingAddress'] = [];
				}

				if ( ! empty( $countryCode ) && strlen( $countryCode ) === 2 ) {
					$threeDsAuthentication['cardholder']['billingAddress']['countryCode'] = $countryCode;
				}

				if ( ! empty( $address ) ) {
					$threeDsAuthentication['cardholder']['billingAddress']['street'] = mb_substr( $address, 0, 50 );
				}

				if ( ! empty( $city ) ) {
					$threeDsAuthentication['cardholder']['billingAddress']['city'] = mb_substr( $city, 0, 50 );
				}

				if ( ! empty( $postalCode ) ) {
					$threeDsAuthentication['cardholder']['billingAddress']['postalCode'] = mb_substr( $postalCode, 0, 16 );
				}
			}

			if ( isset( $orderData['payMethods']['payMethod']['type'] ) && $orderData['payMethods']['payMethod']['type'] === 'CARD_TOKEN' ) {
				$possibleBrowserData = [
					'screenWidth',
					'javaEnabled',
					'timezoneOffset',
					'screenHeight',
					'userAgent',
					'colorDepth',
					'language'
				];

				$browserData = [];

				if ( isset( $_POST['payu_browser'] ) && is_array( $_POST['payu_browser'] ) ) {
					foreach ( $possibleBrowserData as $bd ) {
						$browserData[ $bd ] = isset( $_POST['payu_browser'][ $bd ] ) ? sanitize_text_field( $_POST['payu_browser'][ $bd ] ) : '';
					}
				} else {
					foreach ( $possibleBrowserData as $bd ) {
						$name = strtolower( 'payuBrowser_' . $bd );
						if ( isset( $_POST[ $name ] ) ) {
							$browserData[ $bd ] = sanitize_text_field( $_POST[ $name ] );
						}
					}
				}

				if ( count( $browserData ) > 0 ) {
					$browserData['requestIP'] = $this->getIP();


					if ( empty( $browserData['userAgent'] ) ) {
						$headers = array_change_key_case( getallheaders(), CASE_LOWER );
						if ( $headers['user-agent'] ) {
							$browserData['userAgent'] = $headers['user-agent'];
						}
					}

					$threeDsAuthentication['browser'] = $browserData;
				}
			}

			return $threeDsAuthentication;
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function getIP() {
		return ( $_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '::' ||
		         ! preg_match( '/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
			         $_SERVER['REMOTE_ADDR'] ) ) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * @return string
	 */
	protected function getLanguage() {
		return substr( get_locale(), 0, 2 );
	}

	/**
	 * @param float $value
	 *
	 * @return int
	 */
	protected function toAmount( $value ) {
		return (int) round( $value * 100 );
	}

	/**
	 * @param int $order_id
	 *
	 * @return string|bool
	 */
	protected function completed_transaction_id( $order_id ) {
		$order         = wc_get_order( $order_id );
		$payu_statuses = $order->get_meta( '_payu_order_status', false, '' );
		foreach ( $payu_statuses as $payu_status ) {
			$ps = explode( '|', $payu_status->value );
			if ( $ps[0] === OpenPayuOrderStatus::STATUS_COMPLETED ) {
				return $ps[1];
			}
		}

		return false;
	}

	/**
	 * @param int $order_id
	 * @param null|float $amount
	 * @param string $reason
	 *
	 * @return bool
	 * @throws
	 *
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( $amount > 0 ) {
			$order   = wc_get_order( $order_id );
			$orderId = $this->completed_transaction_id( $order_id );
			if ( empty( $orderId ) ) {
				return false;
			}

			$this->init_OpenPayU( $order->get_currency() );
			$refund = OpenPayU_Refund::create(
				$orderId,
				__( 'Refund of: ', 'woo-payu-payment-gateway' ) . ' ' . $amount . $this->getOrderCurrency( $order ) . __( ' for order: ',
					'woo-payu-payment-gateway' ) . $order_id,
				$this->toAmount( $amount )
			);


			return ( $refund->getStatus() === 'SUCCESS' );
		}

		return false;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function getOrderCurrency( $order ) {
		return method_exists( $order, 'get_currency' ) ? $order->get_currency() : $order->get_order_currency();
	}

	protected function get_payu_pay_method(): array {
		return $this->get_payu_pay_method_array( 'PBL', $this->paytype );
	}

	protected function get_payu_pay_method_array( string $type, string $value ): array {
		return [
			'payMethod' => [
				'type'  => $type,
				'value' => $value
			]
		];
	}

	/**
	 * @param object $payMethod
	 * @param null|string $paytype
	 *
	 * @return bool
	 */
	protected function check_min_max( $payMethod, $paytype = null ) {
		if ( ( $paytype === null || $payMethod->value === $paytype ) && $payMethod->status === 'ENABLED' ) {
			$total = $this->getTotal() * 100;

			if ( isset( $payMethod->minAmount ) && $total < $payMethod->minAmount ) {
				return false;
			}
			if ( isset( $payMethod->maxAmount ) && $total > $payMethod->maxAmount ) {
				return false;
			}

			return true;
		}

		return false;
	}

	protected function getTotal(): float {
		if ( $this->order_total !== null ) {
			return $this->order_total;
		} elseif ( WC()->cart && 0 !== count( WC()->cart->get_cart_contents() ) ) {
			return WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() + WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
		}

		return 0;
	}

	/**
	 * @param string $notification
	 *
	 * @return null|string
	 */
	private function extractCurrencyFromNotification( $notification ) {
		$notification = json_decode( $notification );

		if ( is_object( $notification ) && isset( $notification->order->currencyCode ) ) {
			return $notification->order->currencyCode;
		} elseif ( is_object( $notification ) && isset( $notification->refund->currencyCode ) ) {
			return $notification->refund->currencyCode;
		}

		return null;
	}

	/**
	 * @return void
	 * @throws
	 *
	 */
	function gateway_ipn() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$body = file_get_contents( 'php://input' );
			$data = trim( $body );

			$currency = $this->extractCurrencyFromNotification( $data );

			if ( null !== $currency ) {
				$this->init_OpenPayU( $currency );
			}

			try {
				$response = OpenPayU_Order::consumeNotification( $data );

			} catch ( \Exception $e ) {
				header( 'X-PHP-Response-Code: 500', true, 500 );

				die( $e->getMessage() );
			}

			if ( property_exists( $response->getResponse(), 'refund' ) ) {
				$reportOutput = 'Refund notification - ignore|';
				$order_id     = (int) preg_replace( '/_.*$/', '', $response->getResponse()->extOrderId );
				$order        = wc_get_order( $order_id );
				$note         = '[PayU] ' . $response->getResponse()->refund->reasonDescription . ' ' . __( 'has status', 'woo-payu-payment-gateway' ) . ' ' . $response->getResponse()->refund->status;
				$order->add_order_note( $note );
			} else {
				$order_id       = (int) preg_replace( '/_.*$/', '', $response->getResponse()->order->extOrderId );
				$status         = $response->getResponse()->order->status;
				$transaction_id = $response->getResponse()->order->orderId;

				$reportOutput = 'OID: ' . $order_id . '|PS: ' . $status . '|TID: ' . $transaction_id . '|';

				$order = wc_get_order( $order_id );

				$reportOutput .= 'WC AS: ' . $order->get_status() . '|';
				$order->add_meta_data( '_payu_order_status', $status . '|' . $response->getResponse()->order->orderId );
				if ( $order->get_status() !== 'completed' && $order->get_status() !== 'processing' ) {
					switch ( $status ) {
						case OpenPayuOrderStatus::STATUS_CANCELED:
							if ( ! isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
								$status = apply_filters( 'woocommerce_payu_status_cancelled', 'cancelled', $order );
								$order->update_status( $status, __( 'Payment has been cancelled.', 'woo-payu-payment-gateway' ) );
							}
							break;

						case OpenPayuOrderStatus::STATUS_COMPLETED:
							$order->payment_complete( $transaction_id );
							break;

						case OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION:
							if ( $order->get_status() === 'cancelled' ) {
								$response_order_id = $response->getResponse()->order->orderId;
								OpenPayU_Order::cancel( $response_order_id );
							} else {
								$order->update_status( PAYU_PLUGIN_STATUS_WAITING,
									__( 'Payment has been put on hold - merchant must approve this payment manually.',
										'woo-payu-payment-gateway' )
								);
								if ( isset( get_option( 'payu_settings_option_name' )['global_repayment'] ) ) {
									$payu_statuses = $order->get_meta( '_payu_order_status', false );

									if ( in_array( OpenPayuOrderStatus::STATUS_COMPLETED,
										$this->clean_payu_statuses( $payu_statuses ) ) ) {
										OpenPayU_Refund::create(
											$transaction_id,
											__( 'Refund of: ',
												'woo-payu-payment-gateway' ) . ' ' . $order->get_total() . $this->getOrderCurrency( $order ) . __( ' for order: ',
												'woo-payu-payment-gateway' ) . $order_id,
											$this->toAmount( $order->get_total() )
										);
									} else {
										$status_update = [
											"orderId"     => $transaction_id,
											"orderStatus" => OpenPayuOrderStatus::STATUS_COMPLETED
										];
										OpenPayU_Order::statusUpdate( $status_update );
									}
								}
							}
							break;
					}
				} else {
					if ( $status === OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION ) {
						$response_order_id = $response->getResponse()->order->orderId;
						OpenPayU_Order::cancel( $response_order_id );
					}
				}
				$reportOutput .= 'WC BS: ' . $order->get_status() . '|';
			}

			header( "HTTP/1.1 200 OK" );

			echo esc_html( $reportOutput );
		}

		ob_flush();
	}

	/**
	 * @param array $payu_statuses
	 *
	 * @return array
	 */
	public static function clean_payu_statuses( $payu_statuses ) {
		$result = [];
		if ( is_array( $payu_statuses ) ) {
			foreach ( $payu_statuses as $payu_status ) {
				$status = explode( '|', $payu_status->value )[0];
				array_push( $result, $status );
			}
		}

		return $result;
	}

	/**
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 *
	 * @return void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $sent_to_admin && $order->has_status( get_option( 'payu_settings_option_name' )['global_default_on_hold_status'] ) ) {
			$url = add_query_arg( [
				'pay_for_order' => 'true',
				'key'           => $order->get_order_key()
			], wc_get_endpoint_url( 'order-pay', $order->get_id(), wc_get_checkout_url() ) );

			echo esc_html__( 'If you have not yet paid for the order, you can do so by going to', 'woo-payu-payment-gateway' ) . ' ' . (
				$plain_text
					? esc_html__( 'the website', 'woo-payu-payment-gateway' ) . ': ' . esc_url( $url ) . "\n"
					: '<a href="' . esc_url( $url ) . '">' . esc_html__( 'the website', 'woo-payu-payment-gateway' ) . '</a>.<br /><br />'
				);
		}
	}

	/**
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function unset_gateway( $gateways ) {
		unset( $gateways[ $this->id ] );

		return $gateways;
	}
}
