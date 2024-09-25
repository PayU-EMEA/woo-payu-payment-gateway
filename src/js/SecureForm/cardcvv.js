import CardFieldErrors from './error';
import { useEffect } from '@wordpress/element';

const CardCvv = ( { secureForms, formOptions, errors } ) => {
  useEffect( () => {
    const secureForm = secureForms.add( 'cvv', formOptions );
    secureForm.render( '#payu-card-cvv' );
  }, [] ); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <>
      <div className="payu-card-form" id="payu-card-cvv"></div>
      <CardFieldErrors errors={ errors } />
    </>
  );
};

export default CardCvv;
