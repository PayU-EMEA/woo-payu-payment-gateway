(function ($) {
    $(document).ready(function () {
        if ($('.form-table input[data-global="can-be-global"]').length) {
            $('.form-table').addClass('payu-table');
            $('.form-table .forminp').each(function () {
                if ($(this).find('input[data-global="can-be-global"]').length) {
                    $(this).addClass('absolute-global');
                }
            });
            test_values_type();
        }
        $('input[data-toggle-global]').on('click', function () {
            test_values_type();
        });
    });

    function test_values_type() {
        if ($('input[data-toggle-global]').prop('checked') == true) {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', true);
                var global_value = $(this).attr('global-value');
                $('<span>' + global_value + '</span>').insertAfter($(this));
            });
        } else {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', false);
                $(this).next('span').remove();
            });
        }
    }
})(jQuery);

