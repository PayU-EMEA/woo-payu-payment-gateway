const {registerPlugin} = window.wp.plugins;
import {ExperimentalOrderMeta} from '@woocommerce/blocks-checkout';
import {useEffect} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';

const name = `credit-widget-block`;

const settings = getSetting(`${name}_data`, {});

const widgetEnabledOnPage = decodeEntities(settings.widgetEnabledOnPage) || false;
const posId = decodeEntities(settings.posId) || '';
const widgetKey = decodeEntities(settings.widgetKey) || '';
const excludedPaytypes = decodeEntities(settings.excludedPaytypes) || [];
const lang = decodeEntities(settings.lang) || 'en';
const currency = decodeEntities(settings.currency) || '';

function getTotal(cart) {
    if (!cart || !cart.cartTotals || !cart.cartTotals.total_price) {
        return 0;
    }
    return Number(cart.cartTotals.total_price / (10 ** cart.cartTotals.currency_minor_unit));
}

function Widget({ cart }) {
    useEffect(() => {
        window.OpenPayU?.Installments?.miniInstallment('#installment-mini-block', {
            creditAmount: getTotal(cart),
            posId: posId,
            key: widgetKey,
            excludedPaytypes: excludedPaytypes,
            lang: lang,
            currencySign: currency,
            showLongDescription: true,
        });
    }, [cart]);

    return (
        <div id="installment-mini-block"></div>
    );
}

const render = () => {
    return posId && widgetKey && total ? (
        <ExperimentalOrderMeta>
            <Widget/>
        </ExperimentalOrderMeta>
    ) : null;
};

if (widgetEnabledOnPage) {
    registerPlugin('credit-widget', {render, scope: 'woocommerce-checkout'});
}
