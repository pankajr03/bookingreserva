(($) => {
    const doc = $(document);

    doc.ready(() => {
        doc.on("click", ".purchase-crack-addons", function () {
            booknetic.ajax('boostore.purchase_crack_addons', {}, () => {
                location.href = '?page=' + BACKEND_SLUG + '&module=cart';
            })
        });

    });
})(jQuery)