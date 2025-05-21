import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { useEffect } from '@wordpress/element';

const name = 'payuinstallments';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available ) || false;
const title = decodeEntities( settings.title ) || 'PayU';
const description = decodeEntities( settings.description ) || '';
const iconUrl = settings.icon;
const posId = decodeEntities( settings.additionalData?.posId ) || '';
const widgetKey = decodeEntities( settings.additionalData?.widgetKey ) || '';
const excludedPaytypes = decodeEntities( settings.additionalData?.excludedPaytypes ) || '';
const total = decodeEntities( settings.additionalData?.total ) || '';
const widgetOnCheckout =
  decodeEntities( settings.additionalData?.widgetOnCheckout ) || false;

const canMakePayment = () => {
  return available;
};

const Content = () => {
  return <div>{ description }</div>;
};

const Label = ( props ) => {
  const { PaymentMethodLabel } = props.components;

  useEffect( () => {
    window.OpenPayU?.Installments?.miniInstallment( '#installment-mini-cart', {
      creditAmount: Number( total ),
      posId,
      key: widgetKey,
      excludedPaytypes: excludedPaytypes,
      showLongDescription: true,
    } );
  }, [] );

  return widgetOnCheckout && posId && widgetKey && total ? (
    <div className="payu-block-installments-label">
      <div>
        <PaymentMethodLabel text={ title } className="payu-block-method" />
        <span className="payu-block-method-logo">
          <img src={ iconUrl } alt="PayU" name={ title } />
        </span>
      </div>
      <div id="installment-mini-cart"></div>
    </div>
  ) : (
    <>
      <PaymentMethodLabel text={ title } className="payu-block-method" />
      <span className="payu-block-method-logo">
        <img src={ iconUrl } alt="PayU" name={ title } />
      </span>
    </>
  );
};

const PayuStandardOptions = {
  name,
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment,
  ariaLabel: title,
};

registerPaymentMethod( PayuStandardOptions );
