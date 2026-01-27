(function ($) {
    const $document = $(document);

    $document.ready(function () {
        const $allowPasswordRegeneration = $('#allow-password-regenerate');

        $allowPasswordRegeneration.on('change', function () {
            const isChecked = $(this).is(':checked') ? 1: 0;

            booknetic.ajax('mobile_app_settings.save', {allow_staff_to_regenerate_app_password: isChecked}, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        });
    });
})(jQuery);