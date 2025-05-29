import CardFieldErrors from './error';
import { useEffect } from '@wordpress/element';

const CardNumber = ( {
  secureForms,
  formOptions,
  errors,
  setElementFocus,
} ) => {
  useEffect( () => {
    const secureForm = secureForms.add( 'number', formOptions );
    secureForm.render( '#payu-card-number' );
    secureForm.on( 'change', ( { empty, error, length } ) => {
      if ( empty === false && error === false && length === 16 ) {
        setElementFocus( 'expire' );
      }
    } );
  }, [] ); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <>
      <div className="payu-card-form" id="payu-card-number"></div>
      <CardFieldErrors errors={ errors } />
    </>
  );
};

export default CardNumber;
