<?php

namespace BookneticApp\Backend\Staff;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Staff\Controllers\StaffAjaxController;
use BookneticApp\Backend\Staff\Controllers\StaffController;
use BookneticApp\Backend\Staff\Controllers\StaffRestController;
use BookneticApp\Backend\Staff\Repository\StaffRepository;
use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class StaffModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            StaffRepository::class,
            StaffService::class,
            StaffRestController::class,
        ]);
    }
    public static function registerRoutes(): void
    {
        Route::get('staff', StaffController::class);
        Route::post('staff', StaffAjaxController::class);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('staffs');
        $controller = Container::get(StaffRestController::class);

        $router->get('', [$controller, 'gelAllActive']);
    }
}
