<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Providers\Helpers\Helper;

class LicenseService
{
    public static function checkLicense(): ?bool
    {
        $alert    = Helper::getOption('plugin_alert', '', false);
        $disabled = Helper::getOption('plugin_disabled', '0', false);

        if ($disabled === '1') {
            return false;
        }

        if ($disabled === '2') {
            if (! empty($alert)) {
                echo $alert;
            }

            exit();
        }

        if (! empty($alert)) {
            add_action('admin_notices', static function () use ($alert) {
                echo '<div class="notice notice-error"><p>'.$alert.'</p></div>';
            });
        }

        return true;
    }
}
