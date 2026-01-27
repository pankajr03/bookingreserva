<?php

namespace BookneticApp\Backend\Appointments;

use BookneticApp\Backend\Appointments\Controllers\AppointmentAjaxController;
use BookneticApp\Backend\Appointments\Controllers\AppointmentController;
use BookneticApp\Backend\Appointments\Controllers\AppointmentRestController;
use BookneticApp\Backend\Appointments\Middlewares\AppointmentMiddleware;
use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentPriceRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentRepository;
use BookneticApp\Backend\Appointments\Services\AppointmentDataTableService;
use BookneticApp\Backend\Appointments\Services\AppointmentService;
use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class AppointmentsModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            ServiceExtraRepository::class,
            AppointmentRepository::class,
            AppointmentPriceRepository::class,
            AppointmentExtraRepository::class,
            AppointmentService::class,
            AppointmentDataTableService::class,
            AppointmentController::class,
            AppointmentAjaxController::class,
            AppointmentRestController::class,
        ]);
    }
    /**
     * @throws ReflectionException
     */
    public static function registerRoutes(): void
    {
        Route::get('appointments', Container::get(AppointmentController::class))->middleware(AppointmentMiddleware::class);
        Route::post('appointments', Container::get(AppointmentAjaxController::class))->middleware(AppointmentMiddleware::class);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('appointments');
        $controller = Container::get(AppointmentRestController::class);
        $router->get('', [$controller, 'getAll']);
        $router->get('(?P<id>\d+)', [$controller, 'get']);
        $router->post('', [$controller, 'create']);
        $router->put('(?P<id>\d+)', [$controller, 'update']);
        $router->delete('(?P<id>\d+)', [$controller, 'delete']);
        $router->put('(?P<id>\d+)/change-status', [$controller, 'changeStatus']);

        $router->get('statuses', [$controller, 'getStatuses']);

        $router->get('available-times', [$controller, 'getAvailableTimes']);
    }
}
