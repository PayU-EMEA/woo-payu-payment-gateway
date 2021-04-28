jQuery('body').on('click', '.payu-list-banks li.payu-active label', function () {
    jQuery('.payu-list-banks label').removeClass('active');
    jQuery(this).addClass('active');
});

(function ($) {
    $('body').on('click', '.payu-list-banks .payu-active', function () {
        $('.pbl-error').slideUp(250);
    });

    $('body').on('click', '.payu-conditions-description .payu-read-more', function () {
        $(this).next('.payu-more-hidden').slideDown(250);
        $(this).remove();
    });

    $('form#order_review').on('submit', function (e) {
        var validateResult = validate_payu_checkout('form#order_review', e);

        if (!validateResult) {
            setTimeout(function () {
                $('form#order_review').unblock();
            }, 1000);
        }

        return validateResult;
    });

    $('form.checkout').on('checkout_place_order', function (e) {
        return validate_payu_checkout('form.checkout', e);
    });

    function validate_payu_checkout(form, e) {
        var $payment_method = $(form).find('input[name="payment_method"]:checked').val();

        if ($payment_method === 'payusecureform') {
            if ($('#response-tokenize').val() === '') {
                try {
                    window.payuSdkForms.tokenize()
                        .then(function (result) {
                            $('.payu-sf-validation-error, .payu-sf-technical-error')
                                .html('')
                                .slideUp(250);
                            if (result.status === 'SUCCESS') {
                                $('#response-tokenize').val(result.body.token);
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
            } else {
                return true;
            }
        } else if ($payment_method === 'payulistbanks') {
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
        return true;
    }

})(jQuery);
