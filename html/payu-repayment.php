<?php $pay_now_url = wc_get_endpoint_url('order-pay', $order->get_id(),
        wc_get_checkout_url()) . '?pay_for_order=true&key=' . $order->get_order_key(); ?>

<a href="<?php echo $pay_now_url ?>" class="autonomy-payu-button"><?php echo __('Pay with', 'payu'); ?> <img
            src="<?php echo plugin_dir_url(__FILE__) . '../assets/images/logo-payu.svg' ?>"/></a>
