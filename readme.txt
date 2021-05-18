=== WooCommerce PayU EU Payment Gateway ===
Contributors: payusa
Tags: woocommerce, PayU, payment, payment gateway, platnosci, PayU Poland, PayU EU
Requires at least: 4.4
Tested up to: 5.7
Stable tag: 2.0.3
Requires PHP: 7.0
License: GPLv2

== Description ==
**PayU payment module for WooCommerce**

The plugin offers the following payment methods:

* PayU - standard - payer will be redirected to PayU's hosted payment page where any available payment type configured on your POS can be chosen
* PayU - bank list - payment type list will be displayed, depending on chosen type the payer will be either redirected directly to the bank or to PayU's hosted payment page
* PayU - payment card - payer will be redirected to PayU's hosted card form where credit, debit or prepaid card data can be securely entered
* PayU - secure form - a secure form collecting credit, debit or prepaid card data will be displayed
* PayU - Blik - payer will be redirected to Blik's page
* PayU - installments - payer will be redirected to installment payment form

Detailed information about each method and its configuration [can be found here](https://github.com/PayU-EMEA/plugin_woocommerce).

== Installation ==
If you have any questions or would like to raise an issue please contact [our technical support](https://www.payu.pl/pomoc).

= Minimum Requirements =
PayU merchant account - if you do not have an account you can [**register a production account**](https://poland.payu.com/en/how-to-activate-payu/) or [**register a sandbox account**](https://registration-merch-prod.snd.payu.com/boarding/#/registerSandbox/?lang=en)

**Note:** Module works only with `REST API` POS type (POS type is configured in PayU merchant panel after you register).

Following PHP libraries are required: cURL i hash.

= Automatic installation =
Use [automatic installation and activation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation) available in Wordpress admin panel. Module name is `WooCommerce PayU EU Payment Gateway`.

= Updating =
Upon plugin update from version 1.X to version 2.X the existing config data will be automatically converted.

== Changelog ==
= 2.0.3 =
* Better js and css enqueue and optimize load methods
* Better UX for bank icons
= 2.0.2 =
* Fix #31 js loading dependency
* Fix #30 remove 100% layout width
* Fix #27 fix registration url
= 2.0.1 =
* Fix change order status for virtual products
= 2.0.0 =
* New version introducing many additional features e.g. many payment types, enhanced configuration options, order repayment.
= 1.3.1 =
* Fix WPML compatibility
= 1.3.0 =
* Add shipping method selector
= 1.2.9 =
* Fix error when Multi-currency support is disabled in WPML
= 1.2.8 =
* Moved lang to buyer
* Fixed notice in notification
= 1.2.7 =
* Added support for multicurrency provided by WPML
= 1.2.6 =
* Sandbox added
* Cleanup code
= 1.2.5 =
* PayU SDK update
= 1.2.4 =
* Fix calculate price in products
* Update SDK
= 1.2.3 =
* Fix for WooCommerce 3.x
= 1.2.2 =
* added language to redirect URI
* added e-mail notification
* added stock reduce
= 1.2.1 =
* Fixed extOrderId when other plugin changes WooCommerce order number
= 1.2.0 =
* Add Oauth support
= 1.1.1 =
* fix notifications
= 1.1.0 =
* remove many pos config for currency
