<?php

namespace Payu\PaymentGateway\Settings;
class PayuSettings {
	private array $payu_settings_options;
	private array $fields;

	public function __construct() {
		$this->fields = $this->payu_fields();
		$this->payu_settings_options = get_option( 'payu_settings_option_name', [] );

		add_action( 'admin_menu', [ $this, 'payu_settings_add_plugin_page' ] );
		add_action( 'admin_init', [ $this, 'payu_settings_page_init' ] );
	}

	public static function payu_fields(): array {
		return [
			'pos_id'                => [
				'label'       => __( 'Id point of sales', 'woo-payu-payment-gateway' ),
				'description' => __( 'Pos identifier from "Configuration Keys" section of PayU management panel.',
					'woo-payu-payment-gateway' )
			],
			'md5'                   => [
				'label'       => __( 'Second key (MD5)', 'woo-payu-payment-gateway' ),
				'description' => __( 'Second key from "Configuration Keys" section of PayU management panel.', 'woo-payu-payment-gateway' )
			],
			'client_id'             => [
				'label'       => __( 'OAuth - client_id', 'woo-payu-payment-gateway' ),
				'description' => __( 'Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.',
					'woo-payu-payment-gateway' )
			],
			'client_secret'         => [
				'label'       => __( 'OAuth - client_secret', 'woo-payu-payment-gateway' ),
				'description' => __( 'First key from "Configuration Keys" section of PayU management panel.', 'woo-payu-payment-gateway' ),
			],
			'sandbox_pos_id'        => [
				'label'       => __( 'Sandbox - Id point of sales', 'woo-payu-payment-gateway' ),
				'description' => __( 'Pos identifier from "Configuration Keys" section of PayU management panel.',
					'woo-payu-payment-gateway' ),
			],
			'sandbox_md5'           => [
				'label'       => __( 'Sandbox - Second key (MD5)', 'woo-payu-payment-gateway' ),
				'description' => __( 'Second key from "Configuration Keys" section of PayU management panel.', 'woo-payu-payment-gateway' ),
			],
			'sandbox_client_id'     => [
				'label'       => __( 'Sandbox - OAuth - client_id', 'woo-payu-payment-gateway' ),
				'description' => __( 'Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.',
					'woo-payu-payment-gateway' ),
			],
			'sandbox_client_secret' => [
				'label'       => __( 'Sandbox - OAuth - client_secret', 'woo-payu-payment-gateway' ),
				'description' => __( 'First key from "Configuration Keys" section of PayU management panel.', 'woo-payu-payment-gateway' ),
			],
		];
	}

