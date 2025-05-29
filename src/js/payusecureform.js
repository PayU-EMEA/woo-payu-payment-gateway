import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { StoreNotice } from '@woocommerce/blocks-components';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { scriptUtil } from './util';
import CardNumber from './SecureForm/cardnumber';
import CardExpire from './SecureForm/cardexpire';
import CardCvv from './SecureForm/cardcvv';
import { options } from './SecureForm/options';
import ReadMore from './read-more';

const name = 'payusecureform';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available || false );
const title = decodeEntities( settings.title || 'PayU' );
const description = decodeEntities( settings.description || '' );
const iconUrl = settings.icon;
const posId = decodeEntities( settings.additionalData?.posId ) || '';
const sdkIrl = decodeEntities( settings.additionalData?.sdkUrl ) || '';
const lang = decodeEntities( settings.additionalData?.lang ) || 'en';
const termsLinks = settings.termsLinks;

let payuSDK;
let secureForms;

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
            'The controller of your personal data is PayU S.A. with its registered office in Poznan (60-166), at Grunwaldzka Street 186 ("PayU").',
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
                'Your personal data will be processed for purposes of processing  payment transaction, notifying You about the status of this payment, dealing with complaints and also in order to fulfill the legal obligations imposed on PayU.',
                'woo-payu-payment-gateway'
              ) }
              <br />
              { __(
                'The recipients of your personal data may be entities cooperating with PayU during processing the payment. Depending on the payment method you choose, these may include: banks, payment institutions, loan institutions, payment card organizations, payment schemes), as well as suppliers supporting PayU’s activity providing: IT infrastructure, payment risk analysis tools and also entities that are authorised to receive it under the applicable provisions of law, including relevant judicial authorities. Your personal data may be shared with merchants to inform them about the status of the payment.',
                'woo-payu-payment-gateway'
              ) }
              <br />
              { __(
                'You have the right to access, rectify, restrict or oppose the processing of data, not to be subject to automated decision making, including profiling, or to transfer and erase Your personal data. Providing personal data is voluntary however necessary for the processing the payment and failure to provide the data may result in the rejection of the payment. For more information on how PayU processes your personal data, please click ',
                'woo-payu-payment-gateway'
              ) }
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
  return available;
};

const Content = ( { eventRegistration, emitResponse } ) => {
  const { onPaymentSetup } = eventRegistration;

  const [ error, setError ] = useState();
  const [ errorMessage, setErrorMessage ] = useState();
  const [ validationErrors, setValidationErrors ] = useState( [] );
  const [ elementFocus, setElementFocus ] = useState( '' );

  useEffect( () => {
    const init = () => {
      // eslint-disable-next-line no-undef
      payuSDK = PayU( posId );
      secureForms = payuSDK.secureForms( {
        lang,
      } );
    };

    if ( ! window.PayU ) {
      scriptUtil( sdkIrl )
        .load()
        .then( () => {
          init();
        } )
        .catch( ( e ) => {
          // eslint-disable-next-line no-console
          console.error( e );

          setError(
            __( 'Choose other payment method.', 'woo-payu-payment-gateway' )
          );
        } );
    } else {
      init();
    }

    return () => {
      payuSDK = undefined;
      secureForms = undefined;
    };
  }, [] );

  useEffect( () => {
    setError( undefined );
    setErrorMessage( undefined );
    setValidationErrors( [] );

    const unsubscribe = onPaymentSetup( () => {
      if ( ! secureForms ) {
        setError(
          __( 'Choose other payment method.', 'woo-payu-payment-gateway' )
        );

        document
          .getElementById( `payu_description_${ name }` )
          .scrollIntoView( {
            behavior: 'smooth',
          } );

        return {
          type: emitResponse.responseTypes.ERROR,
        };
      }

      try {
        return payuSDK.tokenize().then( ( result ) => {
          if ( result.status !== 'SUCCESS' ) {
            const technicalErrors = result.error.messages.filter(
              ( value ) => value.type === 'technical'
            );
            if ( technicalErrors.length > 0 ) {
              // eslint-disable-next-line no-console
              console.log( technicalErrors );

              setErrorMessage(
                technicalErrors.map( ( value ) => value.message ).join( ' ' )
              );
            }
            setValidationErrors(
              result.error.messages.filter(
                ( value ) => value.type === 'validation'
              )
            );

            return {
              type: emitResponse.responseTypes.ERROR,
            };
          }

          return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                payu_sf_token: result.body.token,
                payuBrowser_screenWidth: window.screen.width.toString(),
                payuBrowser_javaEnabled: window.navigator.javaEnabled(),
                payuBrowser_timezoneOffset: new Date()
                  .getTimezoneOffset()
                  .toString(),
                payuBrowser_screenHeight: window.screen.height.toString(),
                payuBrowser_userAgent: window.navigator.userAgent,
                payuBrowser_colorDepth: window.screen.colorDepth.toString(),
                payuBrowser_language: window.navigator.language,
              },
            },
          };
        } );
      } catch ( e ) {
        // eslint-disable-next-line no-console
        console.error( e );

        setErrorMessage( e.message );
      }

      return {
        type: emitResponse.responseTypes.ERROR,
      };
    } );
    return unsubscribe;
  }, [
    onPaymentSetup,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
  ] );
  return (
    <>
      { ! error && (
        <>
          <div id={ `payu_description_${ name }` }>{ description }</div>
          <div className="block-payu-card">
            <div className="block-payu-card-number">
              <label htmlFor="payu-card-number">
                { __( 'Card number', 'woo-payu-payment-gateway' ) }
              </label>
              { secureForms && (
                <CardNumber
                  setElementFocus={ setElementFocus }
                  secureForms={ secureForms }
                  formOptions={ { ...options } }
                  errors={ validationErrors.filter(
                    ( e ) => e.source === 'number'
                  ) }
                />
              ) }
            </div>
            <div className="block-payu-card-date">
              <label htmlFor="payu-card-date">
                { __( 'Expire date', 'woo-payu-payment-gateway' ) }
              </label>
              { secureForms && (
                <CardExpire
                  setElementFocus={ setElementFocus }
                  focus={ elementFocus === 'expire' }
                  secureForms={ secureForms }
                  formOptions={ {
                    ...options,
                    placeholder: {
                      ...options.placeholder,
                      date: __( 'MM/YY', 'woo-payu-payment-gateway' ),
                    },
                  } }
                  errors={ validationErrors.filter(
                    ( e ) => e.source === 'date'
                  ) }
                />
              ) }
            </div>
            <div className="block-payu-card-cvv">
              <label htmlFor="payu-card-cvv">
                { __( 'CVV', 'woo-payu-payment-gateway' ) }
              </label>
              { secureForms && (
                <CardCvv
                  secureForms={ secureForms }
                  focus={ elementFocus === 'cvv' }
                  formOptions={ { ...options } }
                  errors={ validationErrors.filter(
                    ( e ) => e.source === 'cvv'
                  ) }
                />
              ) }
            </div>
          </div>
          <TermInfo />
        </>
      ) }
      { error && (
        <StoreNotice status="error" isDismissible={ false }>
          { error }
        </StoreNotice>
      ) }
      { errorMessage && (
        <StoreNotice status="error" isDismissible={ false }>
          { errorMessage }
        </StoreNotice>
      ) }
    </>
  );
};

const Label = ( props ) => {
  const { PaymentMethodLabel } = props.components;

  return (
    <>
      <PaymentMethodLabel text={ title } className="payu-block-method" />
      <span className="payu-block-method-logo">
        <img src={ iconUrl } alt="Mastercard / Visa" name={ title } />
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
