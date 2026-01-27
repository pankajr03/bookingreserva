<?php

namespace {
    use BookneticApp\Backend\Settings\Helpers\LocalizationService;

    /**
     * @param $text
     * @param $params
     * @param $esc
     * @param $textdomain
     *
     * @return mixed
     */
    function bkntc__($text, $params = [], $esc = true, $textDomain = null)
    {
        $textDomain = $textDomain ?: LocalizationService::getTextdomain();

        if (empty($params)) {
            $result = trim(__($text, $textDomain));
        } else {
            $args = array_merge([ trim(__($text, $textDomain)) ], (array)$params);
            $result = sprintf(...$args);
        }

        return $esc ? htmlspecialchars($result) : $result;
    }
}

namespace BookneticApp\Providers\Core
{
    use BookneticApp\Config;
    use BookneticApp\Providers\Common\PluginService;
    use BookneticApp\Providers\Fonts\FontDownloader;
    use BookneticApp\Providers\Helpers\Helper;
    use BookneticApp\Providers\IoC\Container;

    /**
     * Class Bootstrap
     * @package BookneticApp
     */
    class Bootstrap
    {
        /**
         * @var AddonLoader[]
         */
        public static array $addons = [];

        public function __construct()
        {
            if (Helper::getOption('is_updating', '0', false) == '1') {
                add_action('admin_notices', static function () {
                    echo '<div class="notice notice-warning"><p>' . bkntc__('Booknetic is updating, please wait.') . '</p></div>';
                });

                return;
            }

            Config::load();

            if (! Helper::isPluginActivated()) {
                if (! Helper::isSaaSVersion()) {
                    add_action('init', [$this, 'initPluginInstallationPage']);
                }

                return;
            }

            if (LicenseService::checkLicense() === false) {
                add_action('init', [$this, 'initPluginDisabledPage']);

                return;
            }

            RestRoute::init();

            add_action('plugins_loaded', static function () {
                static::$addons = apply_filters('bkntc_addons_load', []);
            });

            add_action('init', [$this, 'initApp'], 10);
        }

        public static function isAddonEnabled(string $slug): bool
        {
            return isset(self::$addons[ $slug ]);
        }

        public function initApp(): void
        {
            $pluginService = Container::get(PluginService::class);
            $pluginService->fetchAndRunMigrationData('booknetic');
            $pluginService->fetchAndRunAddonsMigrationData();

            do_action('bkntc_init');

            if (!Helper::isAdmin() || (Helper::isAjax() && !Helper::isUpdateProcess())) {
                Frontend::init();
            } elseif (Helper::isAdmin()) {
                Backend::init();
            }

            CronJob::init();

            FontDownloader::register();
        }

        public function initPluginInstallationPage(): void
        {
            if (Helper::isAdmin()) {
                Backend::initInstallation();
            }
        }

        public function initPluginDisabledPage(): void
        {
            if (Helper::isAdmin()) {
                Backend::initDisabledPage();
            }
        }

        public static function getAddons(): array
        {
            return self::$addons;
        }
    }
}
