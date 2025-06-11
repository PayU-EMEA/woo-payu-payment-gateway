# 2.7.0 - 2025-06-11
* [Add] Twisto Slice as separate method
* [Add] Support more currencies in buy now pay later payments
* [Update] New credit widget implementation, showing widget on Blocks cart and checkout
* [Update] Autofocus next field in Secure Form
* [Update] The order number is at the beginning of the description in PayU

# 2.6.2 - 2025-04-09
* [Update] Update Klarna and Twisto logos
* [Fix] Manual pickup and rejection for WAITING_FOR_CONFIRMATION

# 2.6.1 - 2024-09-26
* [Fix] Not be accessed before initialization

# 2.6.0 - 2024-09-21
* [Add] PayU - secure form via WooCommerce Blocks

# 2.5.0 - 2024-07-23
* [Add] PayU - bank list via WooCommerce Blocks

# 2.4.1 - 2024-07-16
* [Fix] #73 - Not send e-mail to administrator about a new order
* [Fix] Showing error in empty cart (blocks) when installments are active

# 2.4.0 - 2024-07-07
* [Add] PayU - BLIK via WooCommerce Blocks

# 2.3.1 - 2024-06-24
* [Fix] "Call to a member function get_total() on null" for Installments

# 2.3.0 - 2024-06-21
* [Add] PayU - installments via WooCommerce Blocks
* [Add] PayU - Klarna via WooCommerce Blocks
* [Add] PayU - Twisto via WooCommerce Blocks
* [Add] PayU - PayPo via WooCommerce Blocks
* [Fix] Not showing Installments Mini Widget on product and products list page

# 2.2.2 - 2024-06-18
* [Fix] Warning: Attempt to read property "total" on null

# 2.2.1 - 2024-06-17
* [Fix] Some methods were not showing up
* [Fix] PHP Deprecated: Creation of dynamic property

# 2.2.0 - 2024-06-17
* [Add] PayU – standard via WooCommerce Blocks
* [Add] PayU – payment card via WooCommerce Blocks

# 2.1.0
* Added Klarna as a separate payment method

# 2.0.35
* Fixed product name length
* Fixed php notice/warning (#67)
* Increased requirements: PHP >= 7.4, Wordpress >= 5.0, WooCommerce >= 4.0

# 2.0.34
* Fixed card holder name
* Fixed substring for multi-byte characters

# 2.0.33
* Fixed getTotal for min/max

# 2.0.32
* Fixed php notices
* Fixed getTotal for min/max

# 2.0.31
* Fixed warnings
* SDK update

# 2.0.30
* Removed use of the NumberUtil class

# 2.0.29
* Decimal quantity in products

# 2.0.28
* Fixed installment widget to use taxed product price
* Fixed installment widget not refreshing after product quantity changes in cart details
* Fixed refund request

# 2.0.27
*  Fixed for decimal quantity

# 2.0.26
*  Better use of checkout_place_order hook

# 2.0.23 - 2.0.25
* Added compatibility with HPOS (High-Performance Order Storage)
* Sends more data for better detected frauds

# 2.0.22
* Fixed getTotal

# 2.0.21
* Fixed installment payment method name in order summary page

# 2.0.20
* Fixed installment payment method name in emails

# 2.0.19
* Added installment widget integration
* PayU address update

# 2.0.18
* Fixed link to repay in email
* The shorter product name
* Changed plugin url on github

# 2.0.14 - 2.0.17
* Changes required by wordpress plugin teams (plugin name, change translate domain, sanitize variables, remove external js, update readme)

# 2.0.13
* Fix method available for virtual products
* Separate Twisto and PayPo

# 2.0.12
* Fix calculate total for check min/max
* Possibility to enable for virtual orders
* Fix #43 php notice
* Update Visa logo

# 2.0.11
* Remove manual stock reduced
* Remove unused REJECTED
* Add filter `woocommerce_payu_status_cancelled`

# 2.0.10
* PayU terms - info instead of checkbox
* Better check shipping method, clean up

# 2.0.9
* Fix not show installments, blik, card as separated method

# 2.0.8
* Send mail to customer when status change from payu-waiting to processing
* Fix get_total() on null

# 2.0.7
* Fix show paymethods when WPML is active

# 2.0.6
* Fix hide inactive method

# 2.0.5
* Translate fix
* Add filters for multicurrency support
* Notice fix

# 2.0.4
* Fix size gateway logotypes in some themes

# 2.0.3
* Better js and css enqueue and optimize load methods
* Better UX for bank icons

# 2.0.2
* Fix #31 js loading dependency
* Fix #30 remove 100% layout width
* Fix #27 fix registration url

# 2.0.1
* Fix change order status for virtual products

# 2.0.0
* New version introducing many additional features e.g. many payment types, enhanced configuration options, order repayment.

