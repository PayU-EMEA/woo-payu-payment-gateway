function showInstallmentsWidgetInCart() {
    if (window.OpenPayU && !document.getElementById('installment-mini-cart')) {
        jQuery(document).find("label[for='payment_method_payuinstallments']")
            .append(("<div id='installment-mini-cart'>"));
        var options = {
            creditAmount: Number(PayUInstallmentsWidgetCartData.priceTotal),
            posId: PayUInstallmentsWidgetCartData.posId,
            key: PayUInstallmentsWidgetCartData.widgetKey,
            showLongDescription: true
        };
        OpenPayU.Installments.miniInstallment('#installment-mini-cart', options);
    }
}
document.addEventListener("DOMContentLoaded", showInstallmentsWidgetInCart);

showInstallmentsWidgetInCart();

jQuery(document).on('updated_checkout', function() {
    showInstallmentsWidgetInCart();
});