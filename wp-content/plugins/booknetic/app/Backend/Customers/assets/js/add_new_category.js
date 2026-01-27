(function ($) {
    "use strict";

    let is_default = 0;

    $(document).ready(function () {
        let current_modal = $('#addCustomerCategorySave').closest('.fs-modal');

        $(".input_color").colorpicker({
            'format': 'hex'
        });

        $("#input_is_default").on('click', function () {
            if ($(this).is(':checked')) {
                $("#uncategorized-customers").removeClass('d-none');
            } else {
                $("#uncategorized-customers").addClass('d-none');
            }
        });


        $('.fs-modal').on('click', '#addCustomerCategorySave', function () {

            let name = $("#input_name").val();
            let color = $("#input_color").val();
            let icon = $("#input_icon").val();
            let note = $("#input_note").val();
            let isDefault =  $("#input_is_default").is(':checked') ? 1 : 0;
            let applyToUncategorizedCustomers =  $("#default_for_new_customers").is(':checked') ? 1 : 0;

            let run_workflows = $("#input_run_workflows").is(':checked') ? 1 : 0;

            const id = $("#add_new_JS").data('customer-category-id');

            let data = new FormData();

            data.append('id', $('#add_new_JS').data('customer-category-id'));

            data.append('name', name);
            data.append('color', color);
            data.append('icon', icon);
            data.append('note', note);
            data.append('isDefault', isDefault);
            data.append('applyToUncategorizedCustomers', applyToUncategorizedCustomers);

            data.append('run_workflows', run_workflows);

            let ajaxUrl;

            if (!id) {
                ajaxUrl = 'customer_categories.create';
            } else {
                ajaxUrl = 'customer_categories.update';
            }

            booknetic.ajax(ajaxUrl, data, function ($result) {

                booknetic.modalHide(current_modal);

                let $fsTableDiv = $("#fs_data_table_div");

                if ($fsTableDiv.length) {
                    booknetic.dataTable.reload($fsTableDiv);
                }
            });
        })


    });

})(jQuery);