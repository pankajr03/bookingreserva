<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
$openSetting = Helper::_get('setting', '', 'string');
$success     = Helper::_get('success', '', 'string');
$msg         = Helper::_get('msg', '', 'string');
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/settings.css', 'Settings') ?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-year-calendar.min.css') ?>">
<script type="application/javascript"
        src="<?php echo Helper::assets('js/bootstrap-year-calendar.min.js') ?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/settings.js', 'Settings') ?>"
        id="settingsJS" <?php if (! empty($openSetting)) {
            echo 'data-goto="' . htmlspecialchars($openSetting) . '"';
        } ?>></script>

<div class="m_header clearfix">
    <div class="m_head_title float-left"><?php echo bkntc__('Settings') ?></div>
</div>

<div class="settings-container" id="booknetic_settings_area">
    <div class="setting-detail-wrapper">
        <div class="settings-details d-flex flex-column">
            <div class="settings-details-header d-flex align-items-center justify-content-between">
                <span class="settings-details-title"><?php echo bkntc__('Settings') ?></span>
                <div class="settings-details-nav align-items-center d-none">
                    <a href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=settings" class="btn d-flex align-items-center view-all-settings-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 7 12" fill="none">
                            <path d="M6.35378 10.6463C6.40023 10.6927 6.43708 10.7479 6.46222 10.8086C6.48736 10.8693 6.50031 10.9343 6.50031 11C6.50031 11.0657 6.48736 11.1308 6.46222 11.1915C6.43708 11.2522 6.40023 11.3073 6.35378 11.3538C6.30732 11.4002 6.25217 11.4371 6.19148 11.4622C6.13078 11.4874 6.06572 11.5003 6.00003 11.5003C5.93433 11.5003 5.86928 11.4874 5.80858 11.4622C5.74788 11.4371 5.69273 11.4002 5.64628 11.3538L0.646277 6.35378C0.599789 6.30735 0.56291 6.2522 0.537747 6.1915C0.512585 6.13081 0.499634 6.06574 0.499634 6.00003C0.499634 5.93433 0.512585 5.86926 0.537747 5.80856C0.56291 5.74786 0.599789 5.69272 0.646277 5.64628L5.64628 0.646284C5.7401 0.552464 5.86735 0.499756 6.00003 0.499756C6.13271 0.499756 6.25996 0.552464 6.35378 0.646284C6.4476 0.740104 6.50031 0.867352 6.50031 1.00003C6.50031 1.13272 6.4476 1.25996 6.35378 1.35378L1.7069 6.00003L6.35378 10.6463Z" fill="#292D32"/>
                        </svg>
                        <span><?php echo bkntc__('Settings') ?></span>
                    </a>
                    <div class="separator"></div>
                    <p class="m-0 current-setting-sub-menu"></p>
                </div>
                <button class="d-flex align-items-center btn btn-lg btn-success settings-save-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="11" viewBox="0 0 16 11" fill="none">
                        <path d="M14.6667 1L5.50004 10.1667L1.33337 6" stroke="white" stroke-width="1.5"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span><?php echo bkntc__('SAVE CHANGES') ?></span>
                </button>
            </div>
            <div class="settings-details-wrapper d-flex overflow-hidden h-100">
                <div class="settings-details-list overflow-auto">
                    <?php foreach ($parameters['menu'] as $menu): ?>
                        <?php if (! $menu->isSubItemsRequired() || ($menu->isSubItemsRequired() && ! empty($menu->getSubItems()))): ?>
                            <div class="settings-menu" data-view="<?php echo $menu->getSlug(); ?>">
                                <div class="setting-menu-header d-flex align-items-center justify-content-between cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $menu->getIcon(); ?>"
                                             alt="<?php echo $menu->getTitle(); ?>"
                                             width="20px" height="20px">
                                        <span class="setting-menu-title"><?php echo $menu->getTitle(); ?></span>
                                    </div>
                                    <?php if (! empty($menu->getSubItems())): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                             viewBox="0 0 16 16"
                                             fill="none">
                                            <path d="M12.6462 5.64622C12.6927 5.59977 12.7478 5.56292 12.8085 5.53778C12.8692 5.51263 12.9343 5.49969 13 5.49969C13.0657 5.49969 13.1307 5.51263 13.1914 5.53778C13.2521 5.56292 13.3073 5.59977 13.3537 5.64622C13.4002 5.69268 13.437 5.74783 13.4622 5.80852C13.4873 5.86922 13.5003 5.93427 13.5003 5.99997C13.5003 6.06567 13.4873 6.13072 13.4622 6.19142C13.437 6.25212 13.4002 6.30727 13.3537 6.35372L8.35372 11.3537C8.30729 11.4002 8.25214 11.4371 8.19144 11.4623C8.13074 11.4874 8.06568 11.5004 7.99997 11.5004C7.93427 11.5004 7.8692 11.4874 7.8085 11.4623C7.7478 11.4371 7.69266 11.4002 7.64622 11.3537L2.64622 6.35372C2.5524 6.2599 2.49969 6.13265 2.49969 5.99997C2.49969 5.86729 2.5524 5.74004 2.64622 5.64622C2.74004 5.5524 2.86729 5.49969 2.99997 5.49969C3.13265 5.49969 3.2599 5.5524 3.35372 5.64622L7.99997 10.2931L12.6462 5.64622Z"
                                                  fill="#717171"/>
                                        </svg>
                                    <?php endif ?>
                                </div>
                                <?php if (! empty($menu->getSubItems())): ?>
                                    <ul class="setting-sub-menu">
                                        <?php foreach ($menu->getSubItems() as $submenu): ?>
                                            <li class="setting-sub-menu-title position-relative cursor-pointer load-setting-view"
                                                data-view="<?php echo $submenu->getSlug(); ?>"><?php echo $submenu->getTitle(); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="settings-details-content overflow-auto"></div>
            </div>
        </div>
    </div>

    <div class="settings-main-menu">
        <?php foreach ($parameters['menu'] as $menu): ?>
            <?php if (! $menu->isSubItemsRequired() || ($menu->isSubItemsRequired() && ! empty($menu->getSubItems()))): ?>
                <div class="settings-chart settings-category-card" data-view="<?php echo $menu->getSlug(); ?>">
                    <div class="settings-category-card-header d-flex justify-content-between">
                        <div class="settings-category-card-info">
                            <div class="settings-category-card-title"><?php echo $menu->getTitle(); ?></div>
                            <div class="settings-category-card-secondary-text"><?php echo $menu->getDescription(); ?></div>
                        </div>
                        <div class="settings-icon"><img src="<?php echo $menu->getIcon(); ?>"></div>
                    </div>
                    <div class="settings-category-card-separator"></div>
                    <div class="settings-category-card-submenu">
                        <?php foreach ($menu->getSubItems() as $submenu): ?>
                            <div data-view="<?php echo $submenu->getSlug(); ?>"
                                 class="d-flex justify-content-between align-items-center cursor-pointer settings-category-card-submenu-item load-setting-view">
                                <span>
                                    <?php echo $submenu->getTitle(); ?>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                                     fill="none">
                                    <path d="M5.64622 3.35372C5.59977 3.30726 5.56292 3.25211 5.53778 3.19141C5.51264 3.13072 5.4997 3.06566 5.4997 2.99997C5.4997 2.93427 5.51264 2.86921 5.53778 2.80852C5.56292 2.74782 5.59977 2.69267 5.64622 2.64621C5.69268 2.59976 5.74783 2.56291 5.80853 2.53777C5.86922 2.51263 5.93428 2.49969 5.99997 2.49969C6.06567 2.49969 6.13073 2.51263 6.19142 2.53777C6.25212 2.56291 6.30727 2.59976 6.35372 2.64621L11.3537 7.64622C11.4002 7.69265 11.4371 7.7478 11.4623 7.8085C11.4874 7.86919 11.5004 7.93426 11.5004 7.99997C11.5004 8.06567 11.4874 8.13074 11.4623 8.19144C11.4371 8.25214 11.4002 8.30728 11.3537 8.35372L6.35372 13.3537C6.2599 13.4475 6.13265 13.5002 5.99997 13.5002C5.86729 13.5002 5.74004 13.4475 5.64622 13.3537C5.5524 13.2599 5.49969 13.1326 5.49969 13C5.49969 12.8673 5.5524 12.74 5.64622 12.6462L10.2931 7.99997L5.64622 3.35372Z"
                                          fill="#292D32"/>
                                </svg>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($success === 'false' && ! empty($msg)): ?>
    <script>
        booknetic.toast("<?php echo htmlspecialchars($msg)?>", 'unsuccess');
    </script>
<?php endif; ?>
