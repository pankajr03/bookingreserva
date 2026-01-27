<?php

namespace BookneticSaaS\Providers\Common;

use BookneticApp\Providers\IoC\Container;
use BookneticSaaS\Providers\Core\PluginInstaller;
use BookneticSaaS\Providers\FSCode\FSCodeAPIClientLite;

class PluginService
{
    private FSCodeAPIClientLite $apiClient;

    public function __construct()
    {
        $this->apiClient = new FSCodeAPIClientLite();
    }

    public function activate(string $licenseCode, string $email, int $subscribedToNewsletter, string $foundFrom): void
    {
        $checkLicense = $this->apiClient->request('booknetic-saas/product/check_license/'.$licenseCode);
        if (! ($checkLicense['status'] ?? false)) {
            throw new \RuntimeException(bkntcsaas__('Please enter the correct license code.'));
        }

        $regularUrl = FSCodeAPIClientLite::API_URL . 'booknetic/product/download/'.$licenseCode;
        $regularInstaller = new PluginInstaller($regularUrl, '/booknetic/init.php');

        if ($regularInstaller->install() === false) {
            throw new \RuntimeException(bkntcsaas__('An error occurred, please try again later'));
        }

        if (!class_exists(Container::class)) {
            require_once WP_PLUGIN_DIR.'/booknetic/init.php';
        }

        $regularPluginService = Container::get(\BookneticApp\Providers\Common\PluginService::class);
        $regularPluginService->activate($licenseCode, $email, $subscribedToNewsletter, $foundFrom);
    }
}
