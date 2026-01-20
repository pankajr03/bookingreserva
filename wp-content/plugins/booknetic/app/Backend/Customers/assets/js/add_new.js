(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		let current_modal = $('#addCustomerSave').closest('.fs-modal');

		$('.fs-modal').on('click', '#addCustomerSave', function ()
		{
			let iti 						= $("#input_phone").data('iti');
			let wp_user	                    = $("#input_wp_user").val();
			let	first_name	                = $("#input_first_name").val();
			let	last_name	                = $("#input_last_name").val();
			let	gender		                = $("#input_gender").val();
			let	birthday	                = $("#input_birthday").val();
			let	phone		                = iti.getNumber(bookneticIntlTelInput.utils.numberFormat.E164);
			let	email		                = $("#input_email").val();
			let	allow_customer_to_login	    = $("#input_allow_customer_to_login").is(':checked') ? 1 : 0;
			let	wp_user_use_existing	    = $("#input_wp_user_use_existing").val();
			let	wp_user_password		    = $("#input_wp_user_password").val();
			let	categoryId		            = $("#input_category_id").val();
			let	note		                = $("#input_note").val();
			let	image		                = $("#input_image")[0].files[0];
			let	run_workflows	    = $("#input_run_workflows").is(':checked') ? 1 : 0;

			const id = $("#add_new_JS").data('customer-id');

            const isPhoneValid = iti.isValidNumber();

            if(phone.length > 0 && !isPhoneValid) {
                booknetic.toast(booknetic.__('phone_is_not_valid'), "unsuccess");
                return
            }

			let data = new FormData();

			data.append('id', $('#add_new_JS').data('customer-id'));
			if ( allow_customer_to_login && wp_user_use_existing === 'yes' )
			{
				data.append('wp_user', wp_user);
			}
			data.append('first_name', first_name);
			data.append('last_name', last_name);
			data.append('gender', gender);
			data.append('birthday', birthday);
			data.append('phone', phone);
			data.append('email', email);
			data.append('allow_customer_to_login', allow_customer_to_login);
			data.append('wp_user_use_existing', wp_user_use_existing);
			data.append('wp_user_password', wp_user_password);
			data.append('categoryId', categoryId);
			data.append('note', note);
			data.append('image', image);
			data.append('extras', JSON.stringify(booknetic.doFilter('customers.save', [] )));
			data.append('run_workflows', run_workflows);

			let ajaxUrl;

			if ( !id ) {
				ajaxUrl = 'customers.create';
			} else {
				ajaxUrl = 'customers.update';
			}

			booknetic.ajax(ajaxUrl, data, function( $result )
			{
				const customer_id = $result[ 'customer_id' ];
				const new_customer = new Option( first_name + ' ' + last_name, customer_id, false, false );

				$(".input_customer").append( new_customer ).trigger( 'change' ).val( customer_id );

				booknetic.modalHide( current_modal );

				let $fsTableDiv = $("#fs_data_table_div");

				if( $fsTableDiv.length )
				{
					booknetic.dataTable.reload( $fsTableDiv );
				}
			});
		}).on('change', '#input_allow_customer_to_login', function ()
		{
			if( $(this).is(':checked') )
			{
				$('[data-hide="allow_customer_to_login"]').slideDown(200);
				$('#input_wp_user_use_existing').trigger('change');
			}
			else
			{
				$('[data-hide="allow_customer_to_login"]').slideUp(200);
				$('[data-hide="existing_user"]').slideUp(200);
				$('[data-hide="create_password"]').slideUp(200);
				$('#input_email').removeAttr('readonly');
			}
		}).on('change', '#input_wp_user_use_existing', function ()
		{
			if( $(this).val() === 'yes' )
			{
				$('[data-hide="existing_user"]').show();
				$('[data-hide="create_password"]').hide();
				$('#input_email').attr('readonly',true);
			}
			else
			{
				$('[data-hide="existing_user"]').hide();
				$('[data-hide="create_password"]').show();
				$('#input_email').removeAttr('readonly');
			}
		}).on('change' , '#input_wp_user' , function ()
		{
			booknetic.ajax('getWpUserData', {id: $(this).val()}, function (result) {
				const email = $('#input_email');
				const firstName = $('#input_first_name');
				const lastName = $('#input_last_name');
				email.attr('readonly',true);
				email.val( result.email );
				firstName.val( result.firstName );
				lastName.val( result.lastName );
			});
		})

		$('#input_wp_user_use_existing').trigger('change');
		$('#input_allow_customer_to_login').trigger('change');

		let phone_input = $('#input_phone');

        phone_input.data('iti', window.bookneticIntlTelInput(phone_input[0], {
            loadUtilsOnInit: telInputAssetUrl,
            initialCountry: phone_input.data('country-code'),
            separateDialCode: true,
        }));

		let birthday = $("#input_birthday");

		let date_format_js = birthday.data('date-format').replace('Y','yyyy').replace('m','mm').replace('d','dd');
		birthday.datepicker({
			autoclose: true,
			format: date_format_js,
			weekStart: weekStartsOn === 'sunday' ? 0 : 1
		});

		$('#input_wp_user, #input_gender').select2({
			theme: 'bootstrap',
			placeholder: booknetic.__('select'),
			allowClear: true
		});
	});

})(jQuery);