(function ($)
{
	"use strict";

	$(document).ready(function ()
	{

		$('#booknetic_settings_area').on('click', '.settings-save-btn', function()
		{
			let sign_in_page	                = $("#input_sign_in_page").val(),
				sign_up_page	                = $("#input_sign_up_page").val(),
				booking_page	                = $("#input_booking_page").val(),
				forgot_password_page	        = $("#input_forgot_password_page").val(),
				change_status_page_id			= $("#input_change_status_page_id").val(),
				regular_sign_in_page			= $("#input_regular_sign_in_page").val(),
				regular_sign_up_page			= $("#input_regular_sign_up_page").val(),
				regular_forgot_password_page    = $("#input_regular_forgot_password_page").val();

			booknetic.ajax('save_page_settings', {
				sign_in_page: sign_in_page,
				sign_up_page: sign_up_page,
				booking_page: booking_page,
				forgot_password_page: forgot_password_page,
				change_status_page_id: change_status_page_id,
				regular_sing_in_page: regular_sign_in_page,
				regular_sign_up_page: regular_sign_up_page,
				regular_forgot_password_page: regular_forgot_password_page
			}, function ()
			{
				booknetic.toast(booknetic.__('saved_successfully'), 'success');
			});

		});

		$("#input_sign_in_page, #input_sign_up_page, #input_booking_page, #input_forgot_password_page, #input_change_status_page_id, #input_regular_sign_in_page, #input_regular_sign_up_page, #input_regular_forgot_password_page").select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true
		});

	});

})(jQuery);
