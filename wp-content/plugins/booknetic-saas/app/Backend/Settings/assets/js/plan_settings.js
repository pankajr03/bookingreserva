(function ($)
{
    "use strict";

    $(document).ready(function ()
    {
        $('#enable_annual_plan_badge').on('change', function () {
            $('#annual_plan_badge_container').toggle($(this).is(':checked'))
        });

        $('#defaultPlan').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('select'),
            width: '100%'
        })

        const $annualPlanBadgeColor = $('#annualPlanBadgeColor');
        $annualPlanBadgeColor.colorpicker({
            format: 'hex'
        });

        $annualPlanBadgeColor.on('changeColor', function (e) {
            const color = e.color.toString('hex');
            $('.current-color').css('background-color', color);
        });

        $('#booknetic_settings_area').on('click', '.plan-settings-save-btn', function ()
        {
            const trialPlanId  = $('#input_trial_plan_id').val();
            const trialPeriod  = $('#input_trial_period').val();
            const expirePlanId = $('#input_expire_plan_id').val();

            const defaultPlanOnBilling = $('#defaultPlan').val();
            const showMonthlyBreakdownOnAnnual = $('#input_show_monthly_breakdown').is(':checked') ? 'on' : 'off';
            const isAnnualPlanBadgeEnabled = $('#enable_annual_plan_badge').is(':checked') ? 1 : 0;
            const annualPlanBadgeText = $('#annualPlanBadgeText').val();
            const annualPlanBadgeColor = $('#annualPlanBadgeColor').val();

            if (isAnnualPlanBadgeEnabled) {
                if (!annualPlanBadgeText) {
                    booknetic.toast(booknetic.__('annual_plan_badge_text_required'), 'unsuccess');
                    return;
                }

                if (!annualPlanBadgeColor) {
                    booknetic.toast(booknetic.__('annual_plan_badge_color_required'), 'unsuccess');
                    return;
                }
            }

            const data = {
                trial_plan_id: trialPlanId,
                trial_period: trialPeriod,
                expire_plan_id: expirePlanId,
                default_interval_on_pricing: defaultPlanOnBilling,
                show_monthly_breakdown_on_annual: showMonthlyBreakdownOnAnnual,
                annual_plan_badge_text: annualPlanBadgeText,
                annual_plan_badge_color: annualPlanBadgeColor,
                is_annual_plan_badge_enabled: isAnnualPlanBadgeEnabled
            };

            booknetic.ajax('save_plan_settings', data, function () {
                booknetic.toast(booknetic.__('saved_successfully'), 'success');
            });

        });
    });

})(jQuery);
