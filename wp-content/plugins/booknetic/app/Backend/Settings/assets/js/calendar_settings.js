(function ($) {
    const $document = $(document);

    $document.ready(function () {
        const $eventColor = $('#event-color');
        const $eventContent = $('#event-content');
        const $enableCustomCalendarCardContent = $('#enableCustomCalendarCardContent');

        booknetic.summernote(
            $eventContent,
            [
                ['style', ['style']],
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']],
                ['height', ['height']],
            ],
            eventContentShortCodesObject
        );

        $eventColor.select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('Select color'),
        });

        bookneticSettings.setOnSave(function () {
            const eventContent = booknetic.summernoteReplace($eventContent, true);
            const plainText = $('<div>').html(eventContent).text().trim();

            if (plainText.length === 0 && $enableCustomCalendarCardContent.is(':checked')) {
                booknetic.toast(booknetic.__('Please enter event content'), 'unsuccess');
                return;
            }

            const params = {
                appointmentCardColor: $eventColor.val(),
                eventContent,
                enableCustomCalendarCardContent: $enableCustomCalendarCardContent.is(':checked'),
            }

            booknetic.doFilter('calendar_settings.save_google');
            booknetic.doFilter('calendar_settings.save_outlook');

            booknetic.ajax('save_calendar_settings', params, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });
        });

        $('#calendar-settings').on('change', '#enableCustomCalendarCardContent', function () {
            $('#customCalendarCardContentContainer').fadeToggle();
        });
    });

})(jQuery);