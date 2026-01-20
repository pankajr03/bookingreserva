<?php

namespace BookneticSaaS\Providers\Core;

use BookneticSaaS\Providers\Common\PluginService;
use BookneticSaaS\Providers\FSCode\FSCodeAPIClientLite;
use BookneticSaaS\Providers\Helpers\Helper;

class Backend
{
    public const MENU_SLUG = 'booknetic-saas';
    public const MODULES_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR;

    public static function init(): void
    {
        Permission::setAsBackEnd();

        self::initAdditionalData(true);

        add_action('admin_menu', function () {
            add_menu_page(
                'Booknetic SaaS',
                'Booknetic SaaS',
                'read',
                self::getSlugName(),
                [ self::class , 'initMenu' ],
                Helper::assets('images/logo-sm.svg'),
                90
            );
        });

        add_action('admin_init', function () {
            $page = Helper::_get('page', '', 'string');

            if ($page == self::getSlugName() && is_user_logged_in()) {
                do_action('bkntcsaas_backend');

                try {
                    Route::init();
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    if (empty($errorMessage)) {
                        $errorMessage = bkntcsaas__('Page not found or access denied!');
                    }

                    echo json_encode(Helper::response(false, $errorMessage, true));
                }

                exit();
            }

            self::initGutenbergBlocks();
        });
    }

    public static function initInstallation(): void
    {
        self::initAdditionalData(false);

        self::checkInstallationRequest();

        add_action('admin_menu', static function () {
            add_menu_page(
                'Booknetic SaaS',
                'Booknetic SaaS',
                'read',
                self::getSlugName(),
                array( self::class , 'installationMenu' ),
                Helper::assets('images/logo-sm.svg'),
                90
            );
        });

        if (Helper::_get('page', '', 'string') == self::getSlugName()) {
            wp_enqueue_script('booknetic-install', Helper::assets('js/install.js'), ['jquery']);
            wp_enqueue_style('booknetic-install', Helper::assets('css/install.css'));
        }
    }

    public static function installationMenu(): void
    {
        $apiClient = new FSCodeAPIClientLite();
        $options = $apiClient->request('booknetic-saas/product/get_options_of_where_did_you_find_us_select');
        $options = $options['data']['options'] ?? [];

        require_once self::MODULES_DIR . 'Base/view/install.php';
    }

    private static function checkInstallationRequest(): void
    {
        add_action('wp_ajax_booknetic_saas_install_plugin', function () {
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

            $pluginService = new PluginService();

            try {
                $pluginService->activate($purchaseCode, $email, $subscribedToNewsletter, $foundFrom);
            } catch (\Exception $e) {
                return Helper::response(false, $e->getMessage());
            }

            return Helper::response(true);
        });
    }

    public static function initDisabledPage(): void
    {
        self::initAdditionalData(false);

        add_action('admin_menu', function () {
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

        if (Helper::_get('page', '', 'string') == self::getSlugName()) {
            wp_enqueue_script('booknetic-disabled', Helper::assets('js/disabled.js'), ['jquery']);
            wp_enqueue_style('booknetic-disabled', Helper::assets('css/disabled.css'));
        }
    }

    public static function disabledMenu(): void
    {
        $select_options = [];

        require_once self::MODULES_DIR . 'Base/view/disabled.php';
    }

    public static function getSlugName(): string
    {
        return self::MENU_SLUG;
    }

    public static function initMenu(): void
    {
        return;
    }

    private static function initAdditionalData($initUpdater): void
    {
        if ($initUpdater) {
            $purchaseCode = Helper::getOption('purchase_code');
            $updater = new PluginUpdater(self::getSlugName());
            $updater->check_if_forced_for_update();
            $updater->set_filters();
        }

        add_filter('plugin_action_links_booknetic-saas/init.php', function ($links) {
            $newLinks = [
                '<a href="https://support.fs-code.com" target="_blank">' . __('Support', 'booknetic-saas') . '</a>',
                '<a href="https://www.booknetic.com/documentation/" target="_blank">' . __('Doc', 'booknetic-saas') . '</a>'
            ];

            return array_merge($newLinks, $links);
        });
    }

    private static function initGutenbergBlocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'booknetic-blocks',
            plugins_url('assets/gutenberg-block.js', dirname(__DIR__, 3) . '/init.php'),
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
        );

        register_block_type('booknetic/customer-sign-in', [ 'editor_script' => 'booknetic-blocks' ]);
        register_block_type('booknetic/customer-sign-up', [ 'editor_script' => 'booknetic-blocks' ]);
        register_block_type('booknetic/customer-forgot-password', [ 'editor_script' => 'booknetic-blocks' ]);

        register_block_type('booknetic/booking', ['editor_script' => 'booknetic-blocks']);
        register_block_type('booknetic/signin', ['editor_script' => 'booknetic-blocks']);
        register_block_type('booknetic/signup', ['editor_script' => 'booknetic-blocks']);
        register_block_type('booknetic/forgot-password', ['editor_script' => 'booknetic-blocks']);
        register_block_type('booknetic/changestatus', ['editor_script' => 'booknetic-blocks']);

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists('WP_Block_Editor_Context') ? 'block_categories_all' : 'block_categories';

        add_filter($filterName, function ($categories) {
            return array_merge(
                $categories,
                [[
                    'slug' => 'booknetic',
                    'title' => 'Booknetic'
                ]]
            );
        }, 10, 2);
    }
}
