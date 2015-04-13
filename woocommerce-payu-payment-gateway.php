<?php
/*
Plugin Name: PayU - WooCommerce Gateway
Plugin URI: http://payu.pl
Description: Bramka płatności PayU dla WooCommerce.
Version: 0.9
Author: Michał Jaworski (upSell.pl)
Author URI: http://upsell.pl
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
