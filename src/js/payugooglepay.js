import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { StoreNotice } from '@woocommerce/blocks-components';
import ReadMore from './read-more';

const name = 'payugooglepay';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available || false );
const title = decodeEntities( settings.title || 'Google Pay' );
const description = decodeEntities( settings.description || '' );
const iconUrl = settings.icon;
const posId = decodeEntities( settings.additionalData?.posId );
const currency = decodeEntities( settings.additionalData?.currency );
const totalPrice = decodeEntities( settings.additionalData?.totalPrice );
const env = decodeEntities( settings.additionalData?.env );
const merchantName = decodeEntities( settings.additionalData?.merchantName );
const termsLinks = settings.termsLinks;
const paymentsClient = window.google?.payments?.api?.PaymentsClient ? new google.payments.api.PaymentsClient( {
    environment: env,
  } ) : null;

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
  if ( ! paymentsClient ) {
    return false;
  }

  const isReadyToPayRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
    allowedPaymentMethods: [
      {
        type: 'CARD',
        parameters: {
          allowedAuthMethods: [ 'PAN_ONLY', 'CRYPTOGRAM_3DS' ],
          allowedCardNetworks: [ 'MASTERCARD', 'VISA' ],
        },
      },
    ],
  };
  return paymentsClient
    .isReadyToPay( isReadyToPayRequest )
    .then(true)
    .catch(() => {
      return false;
    });
};

const Content = ( { eventRegistration, emitResponse } ) => {
  const { onPaymentSetup } = eventRegistration;

  const [ error, setError ] = useState();

  useEffect( () => {
    const unsubscribe = onPaymentSetup( () => {
      setError( undefined );



      // var googleToken = document.getElementById('payu-google-token');
      // console.log(googleToken.value);
      // if ( googleToken.value === '' ) {
      console.log( env );

      const paymentDataRequest = {
        apiVersion: 2,
        apiVersionMinor: 0,
        merchantInfo: {
          merchantName: merchantName,
          merchantId: posId,
        },
        allowedPaymentMethods: [
          {
            type: 'CARD',
            parameters: {
              allowedAuthMethods: [ 'PAN_ONLY', 'CRYPTOGRAM_3DS' ],
              allowedCardNetworks: [ 'MASTERCARD', 'VISA' ],
              billingAddressRequired: false,
            },
            tokenizationSpecification: {
              type: 'PAYMENT_GATEWAY',
              parameters: {
                gateway: 'payu',
                gatewayMerchantId: posId,
              },
            },
          },
        ],
        transactionInfo: {
          totalPriceStatus: 'FINAL',
          countryCode: 'PL',
          totalPrice: totalPrice,
          currencyCode: currency,
        },
      };

        return paymentsClient
          .loadPaymentData( paymentDataRequest )
          .then( function ( paymentData ) {
            const paymentToken =
              paymentData.paymentMethodData.tokenizationData.token;
            // googleToken.value = btoa( paymentToken );
            return {
              type: emitResponse.responseTypes.SUCCESS,
              meta: {
                paymentMethodData: {
                  'payu-google-token': btoa( paymentToken ),
                },
              },
            };
          } )
          .catch( function ( err ) {
            console.error( err );
            return {
              type: emitResponse.responseTypes.ERROR,
            };
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
        <img src={ iconUrl } alt="Google Pay" name={ title } />
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
