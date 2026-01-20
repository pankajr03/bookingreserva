<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 */
$subscription = $parameters['info'];
$currentView = $parameters['currentView'];
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/main.css', 'Mobile') ?>" type="text/css">

<script type="application/javascript" src="<?php echo Helper::assets('js/main.js', 'Mobile') ?>"></script>

<section id="mobile-app" class="d-flex flex-column">
    <header>
        <h1 class="p-0 m-0 main-header"><?php echo bkntc__('Mobile App') ?></h1>
    </header>
    <div class="mobile-app-container d-flex justify-content-between">
        <div class="mobile-app-menu d-flex flex-column">
            <ul class="mobile-app-navigation d-flex flex-column m-0 p-0">
                <li class="<?php echo $currentView === 'manage_users' ? 'active' : '' ?>">
                    <a href="?page=<?php echo Helper::getSlugName() ?>&module=mobile_app&view=manage_users"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/booking-steps-settings.svg', 'Settings') ?>"
                             alt="<?php echo bkntc__('Manage Users') ?>">
                        <span><?php echo bkntc__('Manage Users') ?></span>
                    </a>
                </li>
                <li class="<?php echo $currentView === 'billing' ? 'active' : '' ?>">
                    <a href="?page=<?php echo Helper::getSlugName() ?>&module=mobile_app&view=billing"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/payments-settings.svg', 'Settings') ?>"
                             alt="<?php echo bkntc__('Billing & Plans') ?>">
                        <span><?php echo bkntc__('Billing') ?></span>
                    </a>
                </li>
                <li class="<?php echo $currentView === 'settings' ? 'active' : ''?>">
                    <a href="?page=<?php echo Helper::getSlugName()?>&module=mobile_app&view=settings"
                       class="d-flex align-items-center">
                        <img src="<?php echo Helper::assets('icons/general-settings.svg', 'Settings')?>"
                             alt="<?php echo bkntc__('Settings')?>">
                        <span><?php echo bkntc__('Settings')?></span>
                    </a>
                </li>
            </ul>
            <div class="gets-seats">
                <div class="d-flex align-items-center justify-content-between">
                    <label for="total-seats" class="m-0"><?php echo bkntc__('Seats') ?></label>
                    <p class="m-0">
                        <?php if ($subscription): ?>
                            <?php echo bkntc__('%s of %s seats used', [$subscription['assignedSeatCount'], $subscription['totalSeatCount']]) ?>
                        <?php else: ?>
                            <?php echo bkntc__('No seats') ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="seats-bar">
                    <?php if ($subscription): ?>
                        <progress id="total-seats" max="<?php echo htmlspecialchars($subscription['totalSeatCount']) ?>"
                                  value="<?php echo htmlspecialchars($subscription['assignedSeatCount']); ?>"
                                  class="w-100"></progress>
                    <?php else: ?>
                        <progress id="total-seats" max="0" value="0" class="w-100"></progress>
                    <?php endif; ?>
                </div>
                <div class="get-seats-button">
                    <a href="#"
                       class="booknetic-mobile-button manage-seats-btn"><?php echo bkntc__('Manage Seats') ?></a>
                </div>
            </div>
            <div class="legal-badges mt-auto d-flex align-items-center justify-content-between">
                <a href="https://apps.apple.com/app/booknetic-admin-panel/id6755733387" target="_blank">
                    <img src="<?php echo Helper::assets('legal/app-store-badge.svg') ?>"
                         alt="<?php echo bkntc__('Apple App Store badge') ?>"/>
                </a>
                <a href="https://play.google.com/store/apps/details?id=fs.code.booknetic&hl=en" target="_blank">
                    <img src="<?php echo Helper::assets('legal/google-play-badge.png') ?>"
                         alt="<?php echo bkntc__('Google Play badge') ?>"/>
                </a>
            </div>
        </div>
        <div class="mobile-app-view-area nice-scrollbar-primary">
            <?php echo $parameters['html']; ?>
        </div>
    </div>
</section>

