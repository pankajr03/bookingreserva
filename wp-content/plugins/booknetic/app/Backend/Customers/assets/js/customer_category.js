(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		$(document).on('click', '#addBtn', function ()
		{
			booknetic.loadModal('add_new', {});
		}).on('click', '#importBtn', function ()
		{
			booknetic.loadModal('import', {}, {'type': 'center', 'width': '650px'});
		});

		booknetic.dataTable.actionCallbacks['info'] = function (ids)
		{
			booknetic.loadModal('info', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['edit'] = function (ids)
		{
			booknetic.loadModal('add_new', {'id': ids[0]});
		}

		booknetic.dataTable.actionCallbacks['delete'] = function (ids)
		{
			booknetic.confirm([ booknetic.__('are_you_sure_want_to_delete'), booknetic.__('customer_category_delete_desc')], 'danger', 'trash', function()
			{
                booknetic.dataTable.doAction('delete', ids, {}, function ()
				{
					booknetic.toast(booknetic.__('Deleted'), 'success', 2000);
				});
			});
		}
	});

})(jQuery);