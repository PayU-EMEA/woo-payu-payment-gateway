# Moduł płatności PayU dla WooCommerce

**If you have any questions or want to report a bug please [contact our technical support][ext13].**

## Requirements

**Note:** This module works only with `REST API` POS type.

If you do not have a PayU merchant account [**register a production account**][ext4] or [**register a sandbox account**][ext5]

The following PHP libraries are required: [cURL][ext1] i [hash][ext2].

## Installation
Use [automatic installation and activation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation) available in the Wordpress admin panel. Look out for `WooCommerce PayU EU Payment Gateway`.

## Payment methods
The plugin offers the following payment methods:
| No.    | Method | Description
| :---: | ------ | ---
| 1     | PayU - standard | payer will be redirected to PayU's hosted payment page where any available payment type configured on your POS can be chosen
| 2     | PayU - bank list | payment type list will be displayed, depending on chosen type the payer will be either redirected directly to the bank or to PayU's hosted payment page
| 3     | PayU - payment card | payer will be redirected to PayU's hosted card form where credit, debit or prepaid card data can be securely entered 
| 4     | PayU - secure form | a secure form collecting credit, debit or prepaid card data will be displayed
| 5     | PayU - Blik | payer will be redirected to Blik's page
| 6     | PayU - installments | payer will be redirected to installment payment form 
| 7     | PayU - Twisto | payer will be redirected to Twisto payment form
| 8     | PayU - PayPo | payer will be redirected to PayPo payment form

#### Payment method remarks

