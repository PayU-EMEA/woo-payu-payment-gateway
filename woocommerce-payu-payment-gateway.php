<?php
/**
 * Plugin Name: PayU Payment Gateway
 * Plugin URI: https://github.com/PayU/plugin_woocommerce
 * Description: PayU payment gateway for WooCommerce
 * Version: 1.0.0
 * Author: PayU SA
 * Copyright (c) 2015 PayU
 * License: LGPL 3.0
 */

add_action( 'plugins_loaded', 'bpmj_woocommerce_payu_init', 0 );
function bpmj_woocommerce_payu_init()
{
    // Sprawdzenie, czy wtyczka WooCommerce jest aktywowana
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    // Wczytanie klasy odpowiedzialnej za obsługę płatności
    include_once( 'includes/class-woocommerce-payu.php' );

    // Dodanie bramki do WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'bpmj_woocommerce_payu_gateway' );
    function bpmj_woocommerce_payu_gateway( $methods ) {
        $methods[] = 'BPMJ_WooCommerce_PayU';
        return $methods;
    }

    new BPMJ_WooCommerce_PayU();
}

?>
