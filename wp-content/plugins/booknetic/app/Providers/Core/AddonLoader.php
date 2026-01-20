<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Providers\Helpers\Helper;

class AddonLoader
{
    protected array $syncedData = [];

    public function __construct()
    {
        $this->initAddonData();

        if ($this->shouldBlock()) {
            return;
        }

        LocalizationService::loadTextdomain(static::getAddonSlug());

        add_action('bkntc_init', [ $this, 'init' ]);
        add_action('bkntc_backend', [ $this, 'initBackend' ]);
        add_action('bkntc_frontend', [ $this, 'initFrontend' ]);
        add_action('bkntcsaas_init', [ $this, 'initSaaS' ]);
        add_action('bkntcsaas_backend', [ $this, 'initSaaSBackend' ]);
        add_action('bkntcsaas_frontend', [ $this, 'initSaaSFrontend' ]);
    }

    public function shouldBlock(): bool
    {
        if (empty($this->syncedData['action'])) {
            return false;
        }

        if ($this->syncedData['action'] !== 'block') {
            return false;
        }

        return true;
    }

    final public static function getAddonTextDomain()
    {
        return LocalizationService::getTextdomain(static::getAddonSlug());
    }

    final public static function getAddonSlug()
    {
        $calledAddonClass = get_called_class();

        //todo://Implement caching here
        $reflection = new \ReflectionClass($calledAddonClass);

        $partitions = explode('/', plugin_basename($reflection->getFileName()));

        return reset($partitions);
    }

    final public static function loadAsset($assetUrl)
    {
        if (preg_match('/\.(js|css)$/i', $assetUrl)) {
            if (! function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugins    = get_plugins(DIRECTORY_SEPARATOR . self::getAddonSlug());
            $plugin     = reset($plugins);

            $assetUrl .= '?v=' . ($plugin[ 'Version' ] ?? uniqid());
        }

        return Helper::assets($assetUrl, self::getAddonSlug(), true);
    }

    final public function setFrontendAjaxController($controllerClass)
    {
        Frontend::initAjaxRequests($controllerClass);
    }

    public static function getVersion()
    {
        $plugin_data = get_file_data(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . static::getAddonSlug() . DIRECTORY_SEPARATOR . 'init.php', array('Version' => 'Version'), false);

        return $plugin_data[ 'Version' ] ?? '1.0.0';
    }

    public function init()
    {
    }
    public function initBackend()
    {
    }
    public function initFrontend()
    {
    }
    public function initSaaS()
    {
    }
    public function initSaaSBackend()
    {
    }
    public function initSaaSFrontend()
    {
    }

    private function initAddonData()
    {
        $syncedAddons = Helper::getOption('synced_addons', [], false);

        if (empty($syncedAddons) || !is_array($syncedAddons)) {
            return;
        }

        if (empty($syncedAddons[static::getAddonSlug()])) {
            return;
        }

        $this->syncedData = $syncedAddons[static::getAddonSlug()];
    }
}
