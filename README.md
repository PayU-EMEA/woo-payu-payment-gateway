# PayU plugin for Wordpress WooCommerce
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our technical support: tech@payu.pl.**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

**Important:** This plugin works only with **checkout points of sales (POS)**.

**Note:** This is an alpha release and we are still working on plugin improvements.

## Table of Contents


<!--topic urls:-->

[Features](#features)

[Prerequisites](#prerequisites)

[Installation](#installation)
 
[Installing Manually](#installing-manually)

[Installing from admin page](#installing-from-the-administration-panel)

[Configuration](#configuration)

[Usage](#usage)

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

There are two ways in which you can install the plugin:

* [Manual installation](#installing-manually) by copying and pasting folders from the repository
* [Installing from the administration panel](#installing-from-the-administration-panel)

See the sections below to find out about steps for each of the procedures.

### Manual installation

To install the plugin, copy folders from the repository and activate the plugin in the admin panel:

1. Download plugin from [the plugin repository](https://github.com/PayU/plugin_woocommerce) to local directory as zip.
2. Unzip locally downloaded file
3. Go to the Wordpress administration panel
4. Choose Plugins section from the Wordpress menu
5. Under PayU - Payment Gateway click Activate
6. [Activate](#plugin-activation) PayU Payment Gateway in WooCommerce


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
9. [Activate](#plugin-activation-in-woocommerce) PayU Payment Gateway in WooCommerce

### Plugin activation in WooCommerce

PayU Payment Gateway have to be activated in the WooCommerce plugin.

In the Wordpress administration panel:

1. Go to WooCommerce -> Settings section
2. Choose Checkout tab and scroll down to the "Payment Gateways" section
3. Choose Settings option next to the PayU payment gateway
4. Configure ([Configuration](#configuration)) and enable PayU plugin


## Configuration

To configure the PayU Payment Gateway for WooCommerce plugin go to the PayU Payment Gateway Settings panel.

### Required:

 * **pos id** input field - you will find it in your PayU *Management panel*
 * **second key (MD5)** input field - you will find it in your PayU *Management panel*
 
### Optional (you can leave default values):

 * *Title* - the name of the PayU Payment Gateway visible to your clients
 * *Description* - description for the PayU Payment gateway visible for your clients
 * **validity time** - defines the time interval in which the payment is not payed by the client untill it's cancelled (in **seconds**).
 * **Autoreceive off**  - check this option ff you defined that you want to have autoreceive turned off in your PayU Management panel. Otherwise leave this checkbox not checked.
 
## Usage:

PayU Payment Gateway is visible for you customers as a single "Pay with PayU" button during checkout. After clicking the button customer is moved to the Payment Summary page to choose payment method. After successfull payment customer is redirected back to your shop. 


