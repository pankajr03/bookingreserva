(function ($) {
    "use strict";

    $(document).ready(function () {

        booknetic.initMultilangInput($('input#new_category_name'), 'service_categories', 'name');

        $(".fs-modal #input_employees, .fs-modal #input_parent_category").select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            allowClear: true
        });

        $('#save_new_category').on('click', function () {

            const id = $("#add_new_JS").data('category-id');
            let name = $(".fs-modal #new_category_name").val();
            let parent_id = $(".fs-modal #input_parent_category").val();

            let formData = new FormData();
            formData.append("id", id);
            formData.append("name", name);
            formData.append("parent_id", parent_id);

            let url;

            if (id > 0) {
                url = 'service_categories.update';
            } else {
                url = 'service_categories.create';
            }

            booknetic.ajax(url, formData, function () {

                booknetic.toast(booknetic.__('saved_successfully'), 'success');
                booknetic.modalHide($(".fs-modal"));

                const fsTableDiv = $("#fs_data_table_div");

                if (fsTableDiv.length) {
                    booknetic.dataTable.reload(fsTableDiv);
                }

            });
        });

    });
})(jQuery);
