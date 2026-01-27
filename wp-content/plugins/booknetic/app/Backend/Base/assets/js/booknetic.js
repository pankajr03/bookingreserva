(function ($) {
  "use strict";

  $(document).ready(function () {
    if ("calendar" in $.fn) {
      $.fn.calendar.dates["en"]["months"] = [
        booknetic.__("January"),
        booknetic.__("February"),
        booknetic.__("March"),
        booknetic.__("April"),
        booknetic.__("May"),
        booknetic.__("June"),
        booknetic.__("July"),
        booknetic.__("August"),
        booknetic.__("September"),
        booknetic.__("October"),
        booknetic.__("November"),
        booknetic.__("December"),
      ];
      $.fn.calendar.dates["en"]["days"] = [
        booknetic.__("Sun"),
        booknetic.__("Mon"),
        booknetic.__("Tue"),
        booknetic.__("Wed"),
        booknetic.__("Thu"),
        booknetic.__("Fri"),
        booknetic.__("Sat"),
      ];
      $.fn.calendar.dates["en"]["daysShort"] =
        $.fn.calendar.dates["en"]["days"];
    }

    $(document)
      .on("click", ".validate-button", function () {
        $("div,span").removeClass("input-error");
        $(".validate-form .required").each(function () {
          if ($(this).val() === "") {
            if ($(this).next().hasClass("select2")) {
              $(this).next().addClass("input-error");
            } else {
              $(this).addClass("input-error");
            }
          }
        });
      })
      .on("click", ".l_m_nav_item_link.share_your_page_btn", function () {
        $(".close_menu_s").click();
      })
      .on("click", ".l_m_nav_item[data-id]", function (e, toggleTime) {
        if (!e.target.classList.contains("is_collapse_icon")) {
          return;
        }

        e.preventDefault();

        let _this = $(this);
        let id = _this.data("id");

        _this
          .find(".l_m_nav_item_icon.is_collapse_icon")
          .toggleClass("fa-rotate-180");
        $(`.l_m_nav_item[data-parent-id=${id}]`).slideToggle();
      })
      .on("click", ".share_your_page_btn", function () {
        booknetic.loadModal("billing.share_page", {});
      })
      .on("click", ".starting_guide_icon", function (e) {
        let cookieName = "guide_panel_hidden=";
        let guidePanelDisabled = 0;
        let cookies = document.cookie.split(";");

        for (var i = 0; i < cookies.length; i++) {
          var cookie = cookies[i];

          while (cookie.charAt(0) === " ")
            cookie = cookie.substring(1, cookie.length);

          if (cookie.indexOf(cookieName) === 0) {
            guidePanelDisabled = decodeURIComponent(
              cookie.substring(cookieName.length, cookie.length)
            );
          }
        }

        const guidePanel = $(".starting_guide_panel");

        if (
          guidePanel.hasClass("animated faster fadeOutDown") ||
          guidePanelDisabled == 1 ||
          (guidePanelDisabled == 2 && e.hasOwnProperty("isTrigger"))
        ) {
          return;
        }

        guidePanel.stop().toggle(400, function () {
          let date = new Date();
          date.setTime(Date.now() + 30 * 24 * 60 * 60 * 1000);
          let expires = "; expires=" + date.toUTCString();

          if (guidePanel.is(":hidden")) {
            //set cookie to hide only guide panel
            let name = "guide_panel_hidden=2";
            document.cookie = name + expires + "; path=/";
          } else {
            let name = "guide_panel_hidden=0";
            document.cookie = name + expires + "; path=/";
          }
        });
      })
      .on("click", ".close_starting_guide", function () {
        $(".starting_guide_icon, .starting_guide_panel").addClass(
          "animated faster fadeOutDown"
        );

        //set cookie to hide guide panel and guide icon
        let date = new Date();
        date.setTime(Date.now() + 30 * 24 * 60 * 60 * 1000);
        let name = "guide_panel_hidden=1";
        let expires = "; expires=" + date.toUTCString();
        document.cookie = name + expires + "; path=/";
      })
      .on("click", ".language-switcher-select > div", function () {
        let selected_language = $(this).data("language-key");

        booknetic.ajax(
          "base.switch_language",
          { language: selected_language },
          function () {
            booknetic.loading(true);
            window.location.reload();
          }
        );
      })
      .on("click", ".multilang_globe_icon", function () {
        var siblingInput = $(this).siblings("[data-multilang=true]")[0],
          rowId = $(siblingInput).data("multilang-fk"),
          table = $(siblingInput).data("table-name"),
          column = $(siblingInput).data("column-name"),
          translations = $(siblingInput).data("translations");

        booknetic.defaultTranslatingValue = undefined;
        $(".bkntc_translating_input").removeClass("bkntc_translating_input");
        $(siblingInput).addClass("bkntc_translating_input");

        if (!table || !column) {
          booknetic.toast(
            booknetic.__("Not a valid translatable field"),
            "unsuccess"
          );
          return;
        }

        booknetic.loadModal(
          "Base.get_translations",
          {
            row_id: rowId,
            table: table,
            column: column,
            translations: JSON.stringify(translations),
            node: siblingInput.nodeName.toLowerCase(),
          },
          {
            z_index: 1051,
          }
        );
      })
      .on("click", ".booknetic_leave_beta", function () {
        $(".booknetic_leave_beta_modal").fadeIn(250);
      })
      .on(
        "click",
        ".booknetic_leave_beta_modal_top_right, .booknetic_leave_beta_modal_bottom_right > .booknetic_cancel",
        function () {
          $(".booknetic_leave_beta_modal").fadeOut(250);
        }
      )
      .on("click", ".accept_terms", function () {
        $(
          ".booknetic_join_beta_modal_bottom_right > .booknetic_request_join_beta"
        ).prop(
          "disabled",
          !$(".booknetic_join_beta_modal_bottom_left  input").is(":checked")
        );
      })
      .on("click", ".booknetic_request_join_beta", function () {
        if (
          !$(".booknetic_join_beta_modal_bottom_left  input").is(":checked")
        ) {
          return;
        }

        booknetic.ajax("base.join_beta", {}, function () {
          $(".booknetic_join_beta_modal").fadeOut(450);
          $(".booknetic_join_beta.booknetic_help_center_category").hide();

          booknetic.toast(booknetic.__("join_beta_approval"), "success");
        });
      })
      .on("click", ".booknetic_request_leave_beta", function () {
        booknetic.ajax("base.leave_beta", {}, function () {
          $(".booknetic_leave_beta_modal").fadeOut(450);
          $(".booknetic_leave_beta.booknetic_help_center_category").hide();

          booknetic.toast(booknetic.__("leave_beta_approval"), "success");
        });
      });

    $(document).on("click", ".mobile-app-menu-button", () => $('.mobile-app-menu-dropdown').toggle());

    $(document).on("click", function (e) {
        const dropdown = $('.mobile-app-menu-dropdown');
        const button = $('.mobile-app-menu-button');

        // Close dropdown if clicked outside
        if (!dropdown.is(e.target) && dropdown.has(e.target).length === 0 &&
            !button.is(e.target) && button.has(e.target).length === 0) {
            dropdown.hide();
        }
    });

    let activeParentID = $(".l_m_nav_item.active_menu[data-parent-id]").data(
      "parent-id"
    );

    if (activeParentID) {
      $(`.l_m_nav_item[data-id=${activeParentID}]`).trigger("click", 0);
    }

    $(".left_side_menu")
      .addClass("nice-scrollbar-primary dark")
      .css("overflow", "auto");

    $("#leftSidebarScroll")
      .on("mouseenter", function () {
        $(".left_side_menu").addClass("is_hovered");
      })
      .on("mouseleave", function () {
        $(".left_side_menu").removeClass("is_hovered");
      });

      $(document).on('click', '.staff-regenerate-password-btn', function () {
          booknetic.ajax('base.getAllByUsername', {}, function (response) {
              if(response.result.length === 0){
                  $('.table-body').html(`
                               <div class="empty-info text-center">
                                     <p class="p-0">${booknetic.__("You don't have any seats")}</p>
                               </div>`);
              }else{
                  $('.table-body').html(
                      response.result.map(member => `
                      <div data-seat-id="${member.id}" class="member staff-member d-flex align-items-center justify-content-between">
                          <div class="member-user-info d-flex align-items-center">
                              <div class="d-flex flex-column">
                                  <span>${member.username}</span>
                              </div>
                          </div>
                      </div>
                  `).join('')
                  );
              }

              booknetic.newModal.open('.seats-modal');
          });
      });

      $(document).on('click', '.staff-member', function () {
          const seatId = $(this).data('seat-id');
          $('.staff-regenerate-password-modal').attr('seat-id', seatId);
          booknetic.newModal.open('.staff-regenerate-password-modal');
      });

      $(document).on('click', '.modal-confirm', function () {
          const $modal = $(this).closest('.booknetic-modal');

          if ($modal.hasClass('staff-regenerate-password-modal')) {
              const seatId = $modal.attr('seat-id');
              booknetic.ajax('base.regenerate_password', {seatId}, (result) => {
                  $('.username-credential').text(result.username);
                  $('.password-credential').text(result.app_password);

                  booknetic.newModal.open('.seat-credentials-modal');
              });
          }
      });

      $(document).on('click', '.staff-copy-btn', function () {
          const $btn = $(this);
          const $text = $btn.find('span');
          const original = $text.text();
          const password = $btn.closest('.credential-input').find('span').first().text().trim();

          navigator.clipboard.writeText(password).then(() => {
              $text.text('Copied');

              setTimeout(() => {
                  $text.text(original);
              }, 1500);
          });
      });

    // Starting guide...
    let completed_actions = $(".starting_guide_steps_completed").length;
    let all_actions = $(".starting_guide_steps").length;
    let actions_neded = all_actions - completed_actions;
    if (actions_neded > 0) {
      $(".starting_guide_steps").each(function (index) {
        $(this).attr("data-step-index", index + 1);
      });

      $(".starting_guide_icon").attr("data-actions", actions_neded);
      $(".starting_guide_progress_bar_text > span:first").text(
        booknetic.zeroPad(completed_actions)
      );
      $(".starting_guide_progress_bar_text > span:last").text(
        " / " + booknetic.zeroPad(all_actions)
      );

      let percent = parseInt((completed_actions * 100) / all_actions);
      $(".starting_guide_progress_bar_stick_color").animate(
        { width: percent + "%" },
        400
      );

      $(".starting_guide_icon").click();
    }
  });
})(jQuery);
