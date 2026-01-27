(function ($) {
  "use strict";

  $(document).ready(function () {
    $(document)
      .on("click", "", function () {
        $(".booknetic_join_beta_modal_bottom_right > .booknetic_request").prop(
          "disabled",
          !$(".booknetic_join_beta_modal_bottom_left  input").is(":checked")
        );
      })
      .on("click", ".booknetic_request", function () {
        if (
          !$(".booknetic_join_beta_modal_bottom_left  input").is(":checked")
        ) {
          return;
        }

        booknetic.ajax("base.join_beta", {}, function () {
          $(".booknetic_join_beta_modal").fadeOut(450);
          $(".booknetic_join_beta.booknetic_help_center_category").hide();

          booknetic.toast(booknetic.__("join_beta"), "success");
        });
      });

    $(window).resize(function () {
      $(".left_side_menu").getNiceScroll().resize();
    });

    $(".left_side_menu").niceScroll({
      cursorcolor: "#596269",
      cursorborder: "0",
    });
  });
})(jQuery);