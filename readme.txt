﻿=== PayU GPO Payment for WooCommerce ===
Contributors: payusa
Tags: PayU, payment, payment gateway, płatności, credit card
Requires at least: 5.0
Tested up to: 6.8.1
Stable tag: 2.7.2
Requires PHP: 7.4
License: Apache License 2.0

PayU fast online payments for WooCommerce. Banks, BLIK, credit or debit cards, Installments, Apple Pay, Google Pay.

== Description ==
**PayU payment module for WooCommerce**

The plugin offers the following payment methods:

* PayU - standard - payer will be redirected to PayU's hosted payment page where any available payment type configured on your POS can be chosen
* PayU - bank list - payment type list will be displayed, depending on chosen type the payer will be either redirected directly to the bank or to PayU's hosted payment page
* PayU - payment card - payer will be redirected to PayU's hosted card form where credit, debit or prepaid card data can be securely entered
* PayU - secure form - a secure form collecting credit, debit or prepaid card data will be displayed
* PayU - Blik - payer will be redirected to Blik's page
* PayU - installments - payer will be redirected to installment payment form
* PayU - Klarna - payer will be redirected to Klarna payment form
* PayU - PayPo - payer will be redirected to PayPo payment form
* PayU - Twisto - payer will be redirected to Twisto payment form
* PayU - Twisto pay in 3 - payer will be redirected to Twisto pay in 3 payment form

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
* For presenting credit payment options like minimal installment amount or "buy now pay later" we used [Credit Widget](https://developers.payu.com/europe/docs/payment-solutions/credit/installments/#credit-widget-installments) and plugin loads the script from the static.payu.com domain.

== Changelog ==
= 2.7.2 - 2025-07-09 =
* [Fix] Limit widget script load

= 2.7.1 - 2025-06-23 =
* [Fix] Add missing check for payByLinks array in PayU Installments

= 2.7.0 - 2025-06-11 =
* [Add] Twisto Slice as separate method
* [Add] Support more currencies in buy now pay later payments
* [Update] New credit widget implementation, showing widget on Blocks cart and checkout
* [Update] Autofocus next field in Secure Form
* [Update] The order number is at the beginning of the description in PayU

= 2.6.1 - 2024-09-26 =
* [Fix] Not be accessed before initialization

= 2.6.0 - 2024-09-21 =
* [Add] PayU - secure form via WooCommerce Blocks

= 2.5.0 - 2024-07-23 =
* [Add] PayU - bank list via WooCommerce Blocks

= 2.4.1 - 2024-07-16 =
* [Fix] #73 - Not send e-mail to administrator about a new order
* [Fix] Showing error in empty cart (blocks) when installments are active

= 2.4.0 - 2024-07-07 =
* [Add] PayU - BLIK via WooCommerce Blocks

= 2.3.1 - 2024-06-24 =
* [Fix] "Call to a member function get_total() on null" for Installments

= 2.3.0 - 2024-06-21 =
* [Add] PayU - installments via WooCommerce Blocks
* [Add] PayU - Klarna via WooCommerce Blocks
* [Add] PayU - Twisto via WooCommerce Blocks
* [Add] PayU - PayPo via WooCommerce Blocks
* [Fix] Not showing Installments Mini Widget on product and products list page

[See changelog for all versions](https://raw.githubusercontent.com/PayU-EMEA/woo-payu-payment-gateway/master/changelog.txt).
