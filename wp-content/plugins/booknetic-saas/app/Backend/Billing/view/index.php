<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Helpers\Date;

/**
 * @var $parameters
 */
$currentPlanName = htmlspecialchars($parameters[ 'current_plan' ]->name ?? '-');

?>

<div class="m_header_alert">
    <div class="alert alert-<?php echo ($parameters['has_expired'] ? 'warning' : 'success')?>">
        <?php if ($parameters['has_expired']): ?>
            <span><?php echo bkntcsaas__('Your plan has expired.') ?></span>
            <?php else: ?>
                <span>
                    <?php echo bkntcsaas__('Your current plan is %s.', ['<b>'. $currentPlanName .'</b>'], false)?>
                    <?php echo empty($parameters['expires_in']) ? '' : bkntcsaas__('The plan expiration date is %s.', [ Date::datee($parameters['expires_in']) ])?>
                </span>
        <?php endif; ?>
        <div>
            <?php if (!empty($parameters['active_subscription'])): ?>
                <button type="button" class="btn btn-default" id="cancel_subscription_btn"><?php echo bkntcsaas__('CANCEL SUBSCRIPTION')?></button>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" id="current_plan_btn"><?php echo bkntcsaas__('CURRENT PLAN')?></button>
            <button type="button" class="btn btn-warning" id="upgrade_plan_btn"><?php echo bkntcsaas__('UPGRADE PLAN')?></button>
        </div>
    </div>
    <?php if (Helper::getOption('woocommerce_enable', 'on', false) === 'on'):?>
        <div class="alert alert-info">
            <span><?php echo bkntcsaas__('Your balance: %s', [ Helper::price($parameters['money_balance']) ])?></span>
        <?php if (is_plugin_active('woocommerce/woocommerce.php')): ?>
            <div><button type="button" class="btn btn-info" data-load-modal="add_deposit"><i class="fa fa-plus"></i> <?php echo bkntcsaas__('ADD DEPOSIT')?></button></div>
        <?php endif; ?>
        </div>
    <?php endif;?>
</div>

