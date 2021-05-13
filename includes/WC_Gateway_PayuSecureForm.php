<?php

class WC_Gateway_PayuSecureForm extends WC_PayUGateways
{
    protected $paytype = 'c';

    function __construct()
    {
        parent::__construct('payusecureform');

        if ($this->is_enabled()) {
            $this->has_terms_checkbox = true;
            $this->icon = apply_filters('woocommerce_payu_icon', plugins_url( '/assets/images/card-visa-mc.svg', PAYU_PLUGIN_FILE ));

            add_action('wp_enqueue_scripts', [$this, 'include_payu_sf_scripts']);

            //refresh card iframe after checkout change
            if (!is_admin()) {
                add_action('wp_footer', [$this, 'minicart_checkout_refresh_script']);
                if (!$this->try_retrieve_banks()) {
                    add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
                }
            }
        }
    }

    /**
     * @return null
     */
    function minicart_checkout_refresh_script()
    {
        if (is_checkout() || is_wc_endpoint_url()) :
            ?>
            <script type="text/javascript">
                (function ($) {
                    $(document.body).on('change', '#shipping_method input', function () {
                        $(document.body).trigger('update_checkout').trigger('wc_fragment_refresh');
                        sf_init();
                    });
                    $(document).ready(function(){
                        if($('form#order_review').length > 0) {
                            sf_init();
                        }
                    });
                })(jQuery);

            </script>
        <?php
        endif;
    }

    /**
     * @return bool
     */
    public function try_retrieve_banks()
    {
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $payMethods = $response->getResponse();

            return $payMethods->payByLinks && $this->process_pay_methods($payMethods->payByLinks);
        }

        return false;
    }

    /**
     * @return null
     */
    public function payment_fields()
    {
        $this->init_OpenPayU();
        if ($this->description) {
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $this->retrieve_methods($response);
            $this->agreements_field();
            echo '<script>try{sf_init();}catch(e){}</script>';
        }
    }

    /**
     * @param OpenPayU_Result $response
     *
     * @return null
     */
    function retrieve_methods($response)
    {
        $payMethods = $response->getResponse();
        if ($payMethods->payByLinks) {
            $payByLinks = $this->process_pay_methods($payMethods->payByLinks);
            if ($payByLinks) {
                ?>
                <div class="card-container" id="payu-card-container" data-payu-posid="<?php echo $this->pos_id ?>"
                     data-lang="<?php echo explode('_', get_locale())[0] ?>">
                    <div class="payu-sf-technical-error"
                         data-type="technical"><?php echo __('The card could not be sent', 'payu') ?></div>
                    <label for="payu-card-number"><?php echo __('Card number', 'payu') ?></label>
                    <div class="payu-card-form" id="payu-card-number"></div>
                    <div class="payu-sf-validation-error" data-type="number"></div>

                    <div class="card-details clearfix">
                        <div class="expiration">
                            <label for="payu-card-date"><?php echo __('Expire date', 'payu') ?></label>
                            <div class="payu-card-form" id="payu-card-date"></div>
                            <div class="payu-sf-validation-error" data-type="date"></div>
                        </div>

                        <div class="cvv">
                            <label for="payu-card-cvv"><?php echo __('CVV', 'payu') ?></label>
                            <div class="payu-card-form" id="payu-card-cvv"></div>
                            <div class="payu-sf-validation-error" data-type="cvv"></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="response-tokenize" id="response-tokenize"/>
                <?php
            }
        }
    }

    /**
     * @param array $payMethods
     *
     * @return bool
     */
    function process_pay_methods($payMethods)
    {
        foreach ($payMethods as $payMethod) {
            if (!$this->check_min_max($payMethod, $this->paytype)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    protected function get_payu_pay_method()
    {
        $token = filter_var($_POST['response-tokenize'], FILTER_SANITIZE_STRING);

        return $this->get_payu_pay_method_array('CARD_TOKEN', $token ? $token : -1, $this->paytype);
    }

    /**
     * @return void
     */
    public function include_payu_sf_scripts()
    {
        $payu_sdk_url = $this->sandbox==='yes'?'https://secure.snd.payu.com/javascript/sdk':'https://secure.payu.com/javascript/sdk';
        wp_enqueue_script('payu-sfsdk', $payu_sdk_url, [], null);
        wp_enqueue_script('payu-promise-polyfill', 'https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js', [], null);
        wp_enqueue_script('payu-sf-init', plugins_url( '/assets/js/sf-init.js', PAYU_PLUGIN_FILE ), [], PAYU_PLUGIN_VERSION,
            true);
    }
}
