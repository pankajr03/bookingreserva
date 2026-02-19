(function ($) {
  "use strict";

  $(document).ready(() => {
    const notificationButton = $(".booknetic_notification_button");
    const notificationDropDown = $(".booknetic_notification_panel");
    const notificationBadge = $(".notification-badge");
    const notificationCount = $(".notification_panel_header-count");
    const markAllAsRead = $(".notification_panel_clear_btn");
    const carouselWrapper = $(".notification-carousel-wrapper");
    const notificationCards = $(".notification-cards");
    const emptyState = $(".notification-empty-state");
    const notificationBody = $(".notification-body");

    let notificationsCount = 0;
    let notificationPollInterval = null;

    let slideIndex = 1;
    let scrollIntervalID = null;

    // Infinite scroll
    let currentPage = 1;
    let isLoading = false;
    let hasMorePages = true;

    const closeNotification = (event) => {
      if (!$(event.target).closest(".booknetic_notification_area").length) {
        notificationDropDown.fadeOut("fast");
        $(document).off("click", closeNotification);
        stopCarousel();
      }
    };

    notificationButton.on("click", (event) => {
      notificationDropDown.fadeToggle("fast");
      $(document).one("click", closeNotification);
      $(".booknetic_help_center_dropdown").fadeOut("fast");
      startCarousel();
      event.stopPropagation();
    });

    const updateCarousel = () => {
      const $items = $('.notification-carousel-item:visible');
      const slideCount = $items.length;
      if (slideCount === 0) return;

      const slideWidth = $items.outerWidth() || 0;

      slideIndex = (slideIndex + 1) % slideCount;
      const distance =
        slideIndex > 0
          ? "+=" + slideIndex * slideWidth
          : "-=" + slideWidth * slideCount;

      carouselWrapper.animate(
        {
          scrollLeft: distance,
        },
        "slow"
      );
    };

    const startCarousel = () => {
      if (!scrollIntervalID) {
        scrollIntervalID = setInterval(updateCarousel, 5000);
      }
    };

    const stopCarousel = () => {
      if (scrollIntervalID) {
        clearInterval(scrollIntervalID);
        scrollIntervalID = null;
      }
    };

    const getNotifications = (page = 1) =>
        booknetic.ajaxRest('notifications', 'GET', { page, rows_count: 10 }, { noLoading: true });

    const renderNotifications = (notifications, append = false) => {
      if (!append) {
        notificationCards.empty();
      }

      notifications.forEach((n) => {
        const isUnread = !n.read_at;

        const cardHtml = `
          <div class="notification-card ${isUnread ? "unread-notification" : ""}" data-id="${n.id}" data-action='${n.action_data ?? ""}'>
            <div class="notification-card_header">
              <p>
                <img src="${assetsUrl}/icons/update_icon.svg" alt="bell icon"/>
                <span>${n.title}</span>
              </p>
              <span class="d-flex align-items-center notification-time">
                ${booknetic.timeAgo(n.created_at)}
                <div class="notification_time_badge ml-2 ${isUnread ? "d-inline" : "d-none"}"></div>
              </span>
            </div>
            <div class="notification-content">${n.message}</div>
            <a href="">
              ${booknetic.__('learn_more')}
              <span class="learn_more_icon">
                <img src="${assetsUrl}/icons/learn_more.svg" alt="Learn more"/>
              </span>
            </a>
          </div>
        `;

        notificationCards.append(cardHtml);
      });
    };

    const updateEmptyState = () => {
      const remainingAddons = $('.notification-carousel-item').length;

      if (notificationsCount === 0 && remainingAddons === 0) {
          notificationCards.hide();
          carouselWrapper.hide();
          emptyState.show();
      } else {
          emptyState.hide();
          notificationCards.show();
          if (remainingAddons > 0) {
            carouselWrapper.show();
          }
      }
    };

    const updateCounts = () => {
      const addonCount = $('.notification-carousel-item:visible').length;
      const total = notificationsCount + addonCount;
      const unreadCount = $('.notification-card.unread-notification').length;

      notificationCount.text(`(${total})`);

      if (unreadCount > 0) {
        notificationBadge.show();
        markAllAsRead.show();
      } else {
        notificationBadge.hide();
        markAllAsRead.hide();
      }
    };

    const fetchNotifications = (page = 1, append = false) => {
      if (isLoading) return;

      isLoading = true;

      getNotifications(page)
          .then((response) => {
            const notifications = response.notifications || [];
            notificationsCount = response.count || 0;

            if (!append) {
              currentPage = 1;
              hasMorePages = notifications.length === 10;
            } else {
              hasMorePages = notifications.length === 10;
            }

            renderNotifications(notifications, append);
            updateCounts();
            updateEmptyState();

            isLoading = false;
          })
          .catch((e) => {
            isLoading = false;
          });
    };

    notificationBody.on('scroll', function() {
      if (!hasMorePages || isLoading) return;

      const scrollTop = $(this).scrollTop();
      const scrollHeight = $(this)[0].scrollHeight;
      const clientHeight = $(this).outerHeight();

      if (scrollTop + clientHeight >= scrollHeight * 0.8) {
        currentPage++;
        fetchNotifications(currentPage, true);
      }
    });

    fetchNotifications();

    $(document).on("click", ".notification-card, .notification-card a", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const card = $(this).closest(".notification-card");
      const isUnread = card.hasClass("unread-notification");
      const notification_id = card.attr("data-id");

      let actionData = {};
      try {
        actionData = JSON.parse(card.attr("data-action"));
      } catch (e) {}

      const open = () => {
        if (!actionData.url) return;

        booknetic.loadModal(
          actionData.url,
          actionData.id ? { id: actionData.id } : {}
        );
      };

      if (!isUnread) {
        open();
        return;
      }

      booknetic.ajaxRest(`notifications/mark-as-read`, "POST", { notification_id })
        .then(() => {
          card.removeClass("unread-notification");

          card.find(".notification_time_badge")
              .removeClass("d-inline")
              .addClass("d-none");

          updateCounts();
          open();
        });
    });


      $(document).on("click", ".notification_panel_clear_btn", function () {
        booknetic.ajaxRest("notifications/mark-all-as-read", 'POST', {}).then(() => {
          $(".notification-card").removeClass("unread-notification");

          updateCounts();
        })
    });

    $(document).on('click', '.notification-carousel-item-header button', function (e) {
      const item = $(this).closest('.notification-carousel-item');

      booknetic.ajax('Base.dismiss_notification', { slug: item.data('slug') }, () => {
        item.fadeOut(200, function () {
          $(this).remove();

          const remainingAddons = $('.notification-carousel-item').length;

          updateCounts();
          updateEmptyState();

          if (remainingAddons === 0) {
            stopCarousel();
            carouselWrapper.hide();
          }
        });
      });
    });

    $('.notification-carousel-item-link').each(function () {
      const link = $(this);
      const slug = $(this).closest('.notification-carousel-item').data('slug');

      const url = new URL(window.location.href);

      url.searchParams.set('module', 'boostore');
      url.searchParams.set('action', 'details');
      url.searchParams.set('slug', slug);

      link.attr('href', url.href);
    });

    notificationPollInterval = setInterval(() => {
      currentPage = 1;
      hasMorePages = true;
      fetchNotifications(1, false);
    }, 10000);
  });
})(jQuery);