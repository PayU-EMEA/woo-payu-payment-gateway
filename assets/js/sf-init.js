function sf_init() {
  var cc = document.getElementById( 'payu-card-container' );
  if ( cc ) {
    var lang = cc.dataset.lang;
    var optionsForms = {
      cardIcon: true,
      style: {
        basic: {
          fontSize: '18px',
        },
      },
      placeholder: {
        number: '',
        date: 'MM/YY',
        cvv: '',
      },
      lang: lang,
    };
    var pcn = document.getElementById( 'payu-card-number' );
    if ( pcn.childNodes.length === 0 ) {
      var payuSdkForms = PayU( cc.dataset.payuPosid );
      var secureForms = payuSdkForms.secureForms();
      var cardNumber = secureForms.add( 'number', optionsForms );
      var cardDate = secureForms.add( 'date', optionsForms );
      var cardCvv = secureForms.add( 'cvv', optionsForms );

      cardNumber.render( '#payu-card-number' );
      cardNumber.on( 'change', function ( ev ) {
        if ( ev.empty === false && ev.error === false && ev.length === 16 ) {
          cardDate.focus();
        }
      } );

      cardDate.render( '#payu-card-date' );
      cardDate.on( 'change', function ( ev ) {
        if ( ev.empty === false && ev.error === false ) {
          cardCvv.focus();
        }
      } );

      cardCvv.render( '#payu-card-cvv' );
      window.payuSdkForms = payuSdkForms;
    }
  }
}