<div id="choose_plan_window">
    <div class="close_choose_plan_window_btn">
        <img src="<?php echo Helper::icon('cross.svg')?>">
    </div>

    <div class="choose_plan_title">
        <?php echo bkntcsaas__('Choose a plan')?>
    </div>

    <div class="choose_plan_subtitle">
        <?php echo bkntcsaas__('Upgrade your account')?>
    </div>

    <div class="choose_plan_payment_cycle">
        <div class="payment_cycle active_payment_cycle">
            <?php echo bkntcsaas__('Monthly')?>
        </div>

        <div class="payment_cycle_swicher">
            <input type="checkbox"
                   class="payment_cycle_swicher_checkbox"
                   id="input_payment_cycle_swicher"
                    <?php echo Helper::getOption('default_interval_on_pricing', 'monthly') === 'annual'
                            ? ' checked'
                            : '' ?>>
            <label class="payment_cycle_swicher_label" for="input_payment_cycle_swicher"></label>
        </div>

        <div class="payment_cycle position-relative">
            <?php echo bkntcsaas__('Annual')?>
            <?php if ($parameters['is_annual_plan_badge_enabled']):?>
                <span class="annual-badge position-absolute" style="background-color: <?php echo htmlspecialchars($parameters['annual_plan_badge_color'])?>"><?php echo htmlspecialchars($parameters['annual_plan_badge_text'])?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="plans_area">

        <?php foreach ($parameters['plans'] as $plan): ?>
            <div class="plan_card" data-plan-id="<?php echo (int)$plan->id ?>">

                <?php if (!empty($plan->ribbon_text)): ?>
                    <div class="plan_ribbon">
                        <div><?php echo htmlspecialchars($plan->ribbon_text) ?></div>
                    </div>
                <?php endif; ?>

                <div class="plan_title">
                    <?php echo htmlspecialchars($plan->name) ?>
                </div>

                <!-- MONTHLY -->
                <div class="plan_price" data-price="monthly">
                    <?php echo Helper::price(
                        $plan->monthly_price *
                        ($plan->monthly_price_discount > 0 && empty($plan->actual_monthly_discount)
                                ? (100 - $plan->monthly_price_discount) / 100
                                : 1)
                    ) ?>
                </div>

                <!-- ANNUAL -->
                <div class="plan_price hidden" data-price="annually">
                    <?php
                    echo Helper::price(
                        $parameters['show_monthly_breakdown_on_annual']
                                ? $plan->annual_monthly_breakdown
                                : (
                                    $plan->annually_price *
                                    ($plan->annually_price_discount > 0 && empty($plan->actual_annually_discount)
                                        ? (100 - $plan->annually_price_discount) / 100
                                        : 1)
                                )
                    );
            ?>
                </div>

                <!-- MONTHLY SUBTITLE -->
                <div class="plan_subtitle" data-price="monthly">
                    <?php if ($plan->monthly_price_discount > 0 && empty($plan->actual_monthly_discount)): ?>
                        <div class="plan_subtitle_discount_line">
                            <?php echo bkntcsaas__(
                                '%d%% off ( Normally %s )',
                                [
                                        (int)$plan->monthly_price_discount,
                                        Helper::price($plan->monthly_price)
                                ]
                            ); ?>
                        </div>
                    <?php endif; ?>
                    <div><?php echo bkntcsaas__('per month') ?></div>
                </div>

                <!-- ANNUAL SUBTITLE -->
                <div class="plan_subtitle hidden" data-price="annually">
                    <?php if (!$parameters['show_monthly_breakdown_on_annual']
                            && $plan->annually_price_discount > 0
                            && empty($plan->actual_annually_discount)): ?>
                        <div class="plan_subtitle_discount_line">
                            <?php echo bkntcsaas__(
                                '%d%% off ( Normally %s )',
                                [
                                        (int)$plan->annually_price_discount,
                                        Helper::price($plan->annually_price)
                                ]
                            ); ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <?php echo $parameters['show_monthly_breakdown_on_annual']
                                ? bkntcsaas__('per month')
                                : bkntcsaas__('per year'); ?>
                    </div>
                </div>

                <div class="plan_description">
                    <?php echo $plan->description ?>
                </div>

                <div class="plan_footer">
                    <?php
                    $isSelectedPlan = $plan->id === $parameters['current_plan']->id;

            if ($isSelectedPlan) {
                $buttonText = bkntcsaas__('SELECTED');
                $disabled = 'disabled';
            } else {
                $buttonText = bkntcsaas__('CHOOSE');
                $disabled = '';
            }
            ?>

                    <button type="button"
                            class="btn btn-primary choose_plan_btn"
                            style="background: <?php echo htmlspecialchars($plan->color) ?> !important;"
                            <?php echo $disabled ?>>
                        <?php echo $buttonText ?>
                    </button>
                </div>

            </div>
        <?php endforeach; ?>

    </div>
