<?php

defined('ABSPATH') or die();

/**
 * @var array $parameters
 * */

use BookneticApp\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Helper as SaaSHelper;

if (empty($parameters[ 'tenant' ])) {
    return;
}

/**
 * @var \BookneticSaaS\Models\Tenant $tenant
 * */
$tenant = $parameters[ 'tenant' ];

?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/profile_settings.css', 'Settings') ?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/profile_settings.js', 'Settings') ?>"></script>

<div id="profile-details-settings">
    <div class="profile-image-section">
        <input type="file" id="imageInput" class="d-none" accept="image/*"/>
        <img src="<?php echo Helper::pictureUrl($tenant->picture) ?>"
             alt="Profile image"
             class="profile-image"
             loading="lazy"
             id="profileImage"
        />
        <div class="profile-image-actions">
            <p class="profile-image-label"><?php echo bkntc__('Profile image') ?></p>
            <div class="profile-image-buttons">
                <button id="uploadImage" class="upload-button"><?php echo bkntc__('Upload image') ?></button>
                <button id="removeImage" class="remove-button"><?php echo bkntc__('Remove') ?></button>
            </div>
        </div>
    </div>
    <hr class="divider"/>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="tenantFullName"><?php echo bkntc__('Full name') ?>:</label>
            <input class="form-control" data-multilang="true" id="tenantFullName"
                   value="<?php echo htmlspecialchars($tenant->full_name) ?>">
        </div>
        <div class="form-group col-md-6">
            <label for="tenantEmail"><?php echo bkntc__('Email') ?>:</label>
            <input class="form-control" data-multilang="true" id="tenantEmail"
                   value="<?php echo htmlspecialchars($tenant->email) ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label for="tenantDomain"><?php echo bkntc__('Domain') ?> <span
                        class="required-star">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"
                          id="basic-addon3"><?php echo SaaSHelper::getHostName() ?>/</span>
                </div>
                <input type="text" class="form-control" id="tenantDomain"
                       value="<?php echo htmlspecialchars($tenant->domain) ?>" maxlength="50">
            </div>
        </div>
    </div>
</div>
