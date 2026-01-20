var bookneticSettings = {
    onSave: () => {},
    setOnSave: function (callback) {
        this.onSave = callback
    }
};

(function ($) {
    "use strict";

    const $document = $(document);

    $document.ready(function () {
        const $header = $('.m_header');
        const settingsSaveButton = $('.settings-save-btn');

        settingsSaveButton.on('click', function () {
            bookneticSettings.onSave();
        });

        $('.setting-sub-menu').hide();

        loadDetailsView();

        $('.setting-menu-header').on('click', function () {
            const $submenu = $(this).siblings('.setting-sub-menu');
            const $arrow = $(this).find('svg');

            if ($submenu.length) {
                $submenu.slideToggle(200);
                $arrow.toggleClass('rotate-180');
            }
        });

        $document.on('click', '.load-setting-view', function () {
            const $clicked = $(this);
            const view = $clicked.data('view');

            const params = new URLSearchParams(window.location.search);
            params.set("view", view);
            const newUrl = window.location.pathname + "?" + params.toString() + window.location.hash;
            history.replaceState(null, "", newUrl);

            booknetic.ajax(view, {}, function (result) {
                renderSettingDetails($clicked.text());
                $('.settings-details-content').html(booknetic.htmlspecialchars_decode(result['html']));
            });
        });

        $document.on("click", ".setting-sub-menu-title, .settings-category-card-submenu-item", function () {
            const view = $(this).data("view");
            setActive(view);
        });

        function setActive(view) {
            $(".setting-sub-menu-title").removeClass('active');
            $(".setting-menu-title").removeClass('active');

            const $targetSubMenuTitle = $(`.setting-sub-menu-title[data-view='${view}']`);
            $targetSubMenuTitle.addClass("active");

            const $settingsMenu = $targetSubMenuTitle.closest(".settings-menu");
            $settingsMenu.find(".setting-menu-title").addClass("active");

            const $submenu = $targetSubMenuTitle.closest(".setting-sub-menu");
            $submenu.show();
            $settingsMenu.find(".setting-menu-header svg").addClass("rotate-180");
        }

        function loadDetailsView() {
            const params = new URLSearchParams(window.location.search);
            const urlView = params.get("view");

            if (!urlView) return;

            const subMenuText = $(`.setting-sub-menu-title[data-view='${urlView}']`).text();
            renderSettingDetails(subMenuText);
            setActive(urlView);

            booknetic.ajax(urlView, {}, result => {
                $('.settings-details-content').html(booknetic.htmlspecialchars_decode(result.html));
            });
        }

        function renderSettingDetails(subMenuText) {
            $('.setting-detail-wrapper').fadeIn(300);
            $('.settings-main-menu').hide();
            $('.current-setting-sub-menu').text(subMenuText);
            $header.hide();
        }
    });

})(jQuery);