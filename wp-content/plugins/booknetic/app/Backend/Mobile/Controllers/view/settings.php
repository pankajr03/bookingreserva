<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
$allowStaffToRegeneratePassword = $parameters['allow_staff_to_regenerate_app_password'];

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/settings.css', 'Mobile') ?>" type="text/css">
<script type="application/javascript" src="<?php echo Helper::assets('js/settings.js', 'Mobile') ?>"></script>

<div class="mobile-settings-view h-100">
    <div class="settings-header">
        <h3 class="m-0 p-0"><?php echo bkntc__('Settings')?></h3>
    </div>
    <div class="settings-menu">
        <div class="d-flex align-items-center form-control-checkbox">
            <label class="label m-0 p-0" for="allow-password-regenerate"><?php echo bkntc__('Allow staff to regenerate their app passwords')?></label>
            <div class="fs_onoffswitch">
                <input type="checkbox" <?php echo $allowStaffToRegeneratePassword == '1' ? 'checked' : ''?> class="fs_onoffswitch-checkbox" id="allow-password-regenerate">
                <label class="fs_onoffswitch-label" for="allow-password-regenerate"></label>
            </div>
        </div>
    </div>
</div>
