<?php

namespace BookneticApp\Backend\Customers;

use BookneticApp\Backend\Base\Modules\IModule;
use BookneticApp\Backend\Customers\Controllers\CustomerAjaxController;
use BookneticApp\Backend\Customers\Controllers\CustomerCategoryAjaxController;
use BookneticApp\Backend\Customers\Controllers\CustomerCategoryController;
use BookneticApp\Backend\Customers\Controllers\CustomerController;
use BookneticApp\Backend\Customers\Controllers\CustomerRestController;
use BookneticApp\Backend\Customers\Mappers\CustomerMapper;
use BookneticApp\Backend\Customers\Repositories\CustomerAppointmentRepository;
use BookneticApp\Backend\Customers\Repositories\CustomerCategoryRepository;
use BookneticApp\Backend\Customers\Repositories\CustomerRepository;
use BookneticApp\Backend\Customers\Services\CustomerCategoryService;
use BookneticApp\Backend\Customers\Services\CustomerService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\RestRoute;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\UI\MenuUI;
use ReflectionException;

class CustomerModule implements IModule
{
    public static function registerDependencies(): void
    {
        Container::addBulk([
            CustomerController::class,
            CustomerAjaxController::class,
            CustomerService::class,
            CustomerRepository::class,
            CustomerAppointmentRepository::class,
            CustomerMapper::class,

            CustomerCategoryController::class,
            CustomerCategoryAjaxController::class,
            CustomerCategoryService::class,
            CustomerCategoryRepository::class,

            CustomerRestController::class
        ]);
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRoutes(): void
    {
        if (!Capabilities::tenantCan('customers') || !Capabilities::userCan('customers')) {
            return;
        }

        Route::get('customers', Container::get(CustomerController::class));
        Route::post('customers', Container::get(CustomerAjaxController::class));

        Route::get('customer_categories', Container::get(CustomerCategoryController::class));
        Route::post('customer_categories', Container::get(CustomerCategoryAjaxController::class));
    }

    /**
     * @throws ReflectionException
     */
    public static function registerRestRoutes(): void
    {
        RestRoute::get('/customers', [Container::get(CustomerRestController::class), 'getAll']);
        RestRoute::get('/customer/info/(?P<id>\d+)', [Container::get(CustomerRestController::class), 'get']);
        RestRoute::post('/customer/delete', [Container::get(CustomerRestController::class), 'delete']);
        RestRoute::post('/customer/create', [Container::get(CustomerRestController::class), 'create']);
        RestRoute::post('/customer/edit', [Container::get(CustomerRestController::class), 'update']);
    }

    public static function registerPermissions(): void
    {
        Capabilities::register('customers', bkntc__('Customers module'));
        Capabilities::register('customers_add', bkntc__('Add new'), 'customers');
        Capabilities::register('customers_edit', bkntc__('Edit'), 'customers');
        Capabilities::register('customers_delete', bkntc__('Delete'), 'customers');
        Capabilities::register('customers_import', bkntc__('Export & Import'), 'customers');
        Capabilities::register('customers_allow_to_login', bkntc__('Allow to login'), 'customers');
        Capabilities::register('customers_delete_wordpress_account', bkntc__('Allow to delete associated WordPress account'), 'customers');

        Capabilities::register('customer_categories', bkntc__('Customers module'));
        Capabilities::register('customer_category_add', bkntc__('Add new'), 'customer_categories');
        Capabilities::register('customer_category_edit', bkntc__('Edit'), 'customer_categories');
        Capabilities::register('customer_category_delete', bkntc__('Delete'), 'customer_categories');
        Capabilities::register('customer_category_import', bkntc__('Export & Import'), 'customer_categories');
    }

    public static function registerMenu(): void
    {
        if (!Capabilities::tenantCan('customers') || !Capabilities::userCan('customers')) {
            return;
        }

        MenuUI::get('customers')
            ->setTitle(bkntc__('Customers'))
            ->setIcon('fa fa-users')
            ->setPriority(500);

        MenuUI::get('customers')
            ->subItem('customer_categories')
            ->setTitle(bkntc__('Customer Categories'))
            ->setIcon('fa fa-id-badge')
            ->setPriority(501);
    }
}
