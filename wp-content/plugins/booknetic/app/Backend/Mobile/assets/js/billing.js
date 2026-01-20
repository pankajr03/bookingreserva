(function ($) {
    const $document = $(document);

    $document.ready(function () {
        $document.on('click', '.cancel-subscription-btn',  () => openModal('.payment-cancel-subscription'));

        $document.on('click', '.undo-subscription-btn', () => {
            booknetic.ajax('mobile_app_subscription.undoCancellation', {}, () => {
                location.reload();
            });
        });

        $document.on('click', '.modal-confirm', function () {
            const $modal = $(this).closest('.booknetic-modal');

            booknetic.newModal.close();

            if ($modal.hasClass('payment-cancel-subscription')) {
                booknetic.ajax('mobile_app_subscription.cancel', {}, function () {
                    location.reload();
                });
            }
        });
    });
})(jQuery);