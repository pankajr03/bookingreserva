<?php

use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

defined('ABSPATH') or die();

/**
 * @var $parameters
 */
$tourGuideSupportedAddons = [ 'booknetic-customforms' ];
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/shared.css', 'Boostore') ?>" type='text/css'>

<div class="m_header clearfix">
    <div class="m_head_title float-left">
        <div class="m_head_title float-left">
            <a href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore"><?php echo bkntc__('Add-ons'); ?></a>
            <i class="mx-2"><img src="<?php echo Helper::icon('arrow.svg'); ?>"></i>
            <span class="name"><?php echo bkntc__('My purchases'); ?></span>
        </div>
    </div>
    <div class="m_head_actions float-right">
        <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_addons"><img src="<?php echo Helper::icon('download.svg')?>"><?php echo bkntc__('My Addons'); ?></a>
        <a class="btn btn-lg btn-my" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=my_purchases"><img src="<?php echo Helper::icon('shopping-bag.svg')?>"><?php echo bkntc__('My Purchases'); ?></a>
        <a class="btn btn-lg btn-my-cart" href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=cart"><img src="<?php echo Helper::icon('shopping-cart.svg')?>"><span class="badge badge-info" id="bkntc_cart_items_counter"><?php echo $parameters['cart_items_count']; ?></span> </a>
    </div>
</div>

<div class="fs_separator"></div>

<div class="m_content pt-0" id="fs_data_table_div">
    <div class="fs_data_table_wrapper">
        <table class="fs_data_table elegant_table elegant_table_boostore table_addons table_purchases">
            <thead>
            <tr>
                <th style="text-align: left; width: 20%;"><?php echo bkntc__('Purchased on'); ?></th>
                <th><?php echo bkntc__('Add-ons'); ?></th>
                <th><?php echo bkntc__('Amount'); ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($parameters['items'])): ?>
                <tr>
                    <td colspan="100%" class="pl-4 text-secondary"><?php echo bkntc__('No entries!'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($parameters['items'] as $purchase): ?>
                    <tr>
                        <td style="text-align: left; width: 20%;"><?php echo Date::dateTime($purchase['created_at']); ?></td>
                        <td><?php echo count($purchase['addon_ids']); ?> <i class="fa fa-info-circle help-icon do_tooltip" data-content="<?php echo htmlspecialchars($purchase['addon_names'])?>"></i> </td>
                        <td><span>$<?php echo Math::floor($purchase['amount'], 2); ?></span></td>
                        <td><a href="<?php echo htmlspecialchars($purchase['invoice_url'])?>" target="_blank" class="btn-download"><?php echo bkntc__('Download Invoice')?><i class="fa fa-download ml-2" aria-hidden="true"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?php echo Helper::assets('js/shared.js', 'Boostore'); ?>"></script>

<?php if ($parameters[ 'is_migration' ]): ?>
    <div id="migrationModal" class="modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="progress mb-4" style="height: 8px;">
                    <div id="migrationProgress" class="progress-bar"></div>
                </div>

                <div class="mb-2">
                    <?php echo bkntc__('We are migrating your data.'); ?><br>
                    <?php echo bkntc__('Please wait until the migration process is done.'); ?><br>
                </div>

                <div class="text-danger">
                    <?php echo bkntc__('Do not leave the page.'); ?>
                </div>
            </div>

        </div>
    </div>

    <script src="<?php echo Helper::assets('js/migration.js', 'Boostore'); ?>"></script>
<?php endif; ?>
