<?php

namespace BookneticApp\Backend\Services;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Services\Controllers\ServiceRestController;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Backend\Services\Repositories\ServiceRepository;
use BookneticApp\Backend\Services\Services\ServiceCategoryService;
use BookneticApp\Backend\Services\Services\ServiceService;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\IoC\Container;
use ReflectionException;

class ServiceModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            ServiceRepository::class,
            ServiceExtraRepository::class,
            ServiceCategoryService::class,
            ServiceService::class,
            ServiceRestController::class,
        ]);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('services');
        $controller = Container::get(ServiceRestController::class);

        $router->get('', [$controller, 'getServices']);

        $router->get('categories', [$controller, 'getCategories']);

        $router->get('extras', [$controller, 'getExtras']);
    }
}
