<?php

return array(
    'enabled' => array(
        'title'=> __('Enable / Disable', 'payu'),
        'type' => 'checkbox',
        'label' => __('Enable PayU payment gateway', 'payu'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title:', 'payu'),
        'type'=> 'text',
        'description' => __('Tytuł, który widzi użytkownik podczas składania zamówienia.', 'payu'),
        'default' => __('PayU', 'payu'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description:', 'payu'),
        'type' => 'text',
        'description' => __('Opis, który widzi użytkownik podczas składania zamówienia.', 'payu'),
        'default' => __('PayU - płatności internetowe, szybkie przelewy przez Internet', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_pln' => array(
        'title' => __('[PLN] Id punktu płatności (pos_id):', 'payu'),
        'type' => 'text',
        'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'md5_pln' => array(
        'title' => __('[PLN] Drugi klucz (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_eur' => array(
        'title' => __('[EUR] Id punktu płatności (pos_id):', 'payu'),
        'type' => 'text',
        'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'md5_eur' => array(
        'title' => __('[EUR] Drugi klucz (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_usd' => array(
        'title' => __('[USD] Id punktu płatności (pos_id):', 'payu'),
        'type' => 'text',
        'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'md5_usd' => array(
        'title' => __('[USD] Drugi klucz (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'pos_id_gpb' => array(
        'title' => __('[GPB] Id punktu płatności (pos_id):', 'payu'),
        'type' => 'text',
        'description' => __('Wpisz tutaj identyfikator punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'md5_gpb' => array(
        'title' => __('[GPB] Drugi klucz (MD5):', 'payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj drugi klucz MD5 punktu płatności znajdujący się w sekcji KLUCZE KONFIGURACYJNE', 'payu'),
        'desc_tip' => true
    ),
    'validity_time' => array(
        'title' => __('Ważność zamówienia [s]:', 'payu'),
        'type' => 'text',
        'description' =>  __('Wpisz tutaj, czas (w sekundach) po jakim nieopłacone zamówienie powinno stracić ważność.', 'payu'),
        'default' => '1440',
        'desc_tip' => true
    ),
    'payu_feedback' => array(
        'title'=> __('Autoodbiór wyłączony', 'payu'),
        'type' => 'checkbox',
        'description' =>  __('Zaznacz tę opcję, jeśli chcesz, aby przy ręcznej zmianie statusu zamówienia na anulowane lub zakceptowane informować PayU, w celu odrzucenia lub przyjęcia płatności.', 'payu'),
        'label' => __('Włącz', 'payu'),
        'default' => 'no',
        'desc_tip' => true
    )
);