</div>
<div id="choose_payment_method_window">
    <div class="choose_payment_method_back_btn"><img src="<?php echo Helper::icon('arrow.svg')?>"> <?php echo bkntcsaas__('back')?></div>
    <div class="close_choose_payment_method_window_btn"><img src="<?php echo Helper::icon('cross.svg')?>"></div>
    <div class="choose_payment_method_title"><?php echo bkntcsaas__('Select payment method')?></div>
    <div class="choose_payment_method_subtitle"><?php echo bkntcsaas__('You have chosen %s plan', ['<span id="chosen_plan_name"></span>'], false)?></div>

    <div class="payment_methods_area">
        <?php $availablePaymentMethodsCount = 0;?>
        <?php foreach ($parameters['payment_gateways_order'] as $payment_gateway):?>
            <?php if ($payment_gateway == 'stripe' && Helper::getOption('stripe_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="credit_card">
                    <img src="<?php echo Helper::assets('images/credit_card.svg', 'Billing')?>">
                    <span class="payment_method_card_subtitle"><?php echo bkntcsaas__('Credit card')?></span>
                </div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
            <?php if ($payment_gateway == 'paypal' && Helper::getOption('paypal_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="paypal"><img src="<?php echo Helper::assets('images/paypal.svg', 'Billing')?>" class="paypal_img"></div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
            <?php if ($payment_gateway == 'woocommerce' && Helper::getOption('woocommerce_enable', 'on', false) === 'on') : ?>
                <div class="payment_method_card" data-payment-method="balance">
                    <img src="<?php echo Helper::assets('images/wallet.png', 'Billing')?>">
                    <span class="payment_method_card_subtitle"><?php echo bkntcsaas__('Balance')?></span>
                </div>
                <?php $availablePaymentMethodsCount++;?>
            <?php endif;?>
        <?php endforeach;?>
        <?php if (!$availablePaymentMethodsCount):?>
            <div><?php echo bkntcsaas__('No available payment methods!')?></div>
        <?php endif;?>
    </div>

</div>

<div id="payment_succeeded_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'success' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_succeeded_popup_body">
        <div class="payment_succeeded_img">
            <img src="<?php echo Helper::assets('images/payment_success.svg', 'Billing')?>">
        </div>
        <div class="payment_succeeded_title"><?php echo bkntcsaas__('Payment Successful')?></div>
        <div class="payment_succeeded_subtitle"><?php echo bkntcsaas__('It might take some time to activate your new plan.')?></div>
        <div class="payment_succeeded_footer">
            <button type="button" class="btn btn-primary close_payment_succeeded_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<div id="payment_canceled_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'cancel' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_canceled_popup_body">
        <div class="payment_canceled_img">
            <img src="<?php echo Helper::assets('images/payment_canceled.svg', 'Billing')?>">
        </div>
        <div class="payment_canceled_title"><?php echo bkntcsaas__('Payment Canceled')?></div>
        <div class="payment_canceled_subtitle"><?php echo bkntcsaas__("We aren't able to process your payment. Please try again.")?></div>
        <div class="payment_canceled_footer">
            <button type="button" class="btn btn-primary close_payment_canceled_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<div id="payment_pending_popup"<?php echo (Helper::_get('payment_status', '', 'string') == 'pending' ? 'class="payment_popup"' : ' class="hidden"')?>>
    <div class="payment_pending_popup_body">
        <div class="payment_pending_img">
            <img src="<?php echo Helper::assets('images/payment_pending.jpg', 'Billing')?>">
        </div>
        <div class="payment_pending_title"><?php echo bkntcsaas__('Payment Pending')?></div>
        <div class="payment_pending_subtitle"><?php echo bkntcsaas__("We have received your payment request and it is currently pending")?></div>
        <div class="payment_pending_footer">
            <button type="button" class="btn btn-primary close_payment_pending_popup close_payment_popup"><?php echo bkntcsaas__('CLOSE')?></button>
        </div>
    </div>
</div>

<?php
echo $parameters['table'];
?>

<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/billing.css', 'Billing')?>" />
<script type="application/javascript" src="<?php echo Helper::assets('js/billing.js', 'Billing')?>"></script>
<script src="//js.stripe.com/v3/"></script>

<script type="application/javascript">
    localization['cancel_subscription_text'] = <?php echo json_encode(bkntcsaas__('Are you sure you want to cancel subscription?')) ?>;
    localization['YES'] = <?php echo json_encode(bkntcsaas__('YES'))?>;
    localization['NO'] = <?php echo json_encode(bkntcsaas__('NO'))?>;
    var stripe_client_id = <?php echo json_encode(htmlspecialchars(Helper::getOption('stripe_client_id', ''))) ?>;
</script>