<?php
/**
 * Plugin Name: PayU Payment Gateway
 * Plugin URI: https://github.com/PayU/plugin_woocommerce
 * Description: PayU payment gateway for WooCommerce
 * Version: 1.1.1
 * Author: PayU SA
 * Author URI: http://www.payu.com
 * License: LGPL 3.0
 * Text Domain: payu
 * Domain Path: /lang
 */

add_action('plugins_loaded', 'woocommerce_payu_init', 0);

add_action( 'wp_enqueue_scripts', 'add_theme_scripts' );

function woocommerce_payu_init() {
    if (!class_exists('WC_Payment_Gateway')) return;

    load_plugin_textdomain( 'payu', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

    include_once('includes/class-woocommerce-payu.php');

    add_filter('woocommerce_payment_gateways', 'woocommerce_payu_add_gateway');
}

function woocommerce_payu_add_gateway($methods) {
    $methods[] = 'WC_Gateway_PayU';

    return $methods;
}

function add_theme_scripts()
{

    wp_enqueue_style('PayUMethodsCss', plugin_dir_url( __FILE__ ).'assets/css/PayUMethodsCss.css',array(), '1.1', 'all');

    wp_enqueue_script('PayUMethodsJs',  plugin_dir_url( __FILE__ ).'assets/js/PayUMethodsJs.js');
}
