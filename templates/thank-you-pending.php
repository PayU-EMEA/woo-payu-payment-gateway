<?php
if ( ! isset( $url ) ) {
    throw new LogicException( '[$url] is not set.' );
}
?>
<script>
    window.payu_get_status_url = '<?php echo esc_js( $url );?>';
</script>
<section id="payu-payment-status">
    <h2 class="payu-payment-status-title">
        <img src="<?php echo WC_PAYU_PLUGIN_URL . 'assets/images/logo-payu.svg' ?>" alt="PayU logo" role="img"/>
        <?php esc_html_e( 'PayU payment', 'woo-payu-payment-gateway' ); ?>
    </h2>
    <div id="payu-payment-status-waiting">
        <h3><?php esc_html_e( 'Wait for the payment result', 'woo-payu-payment-gateway' ); ?>
        <div id="payu-preloader">
            <output aria-live="polite" aria-busy="true">
                <img src="<?php echo WC_PAYU_PLUGIN_URL . 'assets/images/spinner.svg' ?>" alt="..." role="img"/>
            </output>
        </div>
        </h3>
    </div>
    <div id="payu-payment-status-result"></div>
</section>