* Methods `PayU - standard` and `PayU - bank list` enable payments of any type and differ only with the way the payment type is chosen. **Should not be configured both at once**.
* Methods `PayU - payment card` and `PayU - secure form` enable card payments and differ only with the way the card data is entered. **Should not be configured both at once**.
* In case `PayU - bank list` method is switched on, the following payment types are removed from the list: cards if `PayU - payment card` or `PayU - secure form` is on, Blik if  `PayU - Blik` is on, installments if `PayU - installments` is on, installments if `PayU - Twisto` is on, PayPo if `PayU - PayPo` is on.
* `PayU - secure form` method requires the shop to be available via HTTPS (for local tests, the address should be http://localhost)
* Even if  `PayU - payment card`, `PayU - secure form`, `PayU - Blik`, `PayU - installments`, `PayU - Twisto` and `PayU - PayPo` are on, they may be not visible in case they are not configured on your POS in PayU system or if the amount is outside min-max range for the given payment type.

## Configuration
#### Global configuration 
Global configuration is available in the main menu as `PayU Settings`

POS parameters:

| Parameter | Opis
| --------- | ----
| POS ID| POS (point of sale) ID in PayU system
| Second key MD5 | Second key (MD5) in PayU system
| OAuth - client_id | OAuth protocol - client_id in PayU system
| OAuth - client_secret | client_secret for OAuth in PayU system
| Sandbox - POS ID| POS (point of sale) ID in Sandbox
| Sandbox - Second key MD5 | Second key (MD5) in Sandbox
| Sandbox - OAuth - client_id | OAuth protocol - client_id in Sandbox
| Sandbox - OAuth - client_secret | client_secret for OAuth in Sandbox


* In case of using more than one currency, there will be a separate configuration for each available currency - more information in [Multicurrency](#multicurrency) section
* By default, each payment method uses global POS parameters

Other parameters - applicable to all modules:

| Parameter | Description
| --------- | ----
| Default order status | Status set after payment is started. Possible values  `on-hold` or `pending`.< br/>According to the WooCommerce documentation, when warehouse management is enabled for `on-hold` status, the number of products in the warehouse will be reduced and restored when the order changes to `canceled` status, for `pending` status, the inventory levels will not be changed.
| Enable repayment | Allows the payer to try again after failed payment. Before using this option please check [Repayment](#repayment).

#### Payment method configuration
Parameters available for every payment method:

| Parameter | Description
| --------- | ----
| Enable / Disable  | Enables payment method.
| Name | Name displayed during checkout.
| Sandbox mode | If enabled, payments are done in Sandbox environment using Sandbox settings.
| Use global settings | If not enabled, you need to provide specific settings for given payment method.
| Description | Payment method description displayed during checkout.
| Enable for shipping method | Payment method may be enabled only for specific shipping methods. In case no shipping method is provided, the payment method is enabled for every shipping method.
| Virtual orders | Payment method will be enabled for virtual orders

Parameters available for `PayU - bank list`:

| Parameter | Description |
| --------- | ---- |
| Own ordering | To use your own ordering of payment types, you need to provide a comma-separated list of payment types codes from PayU system. [Payment types list][ext6].
| Show inactive payment methods | In case a given payment type is not active it is still displayed, but greyed out, otherwise not displayed.

## Multicurrency
There are two ways to handle multicurrency:
### `WMPL` Plugin
For every currency added to `WPML` plugin you can find separate point of sale configuration.
### Filters
The plugin provides two filters, that allow to add support for multiple currencies during payment

| Filter name | Description | Type
| --------- | ---- | ----
| `woocommerce_payu_multicurrency_active` | Should multicurrency service be enabled | bool
| `woocommerce_payu_get_currency_codes` | List of currencies ISO codes in ISO 4217 standard e.g. "PLN". | array

Example:
```php
function payu_activate_multicurrency($active)
{
    return true;
}

function payu_set_currency_list($currencies)
{
    return ['PLN', 'EUR'];
}

add_filter('woocommerce_payu_multicurrency_active', 'payu_activate_multicurrency');
add_filter('woocommerce_payu_get_currency_codes', 'payu_set_currency_list');
```

Notes:
*  When the `WMPL` plugin is installed and filters are configured, the availability of currencies is checked in `WMPL` then through the filters. 
* Seperate point of sale configurations are available when number of currencies is greater than 1.

## Filter hooks

### Change order status
| Filter Name                         | Description                              | Type   | Parameters
|-------------------------------------|------------------------------------------|--------| ----
| `woocommerce_payu_status_cancelled` | Order status for `CANCELED` notification | string | Order

## Repayment
This feature enables the payer to create a new payment for the same order if the previous payment was not successful.
To use the feature it is necessary to properly configure your POS in PayU, by disabling "Automatic collection" (it is enabled by default). This option is available in PayU panel. You need to go to Online payments then My shops and then POS. "Automatic collection is configured for every payment type, but to disable all at once you can use button at the very bottom, under the payment type list.

Repayment allows to create many payments in PayU for a single WooCommerce order. The plugin will automatically collect the first successful payment, all other will be canceled.
From user point of view, repayment is possible:
* by clicking a link in the order confirmation email
* by clicking "Pay with PayU" link in Actions column in order list
* by clicking "Pay with PayU" link over order details section

## Credit widget
Plugin provides seamless integration of [installments widget][ext14], responsible for presenting minimal installment amount for a given product.
Functionality is enabled by default. It can be deactivated in administration panel (WooCommerce->Settings->Payments->PayU - installments).
Integration points of a widget have been described in a table below.

| Checkbox Installments widget | Description | Presentation |
| --- | --- | --- |
| Enabled on product listings | <div style="max-width:200px">Presents "installment from" widget on all product listings.</div> |<div style="max-width:500px">![Prezentacja widgetu](readme_images/credit_widget_listings.jpg)</div>|
| Enabled on product page | <div style="max-width:200px">Presents widget on product page.</div> |<div style="max-width:500px">![Prezentacja widgetu](readme_images/credit_widget_product_page.jpg)</div>|
| Enabled on cart page | <div style="max-width:200px">Presents widget on cart page - relates to a total cart amount and shipping costs.</div> |<div style="max-width:500px">![Prezentacja widgetu](readme_images/credit_widget_cart_page.jpg)</div>|
| Enabled on checkout page | <div style="max-width:200px">Presents widget on checkout page - relates to a total cart amount and shipping costs.</div> |<div style="max-width:500px">![Prezentacja widgetu](readme_images/credit_widget_checkout_page.jpg)</div>|

Widget is presented only for PLN currency and products or carts with amount in allowed installment amount range.

## Emails
The plugin does not send any additional emails and does not interfere with any mailing process.

In case repayment is configured, the mail confirming order placement is enhanced with information about the possibility to pay the order.

<!--external links:-->
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php
[ext4]: https://poland.payu.com/en/how-to-activate-payu/
[ext5]: https://secure.snd.payu.com/boarding/#/registerSandbox/?lang=en
[ext6]: http://developers.payu.com/en/overview.html#paymethods
[ext13]: https://poland.payu.com/en/support/
[ext14]: https://developers.payu.com/en/installments.html#installments_best_practices_mini