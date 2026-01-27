(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('add_new', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['billing_history'] = function (ids)
		{
			location.href = '?page=booknetic-saas&module=payments&tenant_id=' + ids[0];
		}

		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		});

		var js_parameters = $('#tenant-js12394610');

		if( js_parameters.data('edit') > 0 )
		{
			booknetic.loadModal('add_new', {'id': js_parameters.data('edit')});
		}

		$(document).on('click', '.resend_activation_email_btn', function ()
		{
			let tenantId = $(this).closest('tr').data('id');
			booknetic.confirm(booknetic.__('are_you_sure_resend'), 'warning', 'updates', function ()
			{
				booknetic.ajax('resend_activation_code', { id: tenantId }, function ( result )
				{
					booknetic.toast( booknetic.__('activation_sent_success'), 'success' );
				});
			}, booknetic.__('resend'));
		});

	});

})(jQuery);