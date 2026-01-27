<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;

/**
 * @var mixed $parameters
 */
$totalPrice = 0;
$allAddonsInCart = BoostoreHelper::checkAllAddonsInCart();
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/shared.css', 'Boostore') ?>" type='text/css'>
<link rel="stylesheet" href="<?php echo Helper::assets('css/boostore.css', 'Boostore') ?>" type='text/css'>
<link rel="stylesheet" href="<?php echo Helper::assets('css/cart.css', 'Boostore') ?>" type='text/css'>


<div class="boostore">
    <!-- Page header -->
    <div class="m_header clearfix mb-3">
        <div class="m_head_title float-left">
            <?php echo bkntc__('Cart'); ?>
        </div>
        <div class="m_head_actions float-right">
            <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_addons"><img src="<?php echo Helper::icon('download.svg')?>"><?php echo bkntc__('My Addons'); ?></a>
            <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_purchases"><img src="<?php echo Helper::icon('shopping-bag.svg')?>"><?php echo bkntc__('My Purchases'); ?></a>
            <a class="btn btn-lg btn-my-cart" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=cart"><img src="<?php echo Helper::icon('shopping-cart.svg')?>"><span class="badge badge-info" id="bkntc_cart_items_counter"><?php echo $parameters['cart_items_count']; ?></span> </a>
        </div>
    </div>

    <?php if (! empty($parameters[ 'items' ])): ?>
        <section class="addons_content">
            <div class="row addons_card_wrapper">
                <div class="col-lg-8 mb-4 fs_data_table_wrapper">

                    <table class="fs_data_table elegant_table elegant_table_boostore">
                        <thead>
                        <tr>
                            <th style="text-align: left; width: 50%;"><?php echo bkntc__('Add-on name') ?></th>
                            <th><?php echo bkntc__('Price') ?></th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody class="table-group-divider">
                        <?php foreach ($parameters[ 'items' ] as $addon):?>
                            <tr data-addon="<?php echo $addon[ 'slug' ] ?>">
                                <td style="width: 50%; text-align: left;"><?php echo $addon[ 'name' ] ?></td>
                                <td>
                                    <?php if ($addon[ 'price' ][ 'current' ] < $addon[ 'price' ][ 'old' ]): ?>
                                        <span class="cart-addon-old-price">$<?php echo round($addon[ 'price' ][ 'old' ], 1); ?></span>
                                    <?php endif; ?>
                                    <span class="cart-addon-current-price">$<?php echo round($addon[ 'price' ][ 'current' ], 1); ?></span></td>
                                <td class="d-flex justify-content-end align-items-center">
                                    <button class="btn remove-cart-item"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach;?>
                        </tbody>

                    </table>

                </div>
                <div class="col-lg-4">
                    <div class="checkout_wrapper">
                        <div>
                            <p class="checkout-details-title"><?php echo bkntc__('Cart details') ?>:</p>
                        </div>
                        <div class="checkout_wrapper_prices">
                            <?php foreach ($parameters[ 'items' ] as $addon): ?>
                                <?php $totalPrice += round($addon[ 'price' ][ 'current' ], 1);?>
                                <div class="checkout_price_item" data-addon="<?php echo $addon[ 'slug' ] ?>" data-price="<?php echo $addon[ 'price' ][ 'current' ] ?>">
                                    <p class="checkout_price_item__title mb-2"><?php echo $addon[ 'name' ] ?> </p>
                                    <p class="checkout_price_item__price mb-2">$<?php echo round($addon[ 'price' ][ 'current' ], 1)?> </p>
                                </div>
                            <?php endforeach; ?>

                            <div class="checkout_price_item_total" >
                                <?php if ($allAddonsInCart): ?>
                                    <div id="buy_all_discount" class="checkout_price_item"><p class="checkout_price_item__total__title"><?php echo bkntc__('Discount') ?></p> <p class="checkout_price_item__price">15%</p></div>
                                <?php endif; ?>
                                <div class="checkout_price_item">
                                    <p class="checkout_price_item__total__title mb-1"> <?php echo bkntc__('Total'); ?> </p>
                                    <p id="checkout_total_price" class="checkout_price_item__price checkout_price_item_total_price mb-1" data-total-price="<?php echo $totalPrice ?>">$<?php echo $totalPrice ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (! $allAddonsInCart) :?>
                            <div id="coupon_wrapper" class="input-group my-2 mb-3">
                                <input id="coupon" class="form-control" placeholder="Enter coupon code">
                                <div class="input-group-append">
                                    <button id="apply_discount" class="btn h-100 btn-primary" type="button"><?php echo bkntc__('Apply') ?></button>
                                </div>
                            </div>

                            <div id="coupon_applied_wrapper" class="input-group my-2 mb-4 justify-content-between" style="display:none; border-top: 1px solid #d7d9dc; padding-top: 10px;">
                                <label style="font-weight: 500; font-size: 14px; margin: 0;" id="coupon_label">
                                    <?php echo bkntc__('Coupon applied') ?>:<span id="applied_coupon" style="font-weight: normal; margin-left: 8px;"></span>
                                </label>
                                <div id="remove_discount" class="close-btn" style="width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; text-align: center; border-radius: 50%; background: #721c24; cursor: pointer;"><i class="fa fa-times" style="color: #ffffff; line-height: normal;"></i></div>
                            </div>
                        <?php endif;?>

                        <button id="purchaseCart" class="btn w-100 btn-lg btn-success d-flex justify-content-center"><?php echo bkntc__('Proceed to checkout') ?></button>
                    </div>
                </div>
            </div>
        </section>
        <!-- Filter panel -->
    <?php else: ?>
        <section class="empty-cart-state">
            <div class="empty-cart-container d-flex justify-content-center align-items-center">
                <div class="empty-cart-content text-center">
                    <h2 class="empty-cart-title mb-2">
                        <?php echo bkntc__('Your cart is empty') ?>
                    </h2>
                    
                    <p class="empty-cart-subtitle mb-0">
                         <?php echo bkntc__('Discover our %s available.', [
                            '<a href="admin.php?page=' . Helper::getBackendSlug() . '&module=boostore" class="browse-add-ons">most popular items</a>'
                         ], false) ?>
                    </p>
                </div>
            </div>
        </section>
    <?php endif; ?>


</div>

<script src="<?php echo Helper::assets('js/shared.js', 'Boostore') ?>"></script>