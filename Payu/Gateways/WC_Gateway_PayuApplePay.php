<?php
declare( strict_types=1 );

namespace Payu\PaymentGateway\Gateways;

use OpenPayU_ApplePay;
use WP_REST_Response;
use WP_REST_Server;

class WC_Gateway_PayuApplePay extends WC_Payu_Gateways {
    protected string $paytype = 'jp';

    public function __construct() {
        parent::__construct( 'payuapplepay' );

        $this->icon = apply_filters( 'woocommerce_payu_icon', plugins_url( '/assets/images/jp.svg', PAYU_PLUGIN_FILE ) );

        if ( $this->is_enabled() ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'include_scripts' ] );
            add_action( 'rest_api_init', [ $this, 'create_apple_pay_session_by_api' ] );
        }
    }

    public function is_available(): bool {
        if ( ! $this->try_retrieve_banks() || empty( $this->get_option( 'apple_domain_name' ) ) || empty( $this->get_option( 'apple_display_name' ) ) ) {
            return false;
        }

        return parent::is_available();
    }

    public function payment_fields(): void {
        ?>
        <ul class="payu-pay-error woocommerce-error" role="alert">
            <li><?php esc_html_e( 'This payment method is not available.', 'woo-payu-payment-gateway' ) ?></li>
        </ul>
        <?php
        parent::payment_fields();
        ?>
        <script>
            var payuApplePayConfig = {
                currency: "<?php echo esc_attr( get_woocommerce_currency() ) ?>",
                totalPrice: "<?php echo esc_attr( $this->getTotal() ) ?>",
                appleDisplayName: "<?php echo esc_attr( mb_substr( $this->get_option( 'apple_display_name', '' ), 0, 64 ) ) ?>",
                createSessionUrl: "<?php echo rest_url( '/payu/createApplepaySession' )?>"
            }
        </script>
        <input type="hidden" name="payu-apple-token" id="payu-apple-token" value=""/>
        <?php
        $this->agreements_field();
    }

    public function get_additional_data(): array {
        return [
                'currency'         => get_woocommerce_currency(),
                'totalPrice'       => $this->getTotal(),
                'appleDisplayName' => mb_substr($this->get_option( 'apple_display_name', '' ), 0, 64),
                'createSessionUrl' => rest_url( '/payu/createApplepaySession' )
        ];
    }

    public function include_scripts(): void {
        wp_enqueue_script( 'apple-pay', 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js' );
    }

    protected function get_payu_pay_method(): array {
        $token = sanitize_text_field( $_POST['payu-apple-token'] );

        return [
                'payMethod' => [
                        'type'              => 'PBL',
                        'value'             => 'jp',
                        'authorizationCode' => $token
                ]
        ];
    }

    protected function get_additional_gateway_fields(): array {
        return [
                'apple_domain_name'  => [
                        'title'       => __( 'Domain name:', 'woo-payu-payment-gateway' ),
                        'type'        => 'text',
                        'description' => __( ' Domain name registered in Apple. To register the domain follow the <a href="https://developers.payu.com/europe/docs/payment-solutions/cards/digital-wallets/apple-pay/" target="_blank" rel="nofollow">instructions</a>.<br /> <b>IMPORTANT</b>: The domain must match the checkout page domain, otherwise Apple will reject the payment request.
', 'woo-payu-payment-gateway' ),
                ],
                'apple_display_name' => [
                        'title'       => __( 'Display name:', 'woo-payu-payment-gateway' ),
                        'type'        => 'text',
                        'description' => __( 'Apple Pay display name. A string of 64 or fewer UTF-8 characters containing the canonical name for your store, suitable for display.', 'woo-payu-payment-gateway' )
                ]
        ];
    }

    public function create_apple_pay_session_by_api(): void {
        register_rest_route(
                'payu',
                'createApplepaySession',
                [
                        'methods'             => [ WP_REST_Server::CREATABLE ],
                        'callback'            => [ $this, 'create_apple_pay_session' ],
                        'permission_callback' => '__return_true',
                ]
        );
    }

    public function create_apple_pay_session(): WP_REST_Response {
        $this->init_OpenPayU();
        try {
            $session = OpenPayU_ApplePay::createSession(
                    $this->get_option( 'apple_domain_name' ),
                    $this->get_option( 'apple_display_name' )
            );

            return new WP_REST_Response( $session->getResponse() );
        } catch ( \Exception $e ) {
            return new WP_REST_Response( [ 'message' => $e->getMessage() ], 400 );
        }
    }
}
