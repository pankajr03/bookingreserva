<?php
defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
?>

<div id="booknetic_settings_area">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/plan_settings.css', 'Settings') ?>"/>
    <script type="application/javascript" src="<?php echo Helper::assets('js/plan_settings.js', 'Settings') ?>"></script>

    <div class="actions_panel clearfix">
        <button type="button"
                class="btn btn-lg btn-success plan-settings-save-btn float-right">
            <i class="fa fa-check pr-2"></i>
            <?php echo bkntcsaas__('SAVE CHANGES') ?>
        </button>
    </div>

    <div class="settings-light-portlet">
        <div class="ms-title">
            <?php echo bkntcsaas__('Plan Settings'); ?>
        </div>

        <div class="ms-content">
            <form class="position-relative">

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label><?php echo bkntcsaas__('Trial plan'); ?></label>
                        <select class="form-control" id="input_trial_plan_id">
                            <?php foreach ($parameters['plans'] as $plan): ?>
                                <option value="<?php echo (int)$plan->id; ?>"
                                        <?php echo ((int)$parameters['trial_plan_id'] === (int)$plan->id)
                                                ? 'selected'
                                                : ''; ?>>
                                    <?php echo esc_html($plan->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label><?php echo bkntcsaas__('Trial period (days)'); ?></label>
                        <input type="number"
                               min="1"
                               class="form-control"
                               id="input_trial_period"
                               value="<?php echo (int)$parameters['trial_period']; ?>">
                    </div>

                    <div class="form-group col-md-4">
                        <label><?php echo bkntcsaas__('Plan for expired tenants'); ?></label>
                        <select class="form-control" id="input_expire_plan_id">
                            <?php foreach ($parameters['plans'] as $plan): ?>
                                <option value="<?php echo (int)$plan->id; ?>"
                                        <?php echo ((int)$parameters['expire_plan_id'] === (int)$plan->id)
                                                ? 'selected'
                                                : ''; ?>>
                                    <?php echo esc_html($plan->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="defaultPlan"><?php echo bkntcsaas__('Default interval on Pricing Page'); ?></label>
                        <select name="defaultPlan" id="defaultPlan" class="col-md-6">
                            <option value="monthly" <?php echo $parameters['default_interval_on_pricing'] === 'monthly' ? 'selected' : ''?> ><?php echo bkntcsaas__('Monthly')?></option>
                            <option value="annual" <?php echo $parameters['default_interval_on_pricing'] === 'annual' ? 'selected' : ''?>><?php echo bkntcsaas__('Annual')?></option>
                        </select>
                    </div>
                </div>

                <div class="form-row" id="monthly_breakdown_container">
                    <div class="form-group col-md-12">
                        <div class="form-control-checkbox">
                            <label>
                                <?php echo bkntcsaas__('Display monthly breakdown on the annual plan view'); ?>
                            </label>

                            <div class="fs_onoffswitch">
                                <input type="checkbox"
                                       class="fs_onoffswitch-checkbox"
                                       id="input_show_monthly_breakdown"
                                        <?php echo ($parameters['show_monthly_breakdown_on_annual'] === 'on')
                                                ? 'checked'
                                                : ''; ?>>
                                <label class="fs_onoffswitch-label"
                                       for="input_show_monthly_breakdown"></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <div class="form-control-checkbox">
                            <label>
                                <?php echo bkntcsaas__('Display annual plan badge'); ?>
                            </label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="enable_annual_plan_badge"
                                        <?php echo $parameters['is_annual_plan_badge_enabled'] ? 'checked' : ''; ?>>
                                <label class="fs_onoffswitch-label" for="enable_annual_plan_badge"></label>
                            </div>
                        </div>
                    </div>
                </div>
               <div class="form-row" id="annual_plan_badge_container" style="<?php echo $parameters['is_annual_plan_badge_enabled'] ? '' : 'display: none' ?>">
                   <div class="form-group col-md-6">
                       <label for="annualPlanBadgeText"><?php echo bkntcsaas__('Annual plan badge text')?></label>
                       <input type="text" class="form-control" id="annualPlanBadgeText" placeholder="<?php echo bkntcsaas__('Enter annual plan text')?>" value="<?php echo htmlspecialchars($parameters['annual_plan_badge_text'])?>">
                   </div>
                   <div class="form-group col-md-6 position-relative">
                       <label for="annualPlanBadgeColor"><?php echo bkntcsaas__('Annual plan badge color')?></label>
                       <input type="text" class="form-control" id="annualPlanBadgeColor" value="<?php echo htmlspecialchars($parameters['annual_plan_badge_color']) ?>">
                       <span class="current-color position-absolute" style="background-color: <?php echo htmlspecialchars($parameters['annual_plan_badge_color'])?>"></span>
                   </div>
               </div>
            </form>
        </div>
    </div>
</div>
