<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;

/**
 * @var mixed $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/shared.css', 'Boostore') ?>" type='text/css'>
<link rel="stylesheet" href="<?php echo Helper::assets('css/boostore.css', 'Boostore') ?>" type='text/css'>

<div class="boostore">
    <!-- Page header -->
    <div class="m_header clearfix addons_filter">
        <div class="row m-0 p-0">
            <div class="col-md-3 m-0 p-0">
                <div class="input-icon">
                    <i><img src="<?php echo Helper::icon('search.svg'); ?>" alt=""></i>
                    <input type="text" class="form-control search_input" placeholder="<?php echo bkntc__('Quick search'); ?>" value="">
                </div>
            </div>
            <div class="col-md-2 m-0 p-0">
               <select class="form-control" data-placeholder="<?php echo bkntc__('Select category') ?>" id="category">
                   <option value="0"><?php echo bkntc__('Categories'); ?></option>

                   <?php if (isset($parameters[ 'categories' ])): ?>
                       <?php foreach ($parameters[ 'categories' ] as $category): ?>
                           <option value="<?php echo htmlspecialchars($category[ 'id' ]); ?>"><?php echo htmlspecialchars($category[ 'name' ]); ?></option>
                       <?php endforeach ?>
                   <?php endif ?>
               </select>
            </div>
            <div class="col-md-7 m-0 p-0">
                <div class="m_head_actions float-right">
                    <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_addons"><img src="<?php echo Helper::icon('download.svg')?>"><?php echo bkntc__('My Addons'); ?></a>
                    <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_purchases"><img src="<?php echo Helper::icon('shopping-bag.svg')?>"><?php echo bkntc__('My Purchases'); ?></a>
                    <a class="btn btn-lg btn-my-cart" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=cart"><img src="<?php echo Helper::icon('shopping-cart.svg')?>"><span class="badge badge-info" id="bkntc_cart_items_counter"><?php echo $parameters['cart_items_count']; ?></span> </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel -->
    <section class="addon-title">
        <div class="m_head_title float-left">
            <?php echo bkntc__('Add-ons'); ?>
        </div>
    </section>
    <hr>

    <?php if (BoostoreHelper::checkAllAddonsUnowned()):?>
        <div id="bkntc-buy-all-banner-wrapper">
            <div id="bkntc-buy-all-banner">
                <p id="bkntc-banner-text"><?php echo bkntc__('Save 15% now – click to get all add-ons') ?></p>
                <button class="btn btn-lg btn-primary btn-danger float-left ml-1" id="buy_all" <?php echo BoostoreHelper::checkAllAddonsInCart() ? ' disabled' : '' ?>><?php echo bkntc__('BUY ALL'); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <section class="addons_content"></section>
</div>

<script src="<?php echo Helper::assets('js/shared.js', 'Boostore'); ?>"></script>
<script src="<?php echo Helper::assets('js/boostore.js', 'Boostore'); ?>"></script>