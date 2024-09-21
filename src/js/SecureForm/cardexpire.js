import CardFieldErrors from './error';
import { useEffect } from '@wordpress/element';

const CardExpire = ( { secureForms, formOptions, errors } ) => {
	useEffect( () => {
		const secureForm = secureForms.add( 'date', formOptions );
		secureForm.render( '#payu-card-date' );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<>
			<div className="payu-card-form" id="payu-card-date"></div>
			<CardFieldErrors errors={ errors } />
		</>
	);
};

export default CardExpire;
