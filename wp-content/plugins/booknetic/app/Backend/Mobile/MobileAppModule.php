<?php

namespace BookneticApp\Backend\Mobile;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\Controllers\BillingAjaxController;
use BookneticApp\Backend\Mobile\Controllers\PlanController;
use BookneticApp\Backend\Mobile\Controllers\SeatAjaxController;
use BookneticApp\Backend\Mobile\Controllers\SettingsAjaxController;
use BookneticApp\Backend\Mobile\Controllers\SubscriptionAjaxController;
use BookneticApp\Backend\Mobile\Controllers\MobileAppController;
use BookneticApp\Backend\Mobile\Services\PlanService;
use BookneticApp\Backend\Mobile\Services\SeatService;
use BookneticApp\Backend\Mobile\Services\SubscriptionService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;
use BookneticApp\Providers\UI\MenuUI;
use ReflectionException;

class MobileAppModule implements IModule
{
    /**
     * @throws ReflectionException
     */
    public static function registerRoutes(): void
    {
        if (Helper::isSaaSVersion() || !Capabilities::userCan('mobile_app')) {
            return;
        }

        Container::addBulk([
            FSCodeMobileAppClient::class,

            SubscriptionService::class,
            PlanService::class,
            SeatService::class,

            MobileAppController::class,
            SubscriptionAjaxController::class,
            SeatAjaxController::class,
            SettingsAjaxController::class,
            PlanController::class,
            BillingAjaxController::class
        ]);

        Route::get('mobile_app', Container::get(MobileAppController::class));
        Route::get('mobile_app_plan', Container::get(PlanController::class));
        Route::get('mobile_app_billing', Container::get(BillingAjaxController::class));

        Route::post('mobile_app_plan', Container::get(PlanController::class));
        Route::post('mobile_app_settings', Container::get(SettingsAjaxController::class));
        Route::post('mobile_app_subscription', Container::get(SubscriptionAjaxController::class));
        Route::post('mobile_app_seat', Container::get(SeatAjaxController::class));
    }

    public static function registerPermissions(): void
    {
        Capabilities::register('mobile_app', bkntc__('Mobile app module'));
        Capabilities::register('mobile_app_save_settings', bkntc__('Mobile app settings'));
        Capabilities::register('mobile_app_manage_additional_seats', bkntc__('Mobile app additional seats'), 'mobile_app');
        Capabilities::register('mobile_app_preview_proration', bkntc__('Mobile app additional seats preview proration'), 'mobile_app');
        Capabilities::register('mobile_app_cancel_subscription', bkntc__('Mobile app additional seats cancel subscription'), 'mobile_app');
        Capabilities::register('mobile_app_unassign_seat', bkntc__('Mobile app additional seats unassign seat'), 'mobile_app');
        Capabilities::register('mobile_app_logout', bkntc__('Mobile app additional seats logout user'), 'mobile_app');
        Capabilities::register('mobile_app_manage_seats', bkntc__('Mobile app manage additional seats'), 'mobile_app');
    }

    public static function registerMenu(): void
    {
        if (!Capabilities::userCan('mobile_app') || Helper::isSaaSVersion()) {
            return;
        }

        MenuUI::get('mobile_app', AbstractMenuUI::MENU_TYPE_TOP_RIGHT)
            ->setTitle(bkntc__('Mobile App'))
            ->setIcon('fa fa-mobile')
            ->setPriority(400);
    }

    public static function registerTenantPermissions(): void
    {
        //        Capabilities::registerTenantCapability('mobile-app', bkntc__('Mobile App module'));
    }
}
