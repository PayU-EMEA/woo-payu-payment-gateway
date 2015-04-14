# PayU plugin for Wordpress WooCommerce
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

**Note:** This is an alpha release and we are still working on plugin improvements.

## Table of Contents


<!--topic urls:-->

[Features](#features)

[Prerequisites](#prerequisites)

[Installation](#installation)
 
[Installing Manually](#installing-manually)

[Installing from admin page](#installing-from-the-administration-panel)

[Configuration](#configuration)

## Features
The PayU payments WooCommerce plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order
* Updating order status (cancell/ complete order in case of autoreceive off option enabled)
* Conducting a refund operation (for a whole or partial order)


## Prerequisites

**Important:** This plugin works only with checkout points of sales (POS).

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.

## Installation

<!--There are two ways in which you can install the plugin:

* [manual installation](#installing-manually) by copying and pasting folders from the repository
* [installation from the admin panel](#installing-from-admin-panel)

See the sections below to find out about steps for each of the procedures.-->

### Installing Manually

To install the plugin, copy folders from the repository and activate the plugin in the admin panel:

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_woocommerce) to local directory as zip.
2. Unzip locally downloaded file
3. Go to the Wordpress administration panel
4. Choose Plugins section from the Wordpress menu
5. Under PayU - Payment Gateway click Activate
6. [Activate](#plugin-activation) PayU payment gateway in WooCommerce


### Installing from the administration panel

WooCommerce allows you to install the plugin from the administration page:

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_woocommerce) to local directory as zip.
2. Go to the Wordpress administration panel
3. Choose Plugins section from the Wordpress menu
4. Click Add New option next to the Plugins section title
5. Click Upload Plugin option next to the Add Plugins section title
6. Select uploaded plugin .zip file from disk and upload plugin
7. Click Plugins in the Wordpress menu to go to the plugins section once again 
8. Under PayU - Payment Gateway click Activate
9. [Activate](#plugin-activation-in-woocommerce) PayU payment gateway in WooCommerce

### Plugin activation in WooCommerce

PayU Gateway have to be activated in the WooCommerce plugin.

In the Wordpress administration panel:
1. Go to WooCommerce -> Settings section
2. Choose Checkout tab and scroll down to the "Payment Gateways" section
3. Choose Settings option next to the PayU payment gateway
4. Configure ([Configuration](#configuration)) and enable PayU plugin


## Configuration

To configure the PayU Payment Gateway WooCommerce plugin.

In the PayU Payment Gateway Settings panel:
 * Enter the name of the PayU Payment Gateway in the *Title* field
 * Enter the description for the PayU Payment gateway for your clients in the *Description* input field
 * Enter your **pos id** in the *pos_id* input field (you will find it in your PayU Management panel)
 * Enter your **second key** in the *second key (MD5)* input field
 * Define the order validity time in the *validity time* input field in **seconds**. It defines the time interval in which the payment is not payed by the client untill it's cancelled.
 * *Autoreceive off*  - check this option ff you defined that you want to have autoreceive turned off in your PayU Management panel. Otherwise leave this checkbox not checked.


