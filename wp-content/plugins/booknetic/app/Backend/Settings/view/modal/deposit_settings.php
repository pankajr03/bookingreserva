<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Settings\DTOs\Response\DepositSettingResponse;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var DepositSettingResponse $parameters
 */
?>

<script type="application/javascript" src="<?php echo Helper::assets('js/deposit_settings.js', 'Settings')?>"></script>
<style>

    .enable_deposit_text {
        display: flex;
        align-items: center;
        gap: 6px;
    }

</style>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="isEnabled">
            <span class="required-star">&nbsp;</span>
        </label>

        <div class="form-control-checkbox">

                            <span class="enable_deposit_text">
                                <i class="fa fa-info-circle help-icon do_tooltip"
                                   data-content="<?php echo bkntc__('Apply this deposit to all services unless a service has its own deposit configuration'); ?>">
                                </i>

                                <label for="isEnabled">
                                    <?php echo bkntc__('Enable Deposit'); ?>
                                </label>
                            </span>

            <div class="fs_onoffswitch">
                <input type="checkbox"
                       id="isEnabled"
                       class="fs_onoffswitch-checkbox"
                    <?php echo $parameters->isEnabled() ? 'checked' : ''; ?>>

                <label for="isEnabled"
                       class="fs_onoffswitch-label">
                </label>
            </div>

        </div>
    </div>
</div>
<div class="form-row deposit-settings"
     style="<?php echo $parameters->isEnabled() ? '' : 'display:none;' ?>">
    <div class="form-group col-md-6">
        <label><?php echo bkntc__('Type') ?></label>
        <select id="depositType" class="form-control">
            <option value="percent"
                <?php echo $parameters->getType() === 'percent' ? 'selected' : '' ?>>
                %
            </option>
            <option value="fixed"
                <?php echo $parameters->getType() === 'fixed' ? 'selected' : '' ?>>
                <?php echo htmlspecialchars(Helper::currencySymbol()) ?>
            </option>
        </select>
    </div>

    <div class="form-group col-md-6">
        <label><?php echo bkntc__('Amount') ?></label>
        <input type="number"
               id="depositValue"
               class="form-control"
               min="0"
               step="0.01"
               value="<?php echo esc_attr($parameters->getValue()) ?>">
    </div>
</div>

