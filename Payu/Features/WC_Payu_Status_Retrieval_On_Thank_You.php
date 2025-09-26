<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Features;

use Payu\PaymentGateway\Gateways\WC_Payu_Gateways;
use Payu\PaymentGateway\WC_Payu;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class WC_Payu_Status_Retrieval_On_Thank_You {
	public static function init(): void {
		new self();
	}

	public function __construct() {
		$payu_settings = get_option( 'payu_settings_option_name', [] );

		if ( isset( $payu_settings['global_retrieve_payment_status']) && $payu_settings['global_retrieve_payment_status'] === 'yes' ) {
			add_action( 'rest_api_init', [ $this, 'get_status_by_api' ] );
			add_action( 'woocommerce_before_thankyou', [ $this, 'status_retrieval' ], 10, 1 );
		}
	}

	public function status_retrieval( int $order_id ): void {
		$order         = wc_get_order( $order_id );
		$payu_gateways = WC_Payu_Gateways::gateways_list();

		if ( isset( $payu_gateways[ $order->get_payment_method() ] ) ) {
			if ( $order->has_status( [ 'failed' ] ) || $order->has_status( [ 'cancelled' ] ) || $order->has_status( wc_get_is_paid_statuses() ) ) {
				// do nothing
			} else {
				$this->load_assets();
				$status_url  = get_rest_url() . 'payu/status/' . $order->get_id() . '/?key=' . $order->get_order_key();
				$payment_url = $order->get_checkout_payment_url();

				WC_Payu::template( 'thank-you-pending', [
					'status_url'  => $status_url,
					'payment_url' => $payment_url
				] );
			}
		}
	}

	private function load_assets(): void {
		$asset_path   = WC_PAYU_PLUGIN_PATH . 'build/js/payu.asset.php';
		$version      = PAYU_PLUGIN_VERSION;
		$dependencies = [];

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = $asset['version'] ?? $version;
			$dependencies = $asset['dependencies'] ?? $dependencies;
		}

		wp_enqueue_script( 'payu', plugins_url( '/build/js/payu.js', PAYU_PLUGIN_FILE ), $dependencies, $version );
		wp_set_script_translations( 'payu', 'woo-payu-payment-gateway', WC_PAYU_PLUGIN_PATH . 'lang' );

		wp_enqueue_style( 'payu', plugins_url( '/build/css/payu.css', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION );
	}

	public function get_status_by_api(): void {
		register_rest_route(
			'payu',
			'status/(?P<order_id>\d+)',
			[
				'methods'             => [ WP_REST_Server::READABLE ],
				'callback'            => [ $this, 'wc_payu_gateway_get_status' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function wc_payu_gateway_get_status( WP_REST_Request $request ): WP_REST_Response {
		$order_id = $request->get_param( 'order_id' );
		$key      = $request->get_param( 'key' );
		$order    = wc_get_order( $order_id );

		if ( ! $order || $order->get_order_key() !== $key ) {
			return new WP_REST_Response( [ 'message' => __( 'Unknown Error', 'woo-payu-payment-gateway' ) ], 400 );
		}

		$order_status = $order->get_status();

		if ( in_array( $order_status, wc_get_is_paid_statuses(), true ) ) {
			$response = [ 'status' => 'success' ];
		} else if ( in_array( $order_status, [
			'failed',
			'cancelled'
		], true ) ) {
			$response = [ 'status' => $order_status ];
		} else {
			$response = [ 'status' => 'pending' ];
		}

		return new WP_REST_Response( $response );
	}
}
