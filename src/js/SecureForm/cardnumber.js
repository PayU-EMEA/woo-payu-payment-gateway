import CardFieldErrors from './error';
import { useEffect } from '@wordpress/element';

const CardNumber = ( { secureForms, formOptions, errors } ) => {
	useEffect( () => {
		const secureForm = secureForms.add( 'number', formOptions );
		secureForm.render( '#payu-card-number' );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<>
			<div className="payu-card-form" id="payu-card-number"></div>
			<CardFieldErrors errors={ errors } />
		</>
	);
};

export default CardNumber;
