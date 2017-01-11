<?php

return array(
    'enabled' => array(
        'title'=> __('Enable:', 'payu'),
        'type' => 'checkbox',
        'label' => ' ',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title:', 'payu'),
        'type'=> 'text',
        'description' => __('Title of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU', 'payu'),
        'desc_tip' => true
    ),
    'pos_id' => array(
        'title' => __('Id point of sales:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'md5' => array(
        'title' => __('Second key (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'client_id' => array(
        'title' => __('OAuth - client_id:', 'payu'),
        'type' => 'text',
        'description' =>  __('Client Id for OAuth identifier  from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'client_secret' => array(
        'title' => __('OAuth - client_secret:', 'payu'),
        'type' => 'text',
        'description' =>  __('First key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description:', 'payu'),
        'type' => 'text',
        'description' => __('Description of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU is a leading payment services provider with presence in 16 growth markets across the world.', 'payu'),
        'desc_tip' => true
    ),
    'validity_time' => array(
        'title' => __('Validity time:', 'payu'),
        'type' => 'text',
        'description' =>  __('Time when paying for order is possible (in seconds).', 'payu'),
        'default' => '1440',
        'desc_tip' => true
    ),
    'payu_feedback' => array(
        'title'=> __('Automatic collection:', 'payu'),
        'type' => 'checkbox',
        'description' =>  __('Automatic collection makes it possible to automatically confirm incoming payments.', 'payu'),
        'label' => ' ',
        'default' => 'no',
        'desc_tip' => true
    ),
);