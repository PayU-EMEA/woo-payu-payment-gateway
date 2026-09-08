import { decodeEntities } from '@wordpress/html-entities';
import { select } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { validationStore } from '@woocommerce/block-data';
import { useEffect, useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { StoreNotice } from '@woocommerce/blocks-components';
import ReadMore from './read-more';

const name = 'payuapplepay';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available || false );
const title = decodeEntities( settings.title || 'Apple Pay' );
const description = decodeEntities( settings.description || '' );
const iconUrl = settings.icon;

const currency = decodeEntities( settings.additionalData.currency );
const totalPrice = decodeEntities( settings.additionalData.totalPrice );
const appleDisplayName = decodeEntities(
  settings.additionalData.appleDisplayName
);
const createSessionUrl = decodeEntities(
  settings.additionalData.createSessionUrl
);
const termsLinks = settings.termsLinks;

const TermInfo = () => {
  const [ showMore1, setShowMore1 ] = useState( false );
  const [ showMore2, setShowMore2 ] = useState( false );
  const conditionUrl = decodeEntities( termsLinks.condition );
  const privacyUrl = decodeEntities( termsLinks.privacy );

  return (
    <div className="payu-accept-conditions">
      <div className="payu-conditions-description">
        <div>
          { __(
            "Payment is processed by PayU SA; The recipient's data, the payment title and the amount are provided to PayU SA by the recipient;",
            'woo-payu-payment-gateway'
          ) }{ ' ' }
          { ! showMore1 && (
            <ReadMore onCLick={ () => setShowMore1( true ) }>
              { __( 'read more', 'woo-payu-payment-gateway' ) }
            </ReadMore>
          ) }
          { showMore1 && (
            <>
              { __(
                'The order is sent for processing when PayU SA receives your payment. The payment is transferred to the recipient within 1 hour, not later than until the end of the next business day; PayU SA does not charge any service fees.',
                'woo-payu-payment-gateway'
              ) }
            </>
          ) }
        </div>
        <div>
          { __( 'By paying you accept', 'woo-payu-payment-gateway' ) }{ ' ' }
          <a href={ conditionUrl } target="_blank" rel="noreferrer">
            { __( '"PayU Payment Terms"', 'woo-payu-payment-gateway' ) }
          </a>
          .
        </div>
        <div>
          { __(
            'The controller of your personal data is PayU S.A. with its registered office in Poznan (60–166), at Grunwaldzka Street 186 ("PayU").',
            'woo-payu-payment-gateway'
          ) }{ ' ' }
          { ! showMore2 && (
            <ReadMore onCLick={ () => setShowMore2( true ) }>
              { __( 'read more', 'woo-payu-payment-gateway' ) }
            </ReadMore>
          ) }
          { showMore2 && (
            <>
              { __(
                'Your personal data will be processed for purposes of processing payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.',
                'woo-payu-payment-gateway'
              ) }
              <br />
              { __(
                'The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.',
                'woo-payu-payment-gateway'
              ) }
              <br />
              { __(
                'You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click',
                'woo-payu-payment-gateway'
              ) }{ ' ' }
              <a href={ privacyUrl } target="_blank" rel="noreferrer">
                { __( 'PayU privacy policy', 'woo-payu-payment-gateway' ) }
              </a>
              .
            </>
          ) }
        </div>
      </div>
    </div>
  );
};

const canMakePayment = () => {
  if ( ! available ) {
    return false;
  }

  let applePayAvailable;

  try {
    applePayAvailable =
      window.ApplePaySession && ApplePaySession.canMakePayments();
  } catch ( _e ) {
    applePayAvailable = false;
  }

  return applePayAvailable;
};

const Content = ( { eventRegistration, emitResponse } ) => {
  const applePayApiVersion = useMemo( () => {
    const APPLE_PAY_API_MIN_VERSION = 1;
    const APPLE_PAY_API_MAX_VERSION = 14; // https://developer.apple.com/documentation/applepayontheweb/apple-pay-on-the-web-version-history

    for ( let i = APPLE_PAY_API_MAX_VERSION; i > APPLE_PAY_API_MIN_VERSION; i-- ) {
      if ( ApplePaySession.supportsVersion( i ) ) {
        return i;
      }
    }

    return APPLE_PAY_API_MIN_VERSION;
  }, [] );

  const applePayPaymentRequest = useMemo( () => {
    return {
      merchantCapabilities: [
        'supports3DS',
        'supportsCredit',
        'supportsDebit',
      ],
      supportedNetworks: [ 'masterCard', 'visa' ],
      countryCode: 'PL',
      total: {
        type: 'final',
        label: appleDisplayName,
        amount: totalPrice,
      },
      currencyCode: currency,
    };
  }, [ appleDisplayName, totalPrice, currency ] );

  const { onPaymentSetup } = eventRegistration;

  const [ error, setError ] = useState();

  useEffect( () => {
    const unsubscribe = onPaymentSetup( async () => {
      if ( select( validationStore ).hasValidationErrors() ) {
        return {
          type: emitResponse.responseTypes.ERROR,
        };
      }

      setError( undefined );

      const applePaySession = new ApplePaySession(
        applePayApiVersion,
        applePayPaymentRequest
      );

      const errorMessage = __(
        'There was a problem with Apple Pay payment. Please try again or use a different payment method.',
        'woo-payu-payment-gateway'
      );

      return new Promise( ( resolve ) => {
        applePaySession.onvalidatemerchant = ( event ) => {
          const getApplePaySession = async () => {
            let sessionResponse;

            sessionResponse = await fetch( createSessionUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
            } );

            if ( ! sessionResponse.ok ) {
              resolve( {
                type: emitResponse.responseTypes.ERROR,
                message: errorMessage,
              } );

              applePaySession.abort();
              return;
            }

            const session = await sessionResponse.json();

            try {
              applePaySession.completeMerchantValidation( session );
            } catch ( error ) {
              resolve( {
                type: emitResponse.responseTypes.ERROR,
                message: errorMessage,
              } );

              applePaySession.abort();
            }
          };

          void getApplePaySession();
        };

        applePaySession.onpaymentauthorized = ( event ) => {
          applePaySession.completePayment( ApplePaySession.STATUS_SUCCESS );

          resolve( {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                'payu-apple-token': btoa(
                  JSON.stringify( event.payment.token.paymentData )
                ),
              },
            },
          } );
        };

        applePaySession.oncancel = ( ) => {
          resolve( {
            type: emitResponse.responseTypes.ERROR,
          } );
        };

        applePaySession.begin();
      } );
    } );

    return unsubscribe;
  }, [
    onPaymentSetup,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
  ] );

  return (
    <>
      { error && (
        <StoreNotice status="error" isDismissible={ false }>
          { error }
        </StoreNotice>
      ) }
      <div>{ description }</div>
      <TermInfo />
    </>
  );
};

const Label = ( props ) => {
  const { PaymentMethodLabel } = props.components;

  return (
    <>
      <PaymentMethodLabel text={ title } className="payu-block-method" />
      <span className="payu-block-method-logo">
        <img src={ iconUrl } alt="Apple Pay" name={ title } />
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
