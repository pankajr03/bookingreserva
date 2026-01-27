(function ($) {
    const $document = $(document);

    $document.ready(function () {

        $document.on('click', '.copy-btn', function () {
            const $btn = $(this);
            const $text = $btn.find('span');
            const original = $text.text();
            const password = $btn.closest('.credential-input').find('span').first().text().trim();

            navigator.clipboard.writeText(password).then(() => {
                $text.text('Copied');

                setTimeout(() => {
                    $text.text(original);
                }, 1500);
            });
        });

        $document.on('click', '.manage-seats-btn', function () {
            booknetic.ajax('mobile_app_seat.manage_seats_modal', {}, function (result) {
                booknetic.newModal.open('.manage-seats-modal');
            });
        });

        $document.on('input', '.additional-seat-input', function () {
            const $input = $(this);
            const max = parseInt($input.attr('max'));
            let val = parseInt($input.val());

            if (isNaN(val) || val < 0) {
                val = 0;
                $input.val(val);
            }

            if (!isNaN(max) && val > max) {
                val = max;
                $input.val(val);
            }

            getNewBilling(val);
        });

        $document.on('click', '.increment-btn', function () {
            if ($(this).attr('disabled')) {
                return;
            }

            const $input = $(this).siblings('input.additional-seat-input');
            const max = parseInt($input.attr('max'));
            let currentVal = parseInt($input.val());

            if (isNaN(currentVal)) currentVal = 0;

            if (!isNaN(max) && currentVal >= max) {
                booknetic.toast(booknetic.__('max_seat_reached'), 'unsuccess');
                return; // Already at max
            }

            const newVal = currentVal + 1;
            $input.val(newVal);

            getNewBilling(newVal);
        });

        $document.on('click', '.decrement-btn', function () {
            if ($(this).attr('disabled')) {
                return;
            }

            const $input = $(this).siblings('input.additional-seat-input');
            let currentVal = parseInt($input.val());

            if (isNaN(currentVal)) currentVal = 0;

            if (currentVal <= 0) {
                return; // Already at min
            }

            const newVal = currentVal - 1;
            $input.val(newVal);

            getNewBilling(newVal);
        });

        $document.on('click', '.pay-and-activate-btn', function () {
            if ($(this).attr('disabled')) {
                return;
            }

            const data = new FormData();
            data.append('additional_seat_count', $('.additional-seat-input').val());

            booknetic.ajax('mobile_app_seat.manage_seats', data, function (result) {
                booknetic.newModal.close();
            });
        });

        const getNewBilling = booknetic.debounce(function (seatCount) {
            const data = new FormData();

            data.append('additional_seat_count', seatCount);

            booknetic.ajax('mobile_app_seat.update_seat_preview', data, function (result) {
                $('.next-billing-payment').text(`${result.result.nextBillingAmount} ${result.result.currency}`);
                $('.subtotal').text(`${result.result.dueToday} ${result.result.currency}`)
            });
        }, 300);
    });

})(jQuery);