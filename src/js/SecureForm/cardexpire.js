import CardFieldErrors from './error';
import { useEffect, useState } from '@wordpress/element';

const CardExpire = ( {
  secureForms,
  formOptions,
  errors,
  setElementFocus,
  focus,
} ) => {
  const [ secureForm ] = useState( () =>
    secureForms.add( 'date', formOptions )
  );

  useEffect( () => {
    if ( focus === true ) {
      secureForm.focus();
    }
  }, [ focus, secureForm ] );

  useEffect( () => {
    secureForm.render( '#payu-card-date' );
    secureForm.on( 'change', ( { empty, error } ) => {
      if ( empty === false && error === false ) {
        setElementFocus( 'cvv' );
      }
    } );
  }, [] ); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <>
      <div className="payu-card-form" id="payu-card-date"></div>
      <CardFieldErrors errors={ errors } />
    </>
  );
};

export default CardExpire;
