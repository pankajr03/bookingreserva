<?php

namespace BookneticApp\Backend\Appearance;

use BookneticApp\Backend\Appearance\Controllers\AppearanceAjaxController;
use BookneticApp\Backend\Appearance\Controllers\AppearanceController;
use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Core\Capabilities;

class AppearanceModule implements IModule
{
    public static function registerRoutes(): void
    {
        if (! Capabilities::tenantCan('appearance')) {
            return;
        }

        Route::get('appearance', AppearanceController::class);
        Route::post('appearance', AppearanceAjaxController::class);
    }
}
