(function ($) {
    const $document = $(document);

    $document.ready(function () {

        $(document).on('click', '.member-dropdown-btn', function(e) {
            e.stopPropagation();

            const $dropdown = $('.member-dropdown');
            const rect = $(this)[0].getBoundingClientRect();

            $dropdown.css({
                position: 'absolute',
                top: rect.bottom + window.scrollY,
                left: rect.right - $dropdown.outerWidth() + window.scrollX,
                zIndex: 9999
            });

            $dropdown.data('seat-id', $(this).closest('.member').data('seat-id'));
            $dropdown.data('username', $(this).closest('.member').data('username'));
            $dropdown.toggle();
        });

        const $scrollContainer = $('.mobile-app-view-area');

        $scrollContainer.on('scroll', function() {
            $('.member-dropdown').hide();
        });

        $document.on('click', function (e) {
            if (!$(e.target).closest('.member-status').length) {
                $('.member-dropdown').hide();
            }
        });

        $document.on('click', '.regenerate-password-btn', function () {
            $('.member-dropdown').hide();
            const seatId = $(this).closest('.member-dropdown').data('seat-id');
            const username = $(this).closest('.member-dropdown').data('username');
            const $modal = $('.regenerate-password-modal');

            $modal.data('username', username);
            $modal.data('seat-id', seatId);
            booknetic.newModal.open('.regenerate-password-modal');
        });

        $document.on('click', '.unassign-btn', function () {
            $('.member-dropdown').hide();
            const seatId = $(this).closest('.member-dropdown').data('seat-id');
            const $modal = $('.unassign-modal');

            $modal.data('seat-id', seatId);
            booknetic.newModal.open('.unassign-modal');
        });

        $document.on('click', '.log-out-btn', function () {
            const seatId = $(this).closest('.member').data('seat-id');
            const $modal = $('.log-out-modal');

            $modal.data('seat-id', seatId);
            booknetic.newModal.open('.log-out-modal');
        });

        $(document).on('click', '.add-members-btn', function () {
            booknetic.ajax('mobile_app_seat.hasAvailableSeat', {}, (result) => {
                if (result.status_response === false) {
                    booknetic.newModal.open('.no-user-available');
                    return
                }

                $('#select-user').select2({
                    theme: 'bootstrap',
                    placeholder: booknetic.__('Select user'),
                    allowClear: false,
                });

                booknetic.select2Ajax($("#select-user"), 'mobile_app_seat.assign_user_modal');

                booknetic.newModal.open('.add-users-modal');
            });
        });

        $document.on('click', '.modal-confirm', function () {
            const $modal = $(this).closest('.booknetic-modal');
            const $regenerateModal = $(this).closest('.regenerate-password-modal');

            let username = $regenerateModal.data('username');
            let regenerateSeatId = $regenerateModal.data('seat-id');
            let logoutSeatId = $('.log-out-modal').data('seat-id');
            let unassignSeatId = $('.unassign-modal').data('seat-id');

            let seatId = logoutSeatId ? logoutSeatId : unassignSeatId;

            if ($modal.hasClass('regenerate-password-modal')) {
                booknetic.newModal.close();
                return regeneratePasswordAjax(username, regenerateSeatId);
            }

            if ($modal.hasClass('unassign-modal')) {
                booknetic.newModal.close();
                return unassignAjax(seatId);
            }

            if ($modal.hasClass('log-out-modal')) {
                booknetic.newModal.close();
                return logOutAjax(seatId);
            }
        });

        $document.on('click', '.assign-user-confirm', function () {
            const selectedUserId = $('#select-user').val().trim();

            if (!selectedUserId) {
                return booknetic.toast(booknetic.__('Please select a user'), 'unsuccess');
            }

            const formData = new FormData();
            formData.append('id', Number(selectedUserId));

            booknetic.ajax('mobile_app_seat.assign', formData, (response) => {
                $('.username-credential').text(response.data.username);
                $('.password-credential').text(response.data.password);
                $('.add-users-modal').hide();
                booknetic.newModal.open('.seat-credentials-modal');
            });
        });

        $document.on('click', '.modal-cancel, .modal-close-btn, #modal-overlay', function (e) {
            const $modal = $('.booknetic-modal.is-open');

            if ($(e.target).is('#modal-overlay') || $(e.target).closest('.modal-cancel, .modal-close-btn').length) {
                if ($modal.hasClass('seat-credentials-modal')) {
                    location.reload();
                }
            }
        });

        function unassignAjax(seatId) {
            const data = new FormData();
            data.append('seatId', seatId);

            booknetic.ajax('mobile_app_seat.unassign', data, () => {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
                location.reload();
            });
        }

        function regeneratePasswordAjax(username, seatId) {
            const data = new FormData();
            data.append('username', username);
            data.append('seatId', seatId);

            booknetic.ajax('mobile_app_seat.regenerate_password', data, (result) => {
                $('.username-credential').text(result.username);
                $('.password-credential').text(result.app_password);

                booknetic.newModal.open('.seat-credentials-modal');
            });
        }

        function logOutAjax(seatId) {
            const data = new FormData();
            data.append('seatId', seatId);

            booknetic.ajax('mobile_app_seat.logout', data, () => {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
                location.reload();
            });
        }

    });
})(jQuery);