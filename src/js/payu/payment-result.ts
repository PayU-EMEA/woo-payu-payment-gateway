import { __ } from '@wordpress/i18n';

window.addEventListener( 'DOMContentLoaded', () => {
  const statusWaitingElement = document.getElementById(
    'payu-payment-status-waiting'
  );

  const statusResultElement = document.getElementById(
    'payu-payment-status-result'
  );

  const showResult = ( header: string ) => {
    statusResultElement.innerHTML = `<h3>${ header }</h3>`;
    statusWaitingElement.style.display = 'none';
  };

  const getStatus = async ( counter: number ) => {
    const response = await fetch( window.payu_get_status_url );
    const contentType = response.headers.get( 'content-type' );

    if ( ! response.ok ) {
      const isJson = contentType?.includes( 'application/json' );
      let message: string;

      if ( ! isJson ) {
        message = await response.text();
      } else {
        const body = await response.json();
        message =
          body.message ?? __( 'Unknown Error', 'woo-payu-payment-gateway' );
      }
      showResult( message );

      return true;
    }
    const { status } = ( await response.json() ) as { status: string };
    switch ( status ) {
      case 'success':
        showResult(
          __( 'Your payment was successful.', 'woo-payu-payment-gateway' )
        );

        return true;
      case 'failed':
        showResult( __( 'Your payment failed.', 'woo-payu-payment-gateway' ) );

        return true;
      default:
        if ( counter === 0 ) {
          showResult(
            __( 'Your payment is being processed.', 'woo-payu-payment-gateway' )
          );
          return true;
        }
    }

    return false;
  };

  const run = async () => {
    setTimeout( async () => {
      let counter = 10;
      const isFirstFinal = await getStatus( counter );

      if ( ! isFirstFinal ) {
        const intervalId = setInterval( async () => {
          const isFinal = await getStatus( counter );
          if ( isFinal || counter === 0 ) {
            clearInterval( intervalId );
          } else {
            counter--;
          }
        }, 4000 );
      }
    }, 3000 );
  };
  if (
    window.payu_get_status_url &&
    document.getElementById( 'payu-payment-status' )
  ) {
    void run();
  }
} );
