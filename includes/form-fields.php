<?php

return array(
    'enabled' => array(
        'title'=> __('Enable / Disable', 'bpmj-woocommerce-payu'),
        'type' => 'checkbox',
        'label' => __('Enable PayU payment gateway', 'bpmj-woocommerce-payu'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title:', 'bpmj-woocommerce-payu'),
        'type'=> 'text',
        'description' => __('Tytuł, który widzi użytkownik podczas składania zamówienia.', 'bpmj-woocommerce-payu'),
        'default' => __('PayU', 'bpmj-woocommerce-payu'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description:', 'bpmj-woocommerce-payu'),
        'type' => 'text',
        'description' => __('Opis, który widzi użytkownik podczas składania zamówienia.', 'bpmj-woocommerce-payu'),
        'default' => __('PayU - płatności internetowe, szybkie przelewy przez Internet', 'bpmj-woocommerce-payu'),
        'desc_tip' => true
    ),
    'pos_id_' . $this->currency_slug => array(
        'title' => __('Id punktu płatności (pos_id):', 'bpmj-woocommerce-payu'),
        'type' => 'text',
        'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE"'),
        'desc_tip' => true
    ),
    'md5_' . $this->currency_slug => array(
        'title' => __('Drugi klucz (MD5):', 'bpmj-woocommerce-payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'bpmj-woocommerce-payu'),
        'desc_tip' => true
    ),
    'validity_time' => array(
        'title' => __('Ważność zamówienia [s]:', 'bpmj-woocommerce-payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj, czas (w sekundach) po jakim nieopłacone zamówienie powinno stracić ważność.', 'bpmj-woocommerce-payu'),
        'default' => '',
        'desc_tip' => true
    ),
    'payu_feedback' => array(
        'title'=> __('Wysyłaj statusy do PayU', 'bpmj-woocommerce-payu'),
        'type' => 'checkbox',
        'description' =>  __('Zaznacz tę opcję, jeśli chcesz, aby przy ręcznej zmianie statusu zamówienia na anulowane lub zakceptowane informować PayU, w celu odrzucenia lub przyjęcia płatności.', 'bpmj-woocommerce-payu'),
        'label' => __('Włącz', 'bpmj-woocommerce-payu'),
        'default' => 'no',
        'desc_tip' => true
    )
);