	public function payu_settings_add_plugin_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'PayU settings', 'woo-payu-payment-gateway' ), // page_title
			__( 'PayU settings', 'woo-payu-payment-gateway' ), // menu_title
			'manage_options', // capability
			'payu-settings', // menu_slug
			[ $this, 'payu_settings_create_admin_page' ], // function
			100
		);
	}

	public function payu_settings_create_admin_page(): void {
		 ?>

        <div class="wrap">
            <h2><?php esc_html_e( 'PayU settings', 'woo-payu-payment-gateway' ) ?></h2>
            <p></p>
			<?php settings_errors(); ?>

            <form method="post" action="options.php">
				<?php
				settings_fields( 'payu_settings_option_group' );
				do_settings_sections( 'payu-settings-admin' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function payu_settings_page_init(): void {
		global $woocommerce_wpml;
		register_setting(
			'payu_settings_option_group', // option_group
			'payu_settings_option_name', // option_name
			[ $this, 'payu_settings_sanitize' ] // sanitize_callback
		);

		//global
		add_settings_section(
			'payu_settings_setting_section', // id
			__( 'PayU config global', 'woo-payu-payment-gateway' ), // title
			[], // callback
			'payu-settings-admin' // page
		);
		$currencies = woocommerce_payu_get_currencies();
		if ( count( $currencies ) < 2 ) {
			$currencies = [ '' ];
		}

		foreach ( $currencies as $code ) {
			$idSuffix   = ( $code ? '_' : '' ) . $code;
			$namePrefix = $code . ( $code ? ' - ' : '' );
			foreach ( $this->fields as $field => $desc ) {
				$args = [
					'id'   => 'global_' . $field . $idSuffix,
					'desc' => $namePrefix . $desc['label'],
					'name' => 'payu_settings_option_name'
				];
				add_settings_field(
					$args['id'], // id
					$args['desc'], // title
					[ $this, 'global_callback' ], // callback
					'payu-settings-admin', // page
					'payu_settings_setting_section',
					$args
				);
			}
		}
		add_settings_field(
			'global_default_on_hold_status', // id
			__( 'Default on-hold status', 'woo-payu-payment-gateway' ), // title
			[ $this, 'global_default_on_hold_status_callback' ], // callback
			'payu-settings-admin', // page
			'payu_settings_setting_section' // section
		);
		add_settings_field(
			'global_repayment', // id
			__( 'Enable repayment', 'woo-payu-payment-gateway' ), // title
			[ $this, 'global_repayment_callback' ], // callback
			'payu-settings-admin', // page
			'payu_settings_setting_section' // section
		);
	}

	public function global_callback(array $args ): void {
		$id    = $args['id'];
		$value = isset( $this->payu_settings_options[ $id ] ) ? esc_attr( $this->payu_settings_options[ $id ] ) : '';
		printf( '<input type="text" class="regular-text" value="%s" name="payu_settings_option_name[%s]" id="%s" />',
			$value, $id, $id );
	}

	public function payu_settings_sanitize( array $input ): array {
		$sanitary_values = [];
		$currencies      = woocommerce_payu_get_currencies();
		if ( count( $currencies ) < 2 ) {
			$currencies = [ '' ];
		}
		foreach ( $currencies as $code ) {
			$idSuffix = ( $code ? '_' : '' ) . $code;
			foreach ( $this->fields as $field => $desc ) {
				$field = $field . $idSuffix;
				if ( isset( $input[ 'global_' . $field ] ) ) {
					$sanitary_values[ 'global_' . $field ] = sanitize_text_field( $input[ 'global_' . $field ] );
				}
			}
		}

		if ( isset( $input['global_default_on_hold_status'] ) ) {
			$sanitary_values['global_default_on_hold_status'] = sanitize_text_field( $input['global_default_on_hold_status'] );
		}

		if ( isset( $input['global_repayment'] ) ) {
			$sanitary_values['global_repayment'] = sanitize_text_field( $input['global_repayment'] );
		}

		return $sanitary_values;
	}

	public function global_repayment_callback(): void {
		printf(
			'<input type="checkbox" name="payu_settings_option_name[global_repayment]" id="global_repayment" value="global_repayment" %s>',
			( isset( $this->payu_settings_options['global_repayment'] ) && $this->payu_settings_options['global_repayment'] === 'global_repayment' ) ? 'checked' : ''
		);
		?>
        <span class="description payu-red">
            <span class="dashicons dashicons-warning"></span>
            <?php echo wp_kses( __( 'Before enabling repayment, read <a target="_blank" href="https://github.com/PayU-EMEA/woo-payu-payment-gateway#ponawianie-p%C5%82atno%C5%9Bci">the documentation</a> and disable <strong>automatic collection</strong> in POS configuration.', 'woo-payu-payment-gateway' ), [ 'a'      => [
	            'target' => [],
	            'href'   => []
            ],
                                                                                                                                                                                                                                                                                                                    'strong' => []
            ] ); ?>
        </span>
		<?php
	}

	public function global_default_on_hold_status_callback(): void {
		?>
        <select class="regular-text" type="text" name="payu_settings_option_name[global_default_on_hold_status]"
                id="global_default_on_hold_status">
			<?php foreach ( $this->before_payment_statuses() as $key => $value ): ?>
                <option <?php if ( @$this->payu_settings_options['global_default_on_hold_status'] === $key )
					echo 'selected="selected"' ?>
                        value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $value ) ?></option>
			<?php endforeach; ?>
        </select>
		<?php
	}

	public function before_payment_statuses(): array {
		$statuses  = wc_get_order_statuses();
		$available = [];
		foreach ( $statuses as $key => $value ) {
			if ( in_array( $key, [ 'wc-pending', 'wc-on-hold' ] ) ) {
				$available[ str_replace( 'wc-', '', $key ) ] = $value;
			}
		}
		ksort( $available );

		return $available;
	}
}

if ( is_admin() ) {
	new PayuSettings();
}
