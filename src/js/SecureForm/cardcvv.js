import CardFieldErrors from './error';
import { useEffect, useState } from '@wordpress/element';

const CardCvv = ( { secureForms, formOptions, errors, focus } ) => {
  const [ secureForm ] = useState( () =>
    secureForms.add( 'cvv', formOptions )
  );

  useEffect( () => {
    if ( focus === true ) {
      secureForm.focus();
    }
  }, [ focus, secureForm ] );

  useEffect( () => {
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
