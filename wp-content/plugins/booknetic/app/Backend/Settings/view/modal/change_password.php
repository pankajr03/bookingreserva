<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

?>

<script type="application/javascript" src="<?php echo Helper::assets('js/change_password.js', 'Settings') ?>"></script>

<div id="change-password-settings">
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="currentPassword"><?php echo bkntc__('Current Password') ?>:</label>
            <input type="password" class="form-control" data-multilang="true" id="currentPassword" placeholder="**************"/>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="newPassword"><?php echo bkntc__('New Password') ?>:</label>
            <input type="password" class="form-control" data-multilang="true" id="newPassword" placeholder="**************"/>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="newPasswordConfirm"><?php echo bkntc__('New Password Again') ?>:</label>
            <input type="password" class="form-control" data-multilang="true" id="newPasswordConfirm" placeholder="**************"/>
        </div>
    </div>
</div>
