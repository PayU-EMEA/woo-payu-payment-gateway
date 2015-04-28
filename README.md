# PayU plugin for Wordpress WooCommerce
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

**Important:** This plugin works only with **checkout points of sales (POS)**.

## Features
The PayU Payment Gateway for WooCommerce plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order
* Updating order status (cancell/ complete order in case of autoreceive off option enabled)
* Conducting a refund operation (for a whole or partial order)


## Prerequisites

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.

## Installation

[Install](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins) and activate the plugin like any other plugin in Wordpress.

In the Wordpress administration panel:

1. Go to **WooCommerce** -> **Settings** section
2. Choose **Checkout** tab and scroll down to the **"Payment Gateways"** section
3. Choose **Settings** option next to the **PayU** name
4. Enable and configure the plugin

## Usage:

PayU Payment Gateway is visible for you customers as a single "Pay with PayU" button during checkout. After clicking the button customer is moved to the Payment Summary page to choose payment method. After successfull payment customer is redirected back to your shop. 


