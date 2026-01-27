<?php

use BookneticApp\Providers\Core\Bootstrap;
use BookneticApp\Providers\Helpers\Helper;

defined('ABSPATH') or die();

$syncedAddons = Helper::getOption('synced_addons', [], false);

$blockedAddons = [];
$warningAddons = [];

if (!is_array($syncedAddons) || empty($syncedAddons)) {
    return;
}

foreach ($syncedAddons as $slug => $addon) {
    if (!Bootstrap::isAddonEnabled($slug)) {
        continue;
    }

    if (!is_array($addon)) {
        continue;
    }

    $action = $addon['action'] ?? null;

    if ($action === 'block') {
        $blockedAddons[] = $slug;
    } elseif ($action === 'warn') {
        $warningAddons[] = $slug;
    }
}

if (empty($blockedAddons) && empty($warningAddons)) {
    return;
}

?>

<div class="m_header_alert">
    <?php if (!empty($blockedAddons)): ?>
        <div class="alert alert-danger" role="alert">
            <div>
                <i class="fa fa-exclamation-triangle mr-2"></i>
                <strong class="font-semibold"><?php echo bkntc__('Blocked Add-ons:') ?></strong>
                <?php echo bkntc__('The following add-ons have been blocked and are not functional: %s', [
                        '<strong class="font-semibold">' . implode(', ', array_map('htmlspecialchars', $blockedAddons)) . '</strong>'
                ], false) ?>
                <button type="button" class="btn btn-primary ml-3 purchase-crack-addons">
                    <i class="fa fa-shopping-cart mr-2"></i>
                    <?php echo bkntc__('Go to Boostore') ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($warningAddons)): ?>
        <div class="alert alert-warning" role="alert">
            <div>
                <i class="fa fa-exclamation-circle mr-2"></i>
                <strong class="font-semibold"><?php echo bkntc__('Purchase Required:') ?></strong>
                <?php echo bkntc__('Please purchase the following addons to use them without any issues: %s', [
                        '<strong class="font-semibold">' . implode(', ', array_map('htmlspecialchars', $warningAddons)) . '</strong>'
                ], false) ?>
                <button type="button" class="btn btn-primary ml-3 purchase-crack-addons">
                    <i class="fa fa-shopping-cart mr-2"></i>
                    <?php echo bkntc__('Purchase Now') ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="application/javascript" src="<?php echo Helper::assets('js/addon-warnings.js') ?>"></script>