<?php

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
            <span class="name"><?php echo bkntc__('My addons'); ?></span>
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
        <table class="fs_data_table elegant_table elegant_table_boostore table_addons">
            <thead>
            <tr>
                <th style="text-align: left; width: 50%;"><?php echo bkntc__('Add-on name'); ?></th>
                <th><?php echo bkntc__('Purchased on'); ?></th>
                <th></th>
                <th style="text-align: right;"><button class="btn-install-all btn-download"><?php echo bkntc__('Install all')?><i class="fa fa-download ml-2" aria-hidden="true"></i></button></th>
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
                        <td style="text-align: left; width: 50%;"><?php echo htmlspecialchars($purchase['name']); ?></td>
                        <td><?php echo Date::dateTime($purchase['created_at']); ?></td>
                        <td class="text-right">
                            <?php if ($purchase[ 'is_installed' ] && in_array($purchase[ 'slug' ], $tourGuideSupportedAddons) && !Helper::getOption($purchase[ 'slug' ] . '_tour_guide_passed', false)): ?>
                                <button class="btn btn-addon-setup" data-addon="<?php echo htmlspecialchars($purchase[ 'slug' ]); ?>">
                                    <?php echo bkntc__('Set up now'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($purchase['is_installed']): ?>
                                <button class="btn btn-outline-danger btn-uninstall" data-addon="<?php echo htmlspecialchars($purchase['slug']); ?>">
                                    <?php echo bkntc__('UNINSTALL'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success btn-install" data-addon="<?php echo htmlspecialchars($purchase['slug']); ?>">
                                    <?php echo bkntc__('INSTALL'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="installModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 2px;">
            <div class="modal-body text-center">
                <div class="mb-4" style="display: inline-flex; padding: 16px; border: 1px solid #E2EAF3; border-radius: 6px;">
                    <i class="fas fa-cloud-download-alt" style="font-size: 24px;"></i>
                </div>

                <h5 class="mb-4" id="modalTitle" style="font-weight: 600; color: #292D32;">You have 0 uninstalled addons</h5>

                <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 14px; color: #6c757d;">
                    <span id="currentAddon">Installing...</span>
                    <span id="progressText">0/0</span>
                </div>

                <div class="progress" style="height: 15px; border-radius: 30px; background-color: #e9ecef;">
                    <div class="progress-bar" id="progressBar" style="width: 0%; background-color: #53D56C; border-radius: 30px;"></div>
                </div>

                <button class="btn btn-secondary mt-3 px-4" data-dismiss="modal" id="closeBtn" disabled style="border-radius: 8px;">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo Helper::assets('js/shared.js', 'Boostore'); ?>"></script>
<script src="<?php echo Helper::assets('js/my_addons.js', 'Boostore'); ?>"></script>

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
