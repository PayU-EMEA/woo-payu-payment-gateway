=== PayU EU Payment Gateway for WooCommerce ===
Contributors: payusa
Tags: PayU, payment, payment gateway, platnosci, PayU Poland, PayU EU
Requires at least: 4.4
Tested up to: 6.2.2
Stable tag: 2.0.29
Requires PHP: 7.0
License: Apache License 2.0

== Description ==
**PayU payment module for WooCommerce**

The plugin offers the following payment methods:

* PayU - standard - payer will be redirected to PayU's hosted payment page where any available payment type configured on your POS can be chosen
* PayU - bank list - payment type list will be displayed, depending on chosen type the payer will be either redirected directly to the bank or to PayU's hosted payment page
* PayU - payment card - payer will be redirected to PayU's hosted card form where credit, debit or prepaid card data can be securely entered
* PayU - secure form - a secure form collecting credit, debit or prepaid card data will be displayed
* PayU - Blik - payer will be redirected to Blik's page
* PayU - installments - payer will be redirected to installment payment form
* PayU - PayPo - payer will be redirected to PayPo payment form
* PayU - Twisto - payer will be redirected to Twisto payment form

Detailed information about each method and its configuration [can be found here](https://github.com/PayU-EMEA/woo-payu-payment-gateway).

== Installation ==
If you have any questions or would like to raise an issue please contact [our technical support](https://www.payu.pl/pomoc).

= Minimum Requirements =
PayU merchant account - if you do not have an account you can [**register a production account**](https://poland.payu.com/en/how-to-activate-payu/) or [**register a sandbox account**](https://registration-merch-prod.snd.payu.com/boarding/#/registerSandbox/?lang=en)

**Note:** Module works only with `REST API` POS type (POS type is configured in PayU merchant panel after you register).

Following PHP libraries are required: cURL i hash.

= Automatic installation =
Use [automatic installation and activation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation) available in Wordpress admin panel. Module name is `PayU EU Payment Gateway for WooCommerce`.

= Updating =
Upon plugin update from version 1.X to version 2.X the existing config data will be automatically converted.

== Frequently Asked Questions ==

= Does this load external javascript resources ? =

Yes, it does.
* For card payment we used [PayU Secure Form](https://developers.payu.com/en/card_tokenization.html#secureform) and for proper working it is necessary to load Secure Form JS SDK from the secure.payu.com domain. As a result, you do not need to have PCI DSS, PayU does it for you.
* For presenting minimal installment amount we used [Widget Installments](https://developers.payu.com/en/installments.html#installments_best_practices_mini) and plugin loads the script from the static.payu.com domain.

== Changelog ==
= 2.0.29 =
* Decimal quantity in products
= 2.0.28 =
* Fixed installment widget to use taxed product price
* Fixed installment widget not refreshing after product quantity changes in cart details
* Fixed refund request
= 2.0.27 =
*  Fixed for decimal quantity
= 2.0.26 =
* Better use of checkout_place_order hook
= 2.0.23 - 2.0.25 =
* Added compatibility with HPOS (High-Performance Order Storage)
* Sends more data for better detected frauds
= 2.0.22 =
* Fixed getTotal
= 2.0.21 =
* Fixed installment payment method name in order summary page
= 2.0.20 =
* Fixed installment payment method name in emails
= 2.0.19 =
* Added installment widget integration
* PayU address update
= 2.0.18 =
* Fixed link to repay in email
* The shorter product name
* Changed plugin url on github
= 2.0.14 - 2.0.17 =
* Changes required by wordpress plugin teams (plugin name, change translate domain, sanitize variables, remove external js, update readme)
= 2.0.13 =
* Fix method available for virtual products
* Separate Twisto and PayPo
= 2.0.12 =
* Fix calculate total for check min/max
* Possibility to enable for virtual orders
* Fix #43 php notice
* Update Visa logo
= 2.0.11 =
* Remove manual stock reduced
* Remove unused REJECTED
* Add filter woocommerce_payu_status_cancelled
= 2.0.10 =
* PayU terms - info instead of checkbox
* Better check shipping method, clean up
= 2.0.9 =
* Fix not show installments, blik, card as separated method
= 2.0.8 =
* Send mail to customer when status change from payu-waiting to processing
= 2.0.7 =
* Fix show paymethods when WPML is active
= 2.0.6 =
* Fix hide inactive method
= 2.0.5 =
* Translate fix
* Add filters for multicurrency support
* Notice fix
= 2.0.4 =
* Fix size gateway logotypes in some themes
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
