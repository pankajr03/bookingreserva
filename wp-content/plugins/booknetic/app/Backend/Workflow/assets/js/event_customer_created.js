(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.select2Ajax( $(".fs-modal #input_categories"), 'workflow_events.get_customer_categories');


        $('.fs-modal').on('click', '#eventSettingsSave', function ()
        {
            var locale			    = $("#input_locale").val();
            var categories     	    = $("#input_categories").val();

            var data = new FormData();

            data.append('id', currentWorkflowID);
            data.append('locale', locale);
            data.append('categories', JSON.stringify( categories ));

            booknetic.ajax( 'workflow_events.event_customer_created_save', data, function()
            {
                booknetic.modalHide($(".fs-modal"));
            });
        });

    });

})(jQuery);