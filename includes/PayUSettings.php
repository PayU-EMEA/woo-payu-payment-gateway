<?php

class PayUSettings
{
    private $payu_settings_options;
    private $fields;

    public function __construct()
    {
        $this->fields = $this->payu_fields();
        add_action('admin_menu', [$this, 'payu_settings_add_plugin_page']);
        add_action('admin_init', [$this, 'payu_settings_page_init']);

    }

    /**
     * @return array
     */
    public static function payu_fields()
    {
        return [
            'pos_id' => [
                'label' => __('Id point of sales', 'payu'),
                'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.',
                    'payu')
            ],
            'md5' => [
                'label' => __('Second key (MD5)', 'payu'),
                'description' => __('Second key from "Configuration Keys" section of PayU management panel.', 'payu')
            ],
            'client_id' => [
                'label' => __('OAuth - client_id', 'payu'),
                'description' => __('Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.',
                    'payu')
            ],
            'client_secret' => [
                'label' => __('OAuth - client_secret', 'payu'),
                'description' => __('First key from "Configuration Keys" section of PayU management panel.', 'payu'),
            ],
            'sandbox_pos_id' => [
                'label' => __('Sandbox - Id point of sales', 'payu'),
                'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.',
                    'payu'),
            ],
            'sandbox_md5' => [
                'label' => __('Sandbox - Second key (MD5):', 'payu'),
                'description' => __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
            ],
            'sandbox_client_id' => [
                'label' => __('Sandbox - OAuth - client_id:', 'payu'),
                'description' => __('Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.',
                    'payu'),
            ],
            'sandbox_client_secret' => [
                'label' => __('Sandbox - OAuth - client_secret:', 'payu'),
                'description' => __('First key from "Configuration Keys" section of PayU management panel.', 'payu'),
            ],
        ];
    }

    /**
     * @return null
     */
    public function payu_settings_add_plugin_page()
    {
        add_submenu_page(
            'woocommerce',
            __('PayU settings', 'payu'), // page_title
            __('PayU settings', 'payu'), // menu_title
            'manage_options', // capability
            'payu-settings', // menu_slug
            [$this, 'payu_settings_create_admin_page'], // function
            100
        );
    }

    /**
     * @return void
     */
    public function payu_settings_create_admin_page()
    {
        $this->payu_settings_options = get_option('payu_settings_option_name'); ?>

        <div class="wrap">
            <h2><?php echo __('PayU settings', 'payu') ?></h2>
            <p></p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('payu_settings_option_group');
                do_settings_sections('payu-settings-admin');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * @return void
     */
    public function payu_settings_page_init()
    {
        global $woocommerce_wpml;
        register_setting(
            'payu_settings_option_group', // option_group
            'payu_settings_option_name', // option_name
            [$this, 'payu_settings_sanitize'] // sanitize_callback
        );

        //global
        add_settings_section(
            'payu_settings_setting_section', // id
            __('PayU config global', 'payu'), // title
            [], // callback
            'payu-settings-admin' // page
        );
        $currencies = [''];
        if (is_wmpl_active_and_configure()) {
            $currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
        }
        foreach ($currencies as $code) {
            $idSuffix = ($code ? '_' : '') . $code;
            $namePrefix = $code . ($code ? ' - ' : '');
            foreach ($this->fields as $field => $desc) {
                $args = [
                    'id' => 'global_' . $field . $idSuffix,
                    'desc' => $namePrefix . $desc['label'],
                    'name' => 'payu_settings_option_name'
                ];
                add_settings_field(
                    $args['id'], // id
                    $args['desc'], // title
                    [$this, 'global_callback'], // callback
                    'payu-settings-admin', // page
                    'payu_settings_setting_section',
                    $args
                );
            }
        }
        add_settings_field(
            'global_default_on_hold_status', // id
            __('Default on-hold status', 'payu'), // title
            [$this, 'global_default_on_hold_status_callback'], // callback
            'payu-settings-admin', // page
            'payu_settings_setting_section' // section
        );
        add_settings_field(
            'global_repayment', // id
            __('Enable repayment', 'payu'), // title
            [$this, 'global_repayment_callback'], // callback
            'payu-settings-admin', // page
            'payu_settings_setting_section' // section
        );
    }

    /**
     * @param array $args
     * @return void
     */
    public function global_callback($args)
    {
        $id = $args['id'];
        $value = isset($this->payu_settings_options[$id]) ? esc_attr($this->payu_settings_options[$id]) : '';
        printf('<input type="text" class="regular-text" value="%s" name="payu_settings_option_name[%s]" id="%s" />',
            $value, $id, $id);
    }

    /**
     * @param array $input
     * @return array
     */
    public function payu_settings_sanitize($input)
    {
        global $woocommerce_wpml;
        $sanitary_values = [];
        $currencies = [];
        if (is_wmpl_active_and_configure()) {
            $currencies = $woocommerce_wpml->multi_currency->get_currency_codes();
        }
        if (count($currencies) < 2) {
            $currencies = [''];
        }
        foreach ($currencies as $code) {
            $idSuffix = ($code ? '_' : '') . $code;
            foreach ($this->fields as $field => $desc) {
                $field = $field . $idSuffix;
                if (isset($input['global_' . $field])) {
                    $sanitary_values['global_' . $field] = sanitize_text_field($input['global_' . $field]);
                }
            }
        }

        if (isset($input['global_default_on_hold_status'])) {
            $sanitary_values['global_default_on_hold_status'] = sanitize_text_field($input['global_default_on_hold_status']);
        }

        if (isset($input['global_repayment'])) {
            $sanitary_values['global_repayment'] = sanitize_text_field($input['global_repayment']);
        }

        return $sanitary_values;
    }

    /**
     * @return null
     */
    public function global_repayment_callback()
    {
        printf(
            '<input type="checkbox" name="payu_settings_option_name[global_repayment]" id="global_repayment" value="global_repayment" %s>',
            (isset($this->payu_settings_options['global_repayment']) && $this->payu_settings_options['global_repayment'] === 'global_repayment') ? 'checked' : ''
        );
        ?>
        <span class="description payu-red">
            <span class="dashicons dashicons-warning"></span>
            <?php echo __('Before enabling repayment, read <a target="_blank" href="https://github.com/PayU-EMEA/plugin_woocommerce#ponawianie-p%C5%82atno%C5%9Bci">the documentation</a> and disable <strong>automatic collection</strong> in POS configuration.', 'payu'); ?>
        </span>
        <?php
    }

    /**
     * @return null
     */
    public function global_default_on_hold_status_callback()
    {
        ?>
        <select class="regular-text" type="text" name="payu_settings_option_name[global_default_on_hold_status]"
                id="global_default_on_hold_status">
            <?php foreach ($this->before_payment_statuses() as $key => $value): ?>
                <option <?php if (@$this->payu_settings_options['global_default_on_hold_status'] === $key) echo 'selected="selected"' ?>
                        value="<?php echo $key ?>"><?php echo $value ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @return array
     */
    public function before_payment_statuses()
    {
        $statuses = wc_get_order_statuses();
        $available = [];
        foreach ($statuses as $key => $value) {
            if (in_array($key, ['wc-pending', 'wc-on-hold'])) {
                $available[str_replace('wc-', '', $key)] = $value;
            }
        }
        ksort($available);
        return $available;
    }
}

if (is_admin()) {
    $payu_settings = new PayUSettings();
}
