<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Config;
use BookneticApp\Providers\Common\PluginService;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Container;
use BookneticSaaS\Providers\Helpers\Helper as SaasHelper;
use Exception;

class Backend
{
    public const MENU_SLUG			= 'booknetic';
    public const MODULES_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR;

    public static function init()
    {
        Permission::setAsBackEnd();

        Config::registerPaymentShortCode();

        self::initAdditionalData(true);

        if (Permission::isSuperAdministrator() && ! Route::isAjax() && Route::getCurrentAction() !== 'my_purchases' && ! empty(Helper::getOption('migration_v3', false, false))) {
            $currentPage = Helper::_get('page', '', 'string');

            if ($currentPage === 'booknetic-saas' && Helper::isSaaSVersion()) {
                Helper::redirect(admin_url('admin.php?page=booknetic-saas&module=boostore&action=my_purchases'));
            } elseif ($currentPage === self::MENU_SLUG && ! Helper::isSaaSVersion()) {
                Helper::redirect(admin_url('admin.php?page=' . Helper::getSlugName() . '&module=boostore&action=my_purchases'));
            }
        }

        if (! Permission::canUseBooknetic()) {
            return;
        }

        add_action('admin_menu', static function () {
            add_menu_page(
                'Booknetic',
                self::getMenuTitle(),
                'read',
                self::getSlugName(),
                [ self::class , 'initMenu' ],
                self::getMenuIcon(),
                90
            );
        });

        add_action('admin_init', static function () {
            $page = Helper::_get('page', '', 'string');

            if ($page === self::getSlugName() && is_user_logged_in()) {
                do_action('bkntc_backend');

                try {
                    Route::init();
                } catch (Exception $e) {
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $childViewFile = Backend::MODULES_DIR . 'Base/view/404.php';
                        $currentModule = 'base';
                        $currentAction = '404';
                        $fullViewPath = Backend::MODULES_DIR . 'Base' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'index' . '.php';
                        require_once $fullViewPath;
                    } else {
                        $errorMessage = $e->getMessage();
                        if (empty($errorMessage)) {
                            $errorMessage = bkntc__('Page not found or access denied!');
                        }

                        echo json_encode(Helper::response(false, $errorMessage, true));
                    }
                }

                exit();
            }

            self::initGutenbergBlocks();
            self::initPopupBookingGutenbergBlocks();
            self::initChangeStatusGutenbergBlocks();
            self::initSigninGutenbergBlocks();
            self::initSignUpGutenbergBlocks();
            self::initForgotPasswordGutenbergBlocks();
        });
    }

    public static function initInstallation(): void
    {
        self::initAdditionalData(false);

        self::checkInstallationRequest();

        add_action('admin_menu', static function () {
            add_menu_page(
                'Booknetic',
                'Booknetic',
                'read',
                self::getSlugName(),
                array( self::class , 'installationMenu' ),
                Helper::assets('images/logo-sm.svg'),
                90
            );
        });

        if (Helper::_get('page', '', 'string') === self::getSlugName()) {
            wp_enqueue_script('booknetic-install', Helper::assets('js/install.js'), ['jquery']);
            wp_enqueue_style('booknetic-install', Helper::assets('css/install.css'));
        }
    }

    private static function checkInstallationRequest(): void
    {
        add_action('wp_ajax_booknetic_install_plugin', static function () {
            $purchaseCode = Helper::_post('purchase_code', null, 'string');
            $foundFrom = Helper::_post('found_from', null, 'string');
            $email = Helper::_post('email', null, 'string');
            $subscribedToNewsletter = Helper::_post('subscribed_to_newsletter', 0, 'int', [ 0, 1 ]);

            if (empty($purchaseCode)) {
                Helper::response(false, 'Please enter the purchase code');
            } elseif (empty($foundFrom)) {
                Helper::response(false, 'Please select where did you find Booknetic from');
            } elseif (empty($email)) {
                Helper::response(false, 'Please enter the email');
            }

            $pluginService = Container::get(PluginService::class);

            try {
                $pluginService->activate($purchaseCode, $email, $subscribedToNewsletter, $foundFrom);
            } catch (Exception $e) {
                return Helper::response(false, $e->getMessage());
            }

            return Helper::response(true);
        });
    }

    public static function installationMenu(): void
    {
        $apiClient = Container::get(FSCodeAPIClient::class);
        try {
            $response = $apiClient->requestNew('booknetic/product/get_options_of_where_did_you_find_us_select');

            $options = $response->getData();
            $options = $options['data']['options'] ?? [];
        } catch (\Exception $e) {
        }

        require_once self::MODULES_DIR . 'Base/view/install.php';
    }

    public static function initDisabledPage(): void
    {
        self::initAdditionalData(false);

        self::checkReActivateAction();

        add_action('admin_menu', static function () {
            add_menu_page(
                'Booknetic (!)',
                'Booknetic (!)',
                'read',
                self::getSlugName(),
                [ self::class , 'disabledMenu' ],
                Helper::assets('images/logo-sm.svg'),
                90
            );
        });

        if (Helper::_get('page', '', 'string') === self::getSlugName()) {
            wp_enqueue_script('booknetic-disabled', Helper::assets('js/disabled.js'), ['jquery']);
            wp_enqueue_style('booknetic-disabled', Helper::assets('css/disabled.css'));
        }
    }

    private static function checkReActivateAction(): void
    {
        add_action('wp_ajax_booknetic_reactivate_plugin', static function () {
            $code = Helper::_post('code', '', 'string');

            if (empty($code)) {
                Helper::response(false, bkntc__('Please enter the purchase code!'));
            }

            $pluginService = Container::get(PluginService::class);

            try {
                $pluginService->reactivate($code);
            } catch (Exception $e) {
                return Helper::response(false, $e->getMessage());
            }

            return Helper::response(true, [ 'msg' => bkntc__('Plugin reactivated!') ]);
        });
    }

    public static function disabledMenu(): void
    {
        $select_options = [];

        require_once self::MODULES_DIR . 'Base/view/disabled.php';
    }

    public static function initMenu(): void
    {
        return;
    }

    private static function initAdditionalData($initUpdater): void
    {
        if ($initUpdater) {
            $updater = new PluginUpdater('booknetic');

            $updater->check_if_forced_for_update();
            $updater->set_filters();
            $updater->plugin_update_message();
        }

        add_filter('plugin_action_links_booknetic/init.php', function ($links) {
            $newLinks = [
                '<a href="https://support.fs-code.com" target="_blank">' . __('Support', 'booknetic') . '</a>',
                '<a href="https://www.booknetic.com/documentation/" target="_blank">' . __('Doc', 'booknetic') . '</a>'
            ];

            return array_merge($newLinks, $links);
        });
    }

    private static function initGutenbergBlocks()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-blocks',
            plugins_url('assets/gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );
        wp_localize_script('booknetic-blocks', 'BookneticData', [
            'appearances'	    =>	DB::fetchAll('appearance', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'staff'			    =>	DB::fetchAll('staff', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'services'		    =>	DB::fetchAll('services', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'service_categs'	=>	DB::fetchAll('service_categories', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'locations'		    =>	DB::fetchAll('locations', null, null, ['`id` AS `value`', '`name` AS `label`'])
        ]);

        register_block_type('booknetic/booking', ['editor_script' => 'booknetic-blocks']);

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists('WP_Block_Editor_Context') ? 'block_categories_all' : 'block_categories';

        if (Route::isAjax()) {
            add_filter($filterName, static function ($categories) {
                return array_merge(
                    $categories,
                    [
                        [
                            'slug' => 'booknetic',
                            'title' => 'Booknetic',
                        ],
                    ]
                );
            }, 10, 2);
        }
    }

    private static function initPopupBookingGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-popup-blocks',
            plugins_url('assets/popup-booking-gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );
        wp_localize_script('booknetic-popup-blocks', 'BookneticData', [
            'appearances'	    =>	DB::fetchAll('appearance', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'staff'			    =>	DB::fetchAll('staff', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'services'		    =>	DB::fetchAll('services', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'service_categs'	=>	DB::fetchAll('service_categories', null, null, ['`id` AS `value`', '`name` AS `label`']),
            'locations'		    =>	DB::fetchAll('locations', null, null, ['`id` AS `value`', '`name` AS `label`'])
        ]);

        register_block_type('booknetic/popup-booking', ['editor_script' => 'booknetic-popup-blocks']);

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists('WP_Block_Editor_Context') ? 'block_categories_all' : 'block_categories';

        add_filter($filterName, static function ($categories) {
            return array_merge(
                $categories,
                [
                    [
                        'slug' => 'booknetic',
                        'title' => 'Booknetic',
                    ],
                ]
            );
        }, 10, 2);
    }

    private static function initChangeStatusGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-change-status-blocks',
            plugins_url('assets/change-status-gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type('booknetic/changestatus', ['editor_script' => 'booknetic-change-status-blocks']);
    }

    private static function initSigninGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-signin-blocks',
            plugins_url('assets/signin-gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type('booknetic/signin', ['editor_script' => 'booknetic-signin-blocks']);
    }

    private static function initSignUpGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-signup-blocks',
            plugins_url('assets/signup-gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type('booknetic/signup', ['editor_script' => 'booknetic-signup-blocks']);
    }

    private static function initForgotPasswordGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-forgot-password-blocks',
            plugins_url('assets/forgot-password-gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type('booknetic/forgot-password', ['editor_script' => 'booknetic-forgot-password-blocks']);
    }
    public static function getSlugName()
    {
        if (Helper::isSaaSVersion()) {
            return SaasHelper::getOption('backend_slug', 'booknetic');
        }

        $slug = self::MENU_SLUG;

        return apply_filters('booknetic_backend_slug', $slug);
    }

    public static function getMenuTitle()
    {
        if (Helper::isSaaSVersion()) {
            return SaasHelper::getOption('powered_by', 'Booknetic');
        }

        $title = 'Booknetic';

        return apply_filters('booknetic_backend_title', $title);
    }

    public static function getMenuIcon()
    {
        if (Helper::isSaaSVersion()) {
            return Helper::profileImage(
                SaasHelper::getOption('whitelabel_logo_sm', 'logo-sm')
            );
        }

        $icon = Helper::assets('images/logo-sm.svg');

        return apply_filters('booknetic_backend_icon', $icon);
    }
}
