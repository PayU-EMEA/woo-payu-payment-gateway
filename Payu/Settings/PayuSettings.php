<?php

namespace Payu\PaymentGateway\Settings;
class PayuSettings {
	private array $payu_settings_options;
	private array $fields;
	private array $credit_widget_fields;

	public function __construct() {
		$this->fields                = $this->payu_fields();
		$this->credit_widget_fields  = $this->credit_widget_activation_fields();
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

	public static function credit_widget_activation_fields(): array {
		return [
			'on_listings'      => [
				'type'  => 'checkbox',
				'label' => __( 'Enabled on product listings', 'woo-payu-payment-gateway' )
			],
			'on_product_page'  => [
				'type'  => 'checkbox',
				'label' => __( 'Enabled on product page', 'woo-payu-payment-gateway' )
			],
			'on_cart_page'     => [
				'type'  => 'checkbox',
				'label' => __( 'Enabled on cart page', 'woo-payu-payment-gateway' )
			],
			'on_checkout_page' => [
				'type'  => 'checkbox',
				'label' => __( 'Enabled on checkout page', 'woo-payu-payment-gateway' )
			]
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
			'global_after_canceled_payment_status', // id
			__( 'Order status for failed payment', 'woo-payu-payment-gateway' ), // title
			[ $this, 'global_after_canceled_payment_statuses_callback' ], // callback
			'payu-settings-admin', // page
			'payu_settings_setting_section' // section
		);

		add_settings_field(
			'global_retrieve_payment_status',
			__( 'Enable retrieve status on Thank You page', 'woo-payu-payment-gateway' ),
			[ $this, 'global_retrieve_payment_status_callback' ],
			'payu-settings-admin',
			'payu_settings_setting_section' // section
		);

		add_settings_field(
			'global_repayment', // id
			__( 'Enable repayment', 'woo-payu-payment-gateway' ), // title
			[ $this, 'global_repayment_callback' ], // callback
			'payu-settings-admin', // page
			'payu_settings_setting_section' // section
		);

		//credit widget
		add_settings_section(
			'payu_settings_credit_widget_setting_section', // id
			__( 'Credit widget', 'woo-payu-payment-gateway' ), // title
			[], // callback
			'payu-settings-admin' // page
		);

		foreach ( $this->credit_widget_fields as $field => $desc ) {
			$args = [
				'id'   => 'credit_widget_' . $field,
				'desc' => $desc['label'],
				'name' => 'payu_settings_option_name'
			];
			add_settings_field(
				$args['id'], // id
				$args['desc'], // title
				[ $this, 'credit_widget_default_callback' ], // callback
				'payu-settings-admin', // page
				'payu_settings_credit_widget_setting_section',
				$args
			);
		}

        add_settings_field(
            'credit_widget_excluded_paytypes', // id
            __( 'Credit widget excluded payment types', 'woo-payu-payment-gateway' ), // title
            [ $this, 'credit_widget_excluded_paytypes_callback' ], // callback
            'payu-settings-admin', // page
            'payu_settings_credit_widget_setting_section' // section
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

		if ( isset( $input['global_after_canceled_payment_status'] ) ) {
			$sanitary_values['global_after_canceled_payment_status'] = sanitize_text_field( $input['global_after_canceled_payment_status'] );
		}

		if ( isset( $input['global_repayment'] ) ) {
			$sanitary_values['global_repayment'] = sanitize_text_field( $input['global_repayment'] );
		}

        $sanitary_values['global_retrieve_payment_status'] =
                isset( $input['global_retrieve_payment_status'] ) && $input['global_retrieve_payment_status'] === 'yes' ? 'yes' : 'no';

		foreach ( $this->credit_widget_fields as $field => $desc ) {
			$field_name = 'credit_widget_' . $field;
			$sanitary_values[ $field_name ] = isset( $input[ $field_name ] ) ? 'yes' : 'no';
		}

		if ( isset( $input['credit_widget_excluded_paytypes'] ) ) {
			$excluded_paytypes = explode( ',', $input['credit_widget_excluded_paytypes'] );
			$sanitary_values['credit_widget_excluded_paytypes'] = $this->sanitize_excluded_paytypes( $excluded_paytypes );
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

	public function global_retrieve_payment_status_callback(): void {
		printf(
			'<input type="checkbox" name="payu_settings_option_name[global_retrieve_payment_status]" id="global_retrieve_payment_status" value="yes" %s>',
			( isset( $this->payu_settings_options['global_retrieve_payment_status'] ) && $this->payu_settings_options['global_retrieve_payment_status'] === 'yes' ) ? 'checked' : ''
		);
	}

    public function global_default_on_hold_status_callback(): void {
        ?>
        <select class="regular-text" type="text" name="payu_settings_option_name[global_default_on_hold_status]"
                id="global_default_on_hold_status">
            <?php foreach ( $this->before_payment_statuses() as $key => $value ): ?>
                <option <?php if ( isset( $this->payu_settings_options['global_default_on_hold_status'] ) && $this->payu_settings_options['global_default_on_hold_status'] === $key )
                    echo 'selected="selected"' ?>
                        value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $value ) ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

	public function global_after_canceled_payment_statuses_callback(): void {
		?>
        <select class="regular-text" type="text" name="payu_settings_option_name[global_after_canceled_payment_status]"
                id="global_after_canceled_payment_status">
			<?php foreach ( $this->after_canceled_payment_statuses() as $key => $value ): ?>
                <option <?php if ( isset( $this->payu_settings_options['global_after_canceled_payment_status'] ) && $this->payu_settings_options['global_after_canceled_payment_status'] === $key )
					echo 'selected="selected"' ?>
                        value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $value ) ?></option>
			<?php endforeach; ?>
        </select>
		<?php
	}

	public function credit_widget_default_callback( array $args ): void {
		$id      = $args['id'];
		$checked = isset( $this->payu_settings_options[ $id ] ) && $this->payu_settings_options[ $id ] === 'yes' ? 'checked' : '';
		printf(
			'<input type="checkbox" name="payu_settings_option_name[%s]" id="%s" %s/>',
			$id, $id, $checked );
	}

	public function credit_widget_excluded_paytypes_callback(): void {
		$id          = 'credit_widget_excluded_paytypes';
		$value       = isset( $this->payu_settings_options[ $id ] ) ? esc_attr( implode( ',', $this->payu_settings_options[ $id ] ) ) : '';
		$description = __( 'Excludes the given credit payment methods from the credit payment widget. The value must be a comma-separated list of <a href="https://developers.payu.com/europe/docs/get-started/integration-overview/references/#installments-and-pay-later" target="_blank" rel="nofollow">credit payment method codes</a>, for example: dpt,dpkl,dpp.'
			, 'woo-payu-payment-gateway' );
		printf( '<input type="text" class="regular-text" value="%s" name="payu_settings_option_name[%s]" id="%s" />
                        <p class="description">%s</p>', $value, $id, $id, $description );
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
    public function after_canceled_payment_statuses(): array {
        $statuses  = wc_get_order_statuses();
        $available = [];
        foreach ( $statuses as $key => $value ) {
            if ( in_array( $key, [ 'wc-cancelled', 'wc-failed' ] ) ) {
                $available[ str_replace( 'wc-', '', $key ) ] = $value;
            }
        }
        ksort( $available );

        return $available;
    }

	private function sanitize_excluded_paytypes( array $excluded_paytypes ): array {
		return array_filter(
			array_map( 'sanitize_key',
				array_map( 'trim',
					array_map( 'sanitize_text_field', $excluded_paytypes )
				)
			)
		);
	}
}

if ( is_admin() ) {
	new PayuSettings();
}
