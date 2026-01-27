(function ($)
{
	"use strict";

	const doc = $(document);

	doc.ready(function () {
		doc.on('click', '.boostore-notification-body', function () {
			const slug = $(this).closest('.boostore-notification-container').data('slug');
			const url = new URL(window.location.href);

			url.searchParams.set('module', 'boostore');
			url.searchParams.set('action', 'details');
			url.searchParams.set('slug', slug);

			window.location.href = url.href;
		})
			.on('click', '.boostore-notification-close', function () {
				const container = $(this).closest('.boostore-notification-container');

				booknetic.ajax('dismiss_notification', {slug: container.data('slug')}, () => {
					container.remove()
				})
			});
	});

})(jQuery);