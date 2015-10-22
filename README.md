# PayU Payment Gateway for WooCommerce
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

**Important:** This plugin works only with **REST API (checkout) points of sales (POS)**.

**Important:** Currently supported currencies are: **PLN**, **EUR**, **USD**, **GPB**.

## Features
The PayU Payment Gateway for WooCommerce adds the PayU payment option and enables you to process the following operations in your shop:

* Creating a payment order
* Updating order status (canceling/completing an order will simultaneously update payment's status)
* Conducting a refund operation (for a whole or partial order)

## Prerequisites

* WooCommerce v2.2 or higher

## Installation

[Install](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins) and activate the plugin like any other plugin in Wordpress.

In the Wordpress administration panel:

1. Go to **WooCommerce** -> **Settings** section
2. Choose **Checkout** tab and scroll down to the **"Payment Gateways"** section
3. Choose **Settings** option next to the **PayU** name
4. Enable and configure the plugin

## Usage

PayU Payment Gateway is visible for your customers as a single "Pay with PayU" button during checkout.
After clicking the button customer is redirected to the Payment Summary page to choose payment method.
After successful payment customer is redirected back to your shop.