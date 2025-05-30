const {registerPlugin} = window.wp.plugins;
import {ExperimentalOrderMeta} from '@woocommerce/blocks-checkout';
import {useEffect} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';

const name = `credit-widget-block`;

const settings = getSetting(`${name}_data`, {});

const widgetEnabledOnPage = decodeEntities(settings.widgetEnabledOnPage) || false;
const total = decodeEntities(settings.total) || '';
const posId = decodeEntities(settings.posId) || '';
const widgetKey = decodeEntities(settings.widgetKey) || '';
const excludedPaytypes = decodeEntities(settings.excludedPaytypes) || [];
const lang = decodeEntities(settings.lang) || 'en';
const currency = decodeEntities(settings.currency) || '';

function Widget() {
    useEffect(() => {
        window.OpenPayU?.Installments?.miniInstallment('#installment-mini-cart', {
            creditAmount: Number(total),
            posId: posId,
            key: widgetKey,
            excludedPaytypes: excludedPaytypes,
            lang: lang,
            currencySign: currency,
            showLongDescription: true,
        });
    }, []);

    return (
        <div id="installment-mini-cart"></div>
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