<div class="booknetic-modal manage-seats-modal d-none">
    <div class="modal-header d-flex align-items-center justify-content-between">
        <h3 class="m-0 modal-title"><?php echo bkntc__('Manage seats') ?></h3>
        <button class="modal-close-btn d-flex align-items-center justify-content-center p-0">
            <img src="<?php echo Helper::assets('images/x-close.svg', 'Mobile') ?>" alt="">
        </button>
    </div>
    <div class="modal-body">
        <div class="d-flex justify-content-between manage-seats-container">
            <div class="seats-info d-flex flex-column">
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Seats from plan:') ?></p>
                    <span class="seats-from-plan"><?php echo htmlspecialchars($subscription['seatCount']) ?></span>
                </div>
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Current additional seats:') ?></p>
                    <span class="current-additional-seats"><?php echo htmlspecialchars($subscription['extraSeatCount']) ?></span>
                </div>
                <div class="seat-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Assigned (used) seats:') ?></p>
                    <span class="used-seats"><?php echo htmlspecialchars($subscription['assignedSeatCount']) ?></span>
                </div>
                <div class="price-per-additional-seat">
                    <div class="seat-row d-flex align-items-center justify-content-between">
                        <p class="m-0 p-0"><?php echo bkntc__('Price per additional seat:') ?></p>
                        <span class="price-additional-seat">
                         <?php echo htmlspecialchars($subscription['plan']['seatPrice']) ?>
                         <?php echo htmlspecialchars($subscription['currency']) ?>
                        </span>
                    </div>
                </div>
                <div class="new-additional-seat-container">
                    <div class="new-additional-seats d-flex align-items-center justify-content-between">
                        <p class="m-0 p-0"><?php echo bkntc__('Manage additional seats:') ?></p>
                        <div class="seat-input d-flex align-items-center justify-content-between">
                            <div class="seat-btn decrement-btn cursor-pointer" <?php echo $subscription['extraSeatCount'] === 0 ? 'disabled' : '' ?>>
                                <img src="<?php echo Helper::assets('images/decrease-icon.svg', 'Mobile') ?>" alt=""
                                     width="20px" height="20px">
                            </div>
                            <input type="number" class="additional-seat-input text-center" min="<?php echo $subscription['extraSeatCount'] ?>" max="<?php echo $subscription['plan']['extraSeatLimit'] ?>" value="<?php echo htmlspecialchars($subscription['extraSeatCount']) ?>"/>
                            <div class="seat-btn increment-btn cursor-pointer" <?php echo $subscription['extraSeatCount'] >=  $subscription['plan']['extraSeatLimit'] ? 'disabled' : '' ?>>
                                <img src="<?php echo Helper::assets('images/increase-icon.svg', 'Mobile') ?>" alt=""
                                     width="20px" height="20px">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="billing-info d-flex flex-column">
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Billing interval:') ?></p>
                    <span><?php echo bkntc__('Annual') ?></span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next billing date:') ?></p>
                    <span class="next-billing-date"><?php echo htmlspecialchars($subscription['nextBillingDate']) ?></span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Due today:') ?></p>
                    <span class="subtotal">
                        0
                        <?php echo htmlspecialchars($subscription['currency']) ?>
                    </span>
                </div>
                <div class="billing-row d-flex align-items-center justify-content-between">
                    <p class="m-0 p-0"><?php echo bkntc__('Next billing payment:') ?></p>
                    <span class="next-billing-payment">
                        <?php echo htmlspecialchars($subscription['nextBillingAmount']) ?>
                        <?php echo htmlspecialchars($subscription['currency']) ?>
                    </span>
                </div>
                <button class="btn-primary button pay-and-activate-btn" <?php echo $subscription['plan']['extraSeatLimit'] === 0 ? 'disabled' : '' ?>>
                    <?php echo bkntc__('Pay and activate') ?>
                </button>
            </div>
        </div>
        <div class="manage-seats-info">
            <?php echo bkntc__('When you purchase additional seats, the amount you pay will be prorated based on the remaining time until your next billing date, ensuring you’re charged fairly.') ?>
            <br/>
            <br/>
            <?php echo bkntc__('Seat reductions do not apply immediately. You will retain access to your current number of seats until your next billing date. The downgrade will take effect after your subscription renews.') ?>
        </div>
    </div>
    <div class="terms-and-conditions d-flex align-items-center mt-0">
        <a href="https://www.booknetic.com/terms-and-conditions"
           target="_blank"><?php echo bkntc__('Terms condition') ?></a>
        <div class="vertical-line"></div>
        <a href="https://www.booknetic.com/privacy-policy" target="_blank"><?php echo bkntc__('Privacy policy') ?></a>
    </div>
</div>

<div id="modal-overlay" class="modal-overlay d-none"></div>
