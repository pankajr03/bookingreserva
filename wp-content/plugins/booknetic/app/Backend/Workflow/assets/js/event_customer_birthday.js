(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        $('.fs-modal').on('click', '#eventSettingsSave', function ()
        {
            const gender			    = $("#input_gender").val();
            const years		            = $("#input_years").val();
            const months			    = $("#input_months").val();
            const offset_sign			= $("#input_offset_sign").val();
            const offset_value		    = $("#input_offset_value").val();
            const input_time			= $("#input_time").val();
            var categories     	        = $("#input_categories").val();


            const data = new FormData();

            data.append('id', currentWorkflowID);
            data.append('gender', gender);
            data.append('years', JSON.stringify( years ));
            data.append('months', JSON.stringify( months ));
            data.append('offset_sign', offset_sign);
            data.append('offset_value',offset_value);
            data.append('input_time', input_time );
            data.append('categories', JSON.stringify( categories ));

            booknetic.ajax( 'workflow_events.event_customer_birthday_changed_save', data, function()
            {
                booknetic.modalHide($(".fs-modal"));
            });
        });

        $('#input_months').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
        });
        $('#input_years').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
        });
        $('#input_gender').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
        });
        $('#input_time').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
        });
        booknetic.select2Ajax( $(".fs-modal #input_categories"), 'workflow_events.get_customer_categories');


    });

})(jQuery);
