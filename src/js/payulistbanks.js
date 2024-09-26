import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { StoreNotice } from '@woocommerce/blocks-components';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { clsx } from 'clsx';
import ReadMore from './read-more';

const name = 'payulistbanks';

const settings = getSetting( `${ name }_data`, {} );

const available = decodeEntities( settings.available || false );
const title = decodeEntities( settings.title || 'PayU' );
const description = decodeEntities( settings.description || '' );
const iconUrl = settings.icon;
const termsLinks = settings.termsLinks;
const paymethods = settings.additionalData?.paymethods ?? {};

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
  const [ selectedPaymethod, setSelectedPaymethod ] = useState( null );
  const [ errorMessage, setErrorMessage ] = useState( '' );

  useEffect( () => {
    const unsubscribe = onPaymentSetup( () => {
      if ( ! selectedPaymethod ) {
        setErrorMessage(
          __( 'Choose payment method.', 'woo-payu-payment-gateway' )
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

      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            'selected-bank': selectedPaymethod,
          },
        },
      };
    } );
    return unsubscribe;
  }, [
    onPaymentSetup,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    selectedPaymethod,
  ] );

  const isApplePayAvailable = useCallback( () => {
    try {
      const isApplePay = window.ApplePaySession?.canMakePayments();

      if ( ! isApplePay ) {
        return false;
      }
    } catch ( error ) {
      return false;
    }

    return true;
  }, [] );

  return (
    <>
      <div id={ `payu_description_${ name }` }>{ description }</div>
      <div className="payu-block-list-banks">
        { Object.values( paymethods ).map(
          ( { paytype, name: paytypeName, brandImageUrl, active } ) => {
            if ( paytype === 'jp' && ! isApplePayAvailable() ) {
              return null;
            }
            const isActive = active !== 'payu-inactive';
            const bankClass = clsx( {
              'payu-bank': true,
              [ `payu-bank-${ paytype }` ]: true,
              disabled: ! isActive,
              active: selectedPaymethod === paytype,
            } );

            return (
              <div
                className={ bankClass }
                key={ paytype }
                title={ paytypeName }
                onClick={ () => {
                  if ( isActive ) {
                    setErrorMessage( '' );
                    setSelectedPaymethod( paytype );
                  }
                } }
                role="button"
                tabIndex={ 0 }
              >
                <img src={ brandImageUrl } alt={ paytypeName } />
              </div>
            );
          }
        ) }
      </div>
      { errorMessage && (
        <StoreNotice status="error" isDismissible={ false }>
          { errorMessage }
        </StoreNotice>
      ) }
      <TermInfo />
    </>
  );
};

const Label = ( props ) => {
  const { PaymentMethodLabel } = props.components;

  return (
    <>
      <PaymentMethodLabel text={ title } />
      <span className="payu-block-method-logo">
        <img src={ iconUrl } alt="PayU" name={ title } />
      </span>
    </>
  );
};

const PayuListBanksOptions = {
  name,
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment,
  ariaLabel: title,
};

registerPaymentMethod( PayuListBanksOptions );
