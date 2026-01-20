<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * @var array $subscription
 */
$plans = $parameters['plans'] ?? [];
$subscription = $parameters['subscription'];
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/billing.css', 'Mobile') ?>" type="text/css">
<script type="application/javascript" src="<?php echo Helper::assets('js/billing.js', 'Mobile') ?>"></script>

<section class="billing-container overflow-auto">
    <?php if (!empty($subscription)):?>
        <div class="billing-table">
            <div class="billing-table-header">
                <h2 class="m-0 p-0"><?php echo bkntc__('Billing table') ?></h2>
            </div>
            <div class="billing-table-container">
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current Plan:') ?></p>
                    <span><?php echo htmlspecialchars($subscription['plan']['name']) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current additional seats:') ?></p>
                    <span><?php echo htmlspecialchars($subscription['seatCount']) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Additional seats on renewal:') ?></p>
                    <span><?php echo htmlspecialchars($subscription['extraSeatCountOnRenewal']) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next Billing Date:') ?></p>
                    <span><?php echo htmlspecialchars($subscription['nextBillingDate']) ?></span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next Payment Amount:') ?></p>
                    <span>
                        <?php echo htmlspecialchars($subscription['nextBillingAmount']) ?>
                        <?php echo !empty($plans) ? htmlspecialchars($plans[0]['currency']) : '$' ?>
                    </span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Payment Method:') ?></p>
                    <span>
 <?php if (!empty($subscription['paymentMethodLabel'])): ?>
     <?php echo htmlspecialchars($subscription['paymentMethodLabel']); ?>
 <?php endif; ?>

                    </span>
                </div>
                <div class="billing-table-row d-flex align-items-center justify-content-end pb-0">
                    <?php if ($subscription['cancelAtPeriodEnd'] === true) : ?>
                        <button class="btn-outline-secondary button undo-subscription-btn">
                            <?php echo bkntc__('Restore subscription') ?>
                        </button>
                    <?php else : ?>
                        <button class="btn-outline-secondary button cancel-subscription-btn">
                            <?php echo bkntc__('Cancel subscription') ?>
                        </button>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif;?>
<!--    <div class="billing-table payment-table">-->
<!--        <div class="billing-table-header">-->
<!--            <h2 class="m-0 p-0">--><?php //echo bkntc__('Payment history')?><!--</h2>-->
<!--        </div>-->
<!--        <div class="billing-table-container">-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>Pro</span>-->
<!--            </div>-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>5</span>-->
<!--            </div>-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>4</span>-->
<!--            </div>-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>9 Oct 2026</span>-->
<!--            </div>-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>$240.00</span>-->
<!--            </div>-->
<!--            <div class="billing-table-row d-flex align-items-center justify-content-between">-->
<!--                <p class="m-0 p-0">9 Oct 2026</p>-->
<!--                <span>$240.00</span>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
    <div class="terms-and-conditions d-flex align-items-center">
        <a href="https://www.booknetic.com/terms-and-conditions"
           target="_blank"><?php echo bkntc__('Terms condition') ?></a>
        <div class="vertical-line"></div>
        <a href="https://www.booknetic.com/privacy-policy" target="_blank"><?php echo bkntc__('Privacy policy') ?></a>
    </div>
</section>

<div class="booknetic-modal payment-cancel-subscription d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Cancel subscription') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>

    <div class="modal-body">
        <p class="m-0 p-0"><?php echo bkntc__('If you cancel your subscription, you’ll still be able to use the mobile app until your next billing date. No further payments will be charged after that since you’ve canceled. You can reactivate your subscription anytime. Are you sure you want to cancel?') ?></p>
    </div>

    <div class="modal-footer d-flex justify-content-end">
        <button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Close') ?></button>
        <button class="modal-confirm button btn-primary m-0"><?php echo bkntc__('Confirm cancellation') ?></button>
    </div>
</div>
