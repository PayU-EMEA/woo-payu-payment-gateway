function showInstallmentsWidgetInCart() {
    if (window.OpenPayU && document.getElementById('installment-mini-cart').childNodes.length === 0) {
        var widgetData = document.getElementById("installment-mini-cart").dataset;
        var options = {
            creditAmount: Number(widgetData.priceTotal),
            posId: widgetData.posId,
            key: widgetData.widgetKey,
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