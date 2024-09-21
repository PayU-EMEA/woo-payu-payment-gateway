import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';

const name = 'payuklarna';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available || false );
const title = decodeEntities( settings.title || 'Klarna' );
const description = decodeEntities( settings.description || '' );
const iconUrl = settings.icon;

const canMakePayment = () => {
	return available;
};

const Content = () => {
	return <div>{ description }</div>;
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;

	return (
		<>
			<PaymentMethodLabel text={ title } className="payu-block-method" />
			<span className="payu-block-method-logo">
				<img src={ iconUrl } alt="Klarna" name={ title } />
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
