import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { useEffect } from '@wordpress/element';

const name = 'payuinstallments';

const settings = getSetting( `${name}_data`, {} );

const available = decodeEntities(settings.available || false);
const title = decodeEntities(settings.title || 'PayU');
const description = decodeEntities(settings.description || '');
const iconUrl = settings.icon;
const { posId, widgetKey, total } = settings.additionalData;

const canMakePayment = () => {
    return available;
};

const Content = () => {
    return <div>{description}</div>;
};

const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components

    useEffect(() => {
        OpenPayU.Installments.miniInstallment('#installment-mini-cart', {
            creditAmount: Number(total),
            posId,
            key: widgetKey,
            showLongDescription: true
        });
    }, []);

    return (
        <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%' }}>
            <div style={{ display: 'flex', alignItems: 'center' }}>
                <PaymentMethodLabel text={title} className="payu-block-method"/>
                <span className="payu-block-method-logo"><img src={iconUrl} alt="PayU" name={title}/></span>
            </div>
            <div id="installment-mini-cart"></div>
        </div>
    );
};

const PayuStandardOptions = {
    name: name,
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment,
    ariaLabel: title
};

registerPaymentMethod(PayuStandardOptions);
