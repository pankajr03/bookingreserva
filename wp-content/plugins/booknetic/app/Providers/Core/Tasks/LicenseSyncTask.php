<?php

namespace BookneticApp\Providers\Core\Tasks;

use BookneticApp\Providers\Core\Tasks\Abstracts\TaskInterface;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\Helpers\Helper;
use Exception;

class LicenseSyncTask implements TaskInterface
{
    private FSCodeAPIClient $apiClient;

    public function __construct(FSCodeAPIClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function canExecute(): bool
    {
        return Helper::processRuntimeController('license_check', 10 * 60 * 60);
    }

    public function execute(): void
    {
        Helper::setOption('license_last_checked_time', time(), false);

        $product = Helper::isSaaSVersion() ? 'booknetic-saas' : 'booknetic';

        try {
            $response = $this->apiClient->requestNew($product.'/product/get_notifications', 'POST');
            $result = $response->getData();

            if ($result['status'] === false) {
                Helper::setOption('plugin_disabled', '1', false);

                return;
            }

            $data = $result['data'] ?? [];
            $action = $data['action'] ?? null;
            $message = $data['message'] ?? null;
            $removeLicense = $data['remove_license'] ?? false;

            if (empty($action)) {
                return;
            }

            if ($action === 'empty') {
                Helper::setOption('plugin_alert', '', false);
                Helper::setOption('plugin_disabled', '0', false);
            } elseif ($action === 'warning') {
                if (!empty($message)) {
                    Helper::setOption('plugin_alert', $message, false);
                }
                Helper::setOption('plugin_disabled', '0', false);
            } elseif ($action === 'disable') {
                if (!empty($message)) {
                    Helper::setOption('plugin_alert', $message, false);
                }

                Helper::setOption('plugin_disabled', '1', false);
            } elseif ($action === 'error') {
                if (!empty($message)) {
                    Helper::setOption('plugin_alert', $message, false);
                }

                Helper::setOption('plugin_disabled', '2', false);
            }

            if ($removeLicense) {
                Helper::deleteOption('purchase_code', false);
            }
        } catch (Exception $e) {
        }
    }

    public function getTaskName(): string
    {
        return 'license_sync';
    }
}
