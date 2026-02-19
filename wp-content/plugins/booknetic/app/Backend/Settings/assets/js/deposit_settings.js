(function ($) {
    "use strict";

    $(document).on('change', '#isEnabled', function () {
        if ($(this).is(':checked')) {
            $('.deposit-settings').slideDown(200);
        } else {
            $('.deposit-settings').slideUp(200);
        }
    });

    $(document).ready(function () {

        bookneticSettings.setOnSave(function () {
            const deposit_enabled = $('#isEnabled').is(':checked') ? 1 : 0;
            const deposit_value = $('#depositValue').val();
            const deposit_type = $('#depositType').val();

            booknetic.ajax('save_deposit_settings', {
                deposit_enabled,
                deposit_value,
                deposit_type
            }, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        });
    });
})(jQuery);
