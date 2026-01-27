(function ($)
{
    "use strict";

    $(document).ready(function ()
    {

        $('#booknetic_settings_area').on('click', '.settings-save-btn', function()
        {
            var gateway_enable_checkboxes = $('.bkntc_enable_payment_gateway'),
                gateways_statuses = {};

            gateway_enable_checkboxes.each(function( i, checkbox )
            {
                var key = $(checkbox).attr('data-slug');
                gateways_statuses[key] = $(checkbox).is(':checked') ? 'on' : 'off';
            });

            var	payment_gateways_order	        = [];

            $('.step_elements_list > .step_element').each(function()
            {
                payment_gateways_order.push( $(this).data('step-id') );
            });

            booknetic.ajax('settings.save_payment_split_payments_settings', {
                gateways_statuses   : gateways_statuses,
                payment_gateways_order: JSON.stringify( payment_gateways_order ),
            }, function ()
            {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });

        }).on('click', '.step_element:not(.selected_step)', function ()
        {
            $('.step_elements_list > .selected_step .drag_drop_helper > img').attr('src', assetsUrl + 'icons/drag-default.svg');

            $('.step_elements_list > .selected_step').removeClass('selected_step');
            $(this).addClass('selected_step');

            $(this).find('.drag_drop_helper > img').attr('src', assetsUrl + 'icons/drag-color.svg')

            var step_id = $(this).data('step-id');

            $('#booking_panel_settings_per_step > [data-step]').hide();
            $('#booking_panel_settings_per_step > [data-step="'+step_id+'"]').removeClass('hidden').show();
        });

        $( '.step_elements_list' ).sortable({
            placeholder: "step_element selected_step",
            axis: 'y',
            handle: ".drag_drop_helper"
        });

        $('.step_elements_list > .step_element:eq(0)').trigger('click');

    });

})(jQuery);