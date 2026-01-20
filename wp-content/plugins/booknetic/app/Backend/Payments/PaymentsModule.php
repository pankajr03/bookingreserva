<?php

namespace BookneticApp\Backend\Payments;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Payments\Controllers\PaymentAjaxController;
use BookneticApp\Backend\Payments\Controllers\PaymentController;
use BookneticApp\Backend\Payments\Controllers\PaymentRestController;
use BookneticApp\Backend\Payments\Repositories\PaymentRepository;
use BookneticApp\Backend\Payments\Services\PaymentService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\RestGroup;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\UI\MenuUI;
use ReflectionException;

class PaymentsModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            PaymentRepository::class,
            PaymentService::class,
            PaymentController::class,
            PaymentAjaxController::class,
            PaymentRestController::class,
        ]);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRoutes(): void
    {
        if (!Capabilities::tenantCan('payments') && !Capabilities::userCan('payments')) {
            return;
        }

        Route::get('payments', Container::get(PaymentController::class));
        Route::post('payments', Container::get(PaymentAjaxController::class));
    }

    public static function registerPermissions(): void
    {
        Capabilities::register('payments', bkntc__('Payments module'));
        Capabilities::register('payments_edit', bkntc__('Edit payments'), 'payments');
    }

    public static function registerTenantPermissions(): void
    {
        Capabilities::registerTenantCapability('payments', bkntc__('Payments module'));
    }

    public static function registerMenu(): void
    {
        if (!Capabilities::tenantCan('payments') || !Capabilities::userCan('payments')) {
            return;
        }

        MenuUI::get('payments')
            ->setTitle(bkntc__('Payments'))
            ->setIcon('fa fa-credit-card')
            ->setPriority(400);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        $router = new RestGroup('payments');
        $controller = Container::get(PaymentRestController::class);

        $router->get('(?P<id>\d+)', [$controller, 'getInfo']);
        $router->put('(?P<id>\d+)', [$controller, 'editPaymentInfo']);
    }
}
