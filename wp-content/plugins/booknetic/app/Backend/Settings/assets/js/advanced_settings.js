(function ($) {
    "use strict";

    $(document).ready(function () {

        bookneticSettings.setOnSave(function () {

            const time_priority     = $('#time_priority').val();
            const flexible_timeslot = $('#input_flexible_timeslot').val();

            booknetic.ajax('save_advanced_settings', {
                flexible_timeslot,
                time_priority,
            }, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });

        });

    });

})(jQuery);
