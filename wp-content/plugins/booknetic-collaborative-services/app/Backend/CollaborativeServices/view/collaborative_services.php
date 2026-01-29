<?php
// Load saved settings
$collaborative_enabled = get_option('bkntc_collaborative_services_enabled', 'off');
$guest_info_required = get_option('bkntc_collaborative_guest_info_required', 'optional');
?>

<div id="booknetic_settings_area">
    <form id="collaborative_services_area">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_collaborative_enabled"><?php echo bkntc__('Enable Collaborative Booking'); ?></label>
                <select class="form-control" id="input_collaborative_enabled" name="collaborative_enabled">
                    <option value="0" <?php selected($collaborative_enabled, 0); ?>><?php echo bkntc__('Disabled'); ?></option>
                    <option value="1" <?php selected($collaborative_enabled, 1); ?>><?php echo bkntc__('Enabled'); ?></option>
                </select>
                <small class="form-text text-muted"><?php echo bkntc__('Activate or deactivate collaborative booking functionality globally'); ?></small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="input_guest_info_required"><?php echo bkntc__('Guest Customer Information'); ?></label>
                <select class="form-control" id="input_guest_info_required" name="guest_info_required">
                    <option value="0" <?php selected($guest_info_required, 0); ?>><?php echo bkntc__('Optional'); ?></option>
                    <option value="1" <?php selected($guest_info_required, 1); ?>><?php echo bkntc__('Required'); ?></option>
                </select>
                <small class="form-text text-muted"><?php echo bkntc__('Set whether guest information fields are required or optional during booking'); ?></small>
            </div>
        </div>

       
    </form>
</div>

<script type="application/javascript">
(function($) {
    "use strict";

    // $(document).ready(function() {
    //     $('#collaborative_services_save_btn').on('click', function() {
    //         var data = new FormData($('#collaborative_services_area')[0]);

    //         // Use collaborative_services.save to match submenu action
    //         data.append('module', 'settings');
    //         data.append('action', 'collaborative_services.save');
    //         booknetic.ajax('collaborative_services.save', data, function(result) {
    //             booknetic.toast(result.message || booknetic.__('saved_successfully'), 'success');
    //         });
    //     });
    // });

    $(document).ready(function() {
        $('.settings-save-btn').on('click', function() {
            var data = new FormData($('#collaborative_services_area')[0]);

            booknetic.ajax('collaborative_services.save', data, function(result) {
                booknetic.toast(result.message || booknetic.__('saved_successfully'), 'success');
            });
        });
    });

})(jQuery);
</script>
