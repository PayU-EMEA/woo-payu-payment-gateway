<?php

class WC_Gateway_PayuListBanks extends WC_PayUGateways
{
    private $unset_banks = [];

    function __construct()
    {
        parent::__construct('payulistbanks');

        if ($this->is_enabled()) {
            $this->has_terms_checkbox = true;

            if (!is_admin()) {
                if (!$this->try_retrieve_banks()) {
                    add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
                }
            }
        }
    }

    function init_form_fields() {
        parent::payu_init_form_fields(true);
    }

    /**
     * @return bool
     */
    public function try_retrieve_banks()
    {
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $payMethods = $response->getResponse();

            return $payMethods->payByLinks;
        }

        return false;
    }

    /**
     * @return void
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        $response = $this->get_payu_response();
        if (isset($response) && $response->getStatus() === 'SUCCESS') {
            $this->retrieve_methods($response);
            $this->agreements_field();
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
        $custom_order = '';
        if (get_option('woocommerce_' . $this->id . '_settings')['custom_order']) {
            $custom_order = get_option('woocommerce_' . $this->id . '_settings')['custom_order'];
        }
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
                <?php if ($payMethods->payByLinks):
                    $payByLinks = $this->process_pay_methods($payMethods->payByLinks, $custom_order);
                    if ($payByLinks):
                        foreach ($payByLinks as $key => $value):
                            ?>
                            <li class="payu-bank payu-bank-<?php echo $key . ' ' . $value['active'] ?>"
                                title="<?php echo $value['name'] ?>">
                                <label>
                                    <input type="radio"
                                           value="<?php if ($value['active'] === 'payu-active') echo $key ?>"
                                           name="selected-bank"/>
                                    <div><img src="<?php echo $value['brandImageUrl']; ?>"></div>
                                </label>
                            </li>
                        <?php
                        endforeach;
                    endif;
                endif;
                ?>
            </ul>
            <ul class="pbl-error woocommerce-error" role="alert">
                <li><?php echo __('Choose payment method.', 'payu') ?></li>
            </ul>
        </div>

        <?php
    }

    /**
     * @param array $payMethods
     * @param string|null $sort
     *
     * @return array
     */
    function process_pay_methods($payMethods, $sort = null)
    {
        $result_methods = [];
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if ($available_gateways) {
            foreach ($available_gateways as $available_gateway => $data) {
                if ($available_gateway === 'payucreditcard' && $data->enabled === 'yes') {
                    array_push($this->unset_banks, 'c');
                }
                if ($available_gateway === 'payusecureform' && $data->enabled === 'yes') {
                    array_push($this->unset_banks, 'c');
                }
                if ($available_gateway === 'payublik' && $data->enabled === 'yes') {
                    array_push($this->unset_banks, 'blik');
                }
                if ($available_gateway === 'payuinstallments' && $data->enabled === 'yes') {
                    array_push($this->unset_banks, 'ai');
                }
            }
        }
        $show_inactive = @get_option('woocommerce_' . $this->id . '_settings')['show_inactive_methods'];
        foreach ($payMethods as $payMethod) {
            if (!in_array($payMethod->value, $this->unset_banks)) {
                if ($show_inactive === 'yes' && $payMethod->value != 't') {
                    $show_method = true;
                    if ($payMethod->status !== 'ENABLED') {
                        $show_method = false;
                    } else {
                        if (!$this->check_min_max($payMethod, $payMethod->value)) {
                            $show_method = false;
                        }
                    }
                    $result_methods[$payMethod->value] = [
                        'brandImageUrl' => $payMethod->brandImageUrl,
                        'name' => $payMethod->name,
                        'active' => $show_method ? 'payu-active' : 'payu-inactive'
                    ];
                } else {
                    if ($payMethod->status === 'ENABLED') {
                        $can_be_use = true;
                        if (!$this->check_min_max($payMethod, $payMethod->value)) {
                            $can_be_use = false;
                        }
                        if ($can_be_use) {
                            $result_methods[$payMethod->value] = [
                                'brandImageUrl' => $payMethod->brandImageUrl,
                                'name' => $payMethod->name,
                                'active' => 'payu-active'
                            ];
                        }
                    }
                }
            }
        }

        if (!$sort) {
            $first_paytypes = ['c','ap','jp','vc'];
            $last_paytypes = ['b', 'pt', 'bt'];
        } else {
            $first_paytypes = explode(',', str_replace(' ', '', $sort));
            $last_paytypes = [];
        }

        list($first, $result_methods) = $this->extract_paytypes($result_methods, $first_paytypes);
        list($last, $result_methods) = $this->extract_paytypes($result_methods, $last_paytypes);

        $result_methods = array_merge($first, $result_methods, $last);

        return $result_methods;
    }

    /**
     * @param $result_methods
     * @param $paytypes
     * @return array
     */
    private function extract_paytypes($result_methods, $paytypes)
    {
        $extracted = [];
        foreach ($paytypes as $item) {
            if (@$result_methods[$item]) {
                $extracted[$item] = [
                    'brandImageUrl' => $result_methods[$item]['brandImageUrl'],
                    'name' => $result_methods[$item]['name'],
                    'active' => $result_methods[$item]['active'],
                ];
                unset($result_methods[$item]);
            }
        }

        return [$extracted, $result_methods];
    }


    /**
     * @return array
     */
    protected function get_payu_pay_method()
    {
        $selected_method = filter_var($_POST['selected-bank'], FILTER_SANITIZE_STRING);

        return $this->get_payu_pay_method_array('PBL', $selected_method ? $selected_method : -1, $selected_method);
    }
}
