(function ($) {
    var $form = $('form.checkout');

    $('body').on('click', '.payu-list-banks li.payu-active label', function () {
        $('.payu-list-banks label').removeClass('active');
        $(this).addClass('active');
    });

    $('body').on('click', '.payu-list-banks .payu-active', function () {
        $('.pbl-error').slideUp(250);
    });

    $('body').on('click', '.payu-conditions-description .payu-read-more', function () {
        $(this).next('.payu-more-hidden').show();
        $(this).remove();
    });

    $('form#order_review').on('submit', function (e) {
        var paymentMethod = $(this).find('input[name="payment_method"]:checked').val();
        var validateResult = true;

        if (paymentMethod === 'payusecureform') {
            validateResult = validate_payu_secure_form(this);
        } else if (paymentMethod === 'payulistbanks') {
            validateResult = validate_payu_list_banks();
        } else if (paymentMethod === 'payugooglepay') {
            validateResult = validate_payu_google_pay(this);
        }

        if (!validateResult) {
            setTimeout(function () {
                $(e.target).unblock();
            }, 500);
        }

        return validateResult;
    });

    $form.on('checkout_place_order_payusecureform', function () {
        return validate_payu_secure_form(this);
    });

    $form.on('checkout_place_order_payulistbanks', function () {
        return validate_payu_list_banks();
    });

    $form.on('checkout_place_order_payugooglepay', function () {
        return validate_payu_google_pay(this);
    });

    function validate_payu_secure_form(form) {
        var payuTokenElement = document.getElementsByName('payu_sf_token')[0];

        if (payuTokenElement.value === '') {
            try {
                window.payuSdkForms.tokenize()
                    .then(function (result) {
                        $('.payu-sf-validation-error, .payu-sf-technical-error')
                            .html('')
                            .slideUp(250);
                        if (result.status === 'SUCCESS') {
                            payuTokenElement.value = result.body.token;
                            document.getElementsByName('payu_browser[screenWidth]')[0].value = screen.width;
                            document.getElementsByName('payu_browser[javaEnabled]')[0].value = navigator.javaEnabled();
                            document.getElementsByName('payu_browser[timezoneOffset]')[0].value = new Date().getTimezoneOffset();
                            document.getElementsByName('payu_browser[screenHeight]')[0].value = screen.height;
                            document.getElementsByName('payu_browser[userAgent]')[0].value = navigator.userAgent;
                            document.getElementsByName('payu_browser[colorDepth]')[0].value = screen.colorDepth;
                            document.getElementsByName('payu_browser[language]')[0].value = navigator.language;
                            $(form).submit();
                        } else {
                            $(result.error.messages).each(function (i, error) {
                                var source = error.source || 'technical';
                                $('.payu-sf-' + error.type + '-error[data-type="' + source + '"]')
                                    .html(error.message)
                                    .slideDown(250);
                                $('html, body').animate({
                                    scrollTop: $('.card-container').offset().top
                                }, 300);
                            });
                        }
                    })
                    .catch(function (e) {
                        console.log(e);
                    });
            } catch (e) {
                console.log(e);
            }

            return false;
        }

        return true;
    }

    function show_error(){
        var errorMessage = document.querySelector('.payu-google-pay-error');
        if (errorMessage) {
            errorMessage.style.display = 'block';
        }
        $('html, body').animate({
            scrollTop: $('.payment_method_payugooglepay').offset().top
        }, 300);
        $('.payu-google-pay-error').slideDown(250);
    }

    function validate_payu_list_banks() {
        if (!$('.payu-list-banks').find('.payu-active .active').length) {
            $('html, body').animate({
                scrollTop: $('.payu-list-banks').offset().top
            }, 300);
            $('.pbl-error').slideDown(250);

            return false;
        } else {
            $('.pbl-error').slideUp(250);

            return true;
        }
    }

    function validate_payu_google_pay(form){
        $('.payu-google-pay-error').slideUp(250);
        if (!window.google?.payments?.api?.PaymentsClient) {
            show_error();
            return false;
        }

        var googleToken = document.getElementById('payu-google-token');
        if (googleToken.value === '') {

            const paymentsClient =
                new google.payments.api.PaymentsClient({environment: payuGooglePayConfig.env});

            const isReadyToPayRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [
                    {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                            allowedCardNetworks: ['MASTERCARD', 'VISA']
                        }
                    }
                ]
            }

            const paymentDataRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                merchantInfo: {
                    merchantName: payuGooglePayConfig.merchantName,
                    merchantId: payuGooglePayConfig.merchantId
                },
                allowedPaymentMethods: [
                {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                        allowedCardNetworks: ['MASTERCARD', 'VISA'],
                        billingAddressRequired: false
                    },
                    tokenizationSpecification: {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            gateway: 'payu',
                            gatewayMerchantId: payuGooglePayConfig.posId
                        }
                    }
                }
                ],
                transactionInfo: {
                    totalPriceStatus: 'FINAL',
                    countryCode: 'PL',
                    totalPrice: payuGooglePayConfig.totalPrice,
                    currencyCode: payuGooglePayConfig.currency
                }
            }
            paymentsClient.isReadyToPay(isReadyToPayRequest)
                .then(function(response) {
                    if (response.result) {
                        paymentsClient.loadPaymentData(paymentDataRequest).then(function(paymentData){
                            paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                            googleToken.value = btoa(paymentToken);
                            $(form).submit();
                        }).catch(function(err){
                            console.error(err);
                        });
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    show_error();
                });

            return false;
        }
    else return true;
    }
})(jQuery);
