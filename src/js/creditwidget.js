const { registerPlugin } = window.wp.plugins;
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const name = `credit-widget-block`;

const settings = getSetting( `${ name }_data`, {} );

const widgetEnabledOnPage =
  decodeEntities( settings.widgetEnabledOnPage ) || false;
const posId = decodeEntities( settings.posId ) || '';
const widgetKey = decodeEntities( settings.widgetKey ) || '';
const excludedPaytypes = decodeEntities( settings.excludedPaytypes ) || [];
const lang = decodeEntities( settings.lang ) || '';
const currency = decodeEntities( settings.currency ) || '';

function getTotal( cart ) {
    if (
        cart?.cartTotals?.total_price &&
        cart?.cartTotals?.currency_minor_unit
    ) {
        const { total_price, currency_minor_unit } = cart.cartTotals;
        return Number(total_price / 10 ** currency_minor_unit);
    }
    return 0;
}


function Widget( { cart } ) {
  useEffect( () => {
    window.OpenPayU?.Installments?.miniInstallment( '#installment-mini-block', {
      creditAmount: getTotal( cart ),
      posId: posId,
      key: widgetKey,
      excludedPaytypes: excludedPaytypes,
      lang: lang,
      currencySign: currency,
      showLongDescription: true,
    } );
  }, [ cart ] );

  return <div id="installment-mini-block"></div>;
}

const render = () => {
  return posId && widgetKey ? (
    <ExperimentalOrderMeta>
      <Widget />
    </ExperimentalOrderMeta>
  ) : null;
};

if ( widgetEnabledOnPage ) {
  registerPlugin( 'credit-widget', { render, scope: 'woocommerce-checkout' } );
}
