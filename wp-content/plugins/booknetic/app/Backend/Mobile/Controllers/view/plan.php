<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
$billingInfo = $parameters['billingInfo'];
$plans = $parameters['plans'];
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/main.css', 'Mobile') ?>" type="text/css">
<link rel="stylesheet" href="<?php echo Helper::assets('css/plan.css', 'Mobile') ?>" type="text/css">

<script type="application/javascript" src="<?php echo Helper::assets('js/main.js', 'Mobile') ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/plan.js', 'Mobile') ?>"></script>

<section id="mobile-app" class="d-flex flex-column plan-container">
    <header>
        <h1 class="p-0 m-0 main-header"><?php echo bkntc__('Mobile App') ?></h1>
    </header>
    <div class="plan-header">
        <h2 class="m-0 p-0"><?php echo bkntc__('Plans') ?></h2>
        <p class="m-0"><?php echo bkntc__('Unlock the full power of the Booknetic Mobile App — choose the plan that’s right for you.') ?></p>
    </div>
    <div class="plan-wrapper">
        <?php if (empty($plans)): ?>
            <div class="danger-alert"><?php echo bkntc__('Something went wrong please try again later')?></div>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card d-flex flex-column"
                     data-plan-id="<?php echo htmlspecialchars($plan['id']); ?>">
                    <div class="header d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between">
                            <p class="card-heading m-0"><?php echo htmlspecialchars($plan['name']) ?></p>
                            <?php if ($plan['badge_text']) : ?>
                                <div class="plan-badge">
                                    <img src="<?php echo Helper::assets('images/star-icon.svg', 'Mobile') ?>" alt=""
                                         width="14px" height="14px">
                                    <span><?php echo bkntc__('Popular') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="card-header-description"><?php echo htmlspecialchars($plan['description'] ?? '') ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="price d-flex align-items-center">
                            <?php if ($plan['discount_price'] !== null && $plan['discount_price'] !== $plan['price']) : ?>
                                <span class="original-price">
                                <?php echo htmlspecialchars($plan['discount_price']); ?>
                                <?php echo htmlspecialchars($plan['currency']); ?>
                            </span>
                            <?php endif; ?>
                            <span>
                                <?php
                                    $yearlyPrice = $plan['price'];
                $monthlyPrice = $yearlyPrice > 0 ? $yearlyPrice / 12 : 0;

                echo bkntc__(
                    '%s%s / month',
                    [
                            htmlspecialchars($plan['currency']),
                            round($monthlyPrice, 2)
                        ]
                );
                ?>
                            </span>
                        </div>
                        <p class="billed m-0"><?php echo bkntc__('*billed annually') ?></p>
                        <div class="horizontal-line"></div>
                        <ul class="features d-flex flex-column p-0 m-0">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <li class="d-flex align-items-center">
                                    <img src="<?php echo Helper::assets('images/check-icon.svg', 'Mobile') ?>" alt=""
                                         width="14px"
                                         height="14px">
                                    <p class="m-0"><?php echo htmlspecialchars($feature) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="horizontal-line"></div>
                        <?php if ($plan['extra_seat_limit']) : ?>
                            <div class="additional-seats mt-auto">
                                <p class="m-0"><?php echo bkntc__('Additional seats') ?></p>
                                <div class="d-flex align-items-end">
                                    <div class="ranger">
                                    <span class="d-block">
                                        <?php echo bkntc__('Max:') ?>
                                        <?php echo htmlspecialchars($plan['extra_seat_limit']); ?>
                                    </span>
                                        <input type="range" class="w-100" name="volume" min="0" value="0"
                                               max="<?php echo htmlspecialchars($plan['extra_seat_limit']); ?>"/>
                                    </div>
                                    <input type="number"  class="additional-seat" value="0" min="0" max="<?php echo htmlspecialchars($plan['extra_seat_limit']); ?>">
                                </div>
                            </div>
                            <div class="horizontal-line"></div>
                        <?php endif; ?>
                        <button class="btn-primary subscribe-plan-btn btn-sm w-100 button">
                            <?php echo bkntc__('Select Plan'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="terms-and-conditions d-flex align-items-center mb-3">
        <a href="https://www.booknetic.com/terms-and-conditions"
           target="_blank"><?php echo bkntc__('Terms condition') ?></a>
        <div class="vertical-line"></div>
        <a href="https://www.booknetic.com/privacy-policy" target="_blank"><?php echo bkntc__('Privacy policy') ?></a>
    </div>
</section>

<div class="booknetic-modal payment-success-modal d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Success') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>

    <div class="modal-body d-flex flex-column align-items-center justify-content-center">
        <img src="<?php echo Helper::assets('images/success.svg', 'Mobile') ?>" alt="" width="36px" height="36px">
        <p class="m-0 p-0 text-center" style="margin-top: 16px !important"><?php echo bkntc__('Your payment request has been received and is currently being processed. This may take some time.') ?></p>
    </div>

    <div class="modal-footer d-flex justify-content-end">
        <button class="modal-cancel button btn-outline-secondary m-0 payment-success-close-btn"><?php echo bkntc__('Close') ?></button>
    </div>
</div>

<div class="booknetic-modal payment-error-modal d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Error') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>

    <div class="modal-body d-flex flex-column align-items-center justify-content-center">
        <img src="<?php echo Helper::assets('images/error.svg', 'Mobile') ?>" alt="" width="36px" height="36px">
        <p class="m-0 p-0 mt-1 text-center" style="margin-top: 16px !important"><?php echo bkntc__('Your payment request has been received and is currently being processed. This may take some time.') ?></p>
    </div>

    <div class="modal-footer d-flex justify-content-end">
        <button class="modal-cancel button btn-outline-secondary m-0"><?php echo bkntc__('Close') ?></button>
        <button class="modal-confirm button btn-primary m-0 payment-error-try-again-btn"><?php echo bkntc__('Try Again') ?></button>
    </div>
</div>


