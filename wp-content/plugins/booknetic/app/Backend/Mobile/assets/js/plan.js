(function ($) {
    const $document = $(document);

    $document.ready(function () {
        let checkedPlan = null;

        const updateRangeFill = (el) => {
            const percent = ((el.value - el.min) / (el.max - el.min)) * 100;
            el.style.setProperty('--val', `${percent}%`);

            $(el).closest('.plan-card').find('.additional-seat').val(el.value);
        };

        $document.on('input change', 'input[type="range"]', function () {
            updateRangeFill(this);
        });

        $document.on('input', '.additional-seat', function () {
            let val = this.value.replace(/\D+/g, '');
            if (val === '') val = 0;

            const $card = $(this).closest('.plan-card');
            const $range = $card.find('input[type="range"]')[0];

            const min = parseInt($range.min);
            const max = parseInt($range.max);

            const clamped = Math.min(Math.max(parseInt(val), min), max);

            this.value = val;
            $range.value = clamped;
            updateRangeFill($range);
        });

        $document.on('click', '.subscribe-plan-btn', function () {
            checkedPlan = this;
            const planId = $(this).closest('.plan-card').data('plan-id');
            const additionalSeatCount = $(this).closest('.plan-card').find('.additional-seat').val();

            const params = {
                plan_id: planId,
                additional_seat_count: additionalSeatCount
            }

            booknetic.ajax('mobile_app_subscription.subscribe', params, (result) => {
                const {payment_link} = result;

                if (payment_link) {
                    const popup = window.open(payment_link, '_blank', 'width=900,height=600');

                    const handleMessage = (event) => {
                        if (event.data && event.data.type === 'checkout-completed') {
                            booknetic.newModal.open('.payment-success-modal');
                            checkedPlan = null;
                            window.removeEventListener('message', handleMessage);

                            if (popup && !popup.closed) popup.close();
                        }

                        if (event.data && event.data.type === 'checkout-error') {
                            booknetic.newModal.open('.payment-error-modal');
                            window.removeEventListener('message', handleMessage);

                            if (popup && !popup.closed) popup.close();
                        }
                    };

                    window.addEventListener('message', handleMessage);
                }
            });
        });

        $document.on('click', '.payment-error-try-again-btn', function (){
            booknetic.newModal.close();
            if (checkedPlan) $(checkedPlan).trigger('click');
        });

        $document.on('click', '.modal-cancel, .modal-close-btn, #modal-overlay', function (e) {
            const $modal = $('.booknetic-modal.is-open');

            if ($(e.target).is('#modal-overlay') || $(e.target).closest('.modal-cancel, .modal-close-btn').length) {
                if ($modal.hasClass('payment-success-modal')) {
                   location.reload();
                }
            }
        });
    });
})(jQuery);