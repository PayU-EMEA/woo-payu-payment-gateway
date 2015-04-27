<?php

return array(
    'enabled' => array(
        'title'=> __('Enable', 'payu'),
        'type' => 'checkbox',
        'label' => __(' ', 'payu'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title:', 'payu'),
        'type'=> 'text',
        'description' => __('Title of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU', 'payu'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description:', 'payu'),
        'type' => 'text',
        'description' => __('Description of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU is a leading payment services provider with presence in 16 growth markets across the world.', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_pln' => array(
        'title' => __('[PLN] POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'md5_pln' => array(
        'title' => __('[PLN] Second key (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_eur' => array(
        'title' => __('[EUR] POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'md5_eur' => array(
        'title' => __('[EUR] Second key (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_usd' => array(
        'title' => __('[USD] POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'md5_usd' => array(
        'title' => __('[USD] Second key (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_gpb' => array(
        'title' => __('[GPB] POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'md5_gpb' => array(
        'title' => __('[GPB] Second key (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
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
        'title'=> __('Automatic collection', 'payu'),
        'type' => 'checkbox',
        'description' =>  __('Automatic collection makes it possible to automatically confirm incoming payments.', 'payu'),
        'label' => __(' ', 'payu'),
        'default' => 'no',
        'desc_tip' => true
    )
);