<?php

namespace BookneticApp\Backend\Services\Controllers;

use BookneticApp\Backend\Services\Exceptions\HasServiceInThisCategoryException;
use BookneticApp\Backend\Services\Exceptions\NoCategorySelectedException;
use BookneticApp\Backend\Services\Exceptions\RemoveSubCategoryException;
use BookneticApp\Backend\Services\Services\ServiceCategoryService;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\DataTableUI;

class ServiceCategoryController extends Controller
{
    private ServiceCategoryService $service;

    public function __construct()
    {
        $this->service = new ServiceCategoryService();
    }

    /**
     * @throws CapabilitiesException
     */
    public function index(): void
    {
        Capabilities::must('service_categories');

        $query = $this->service->getTenantQuery();

        $dataTable = new DataTableUI($query);

        $dataTable
            ->setIdFieldForQuery(ServiceCategory::getField('id'))
            ->setTitle(bkntc__('Service Categories'))
            ->addColumns(bkntc__('ID'), 'id')
            ->addColumns(bkntc__('Category Name'), 'name')
            ->addColumns(bkntc__('Parent Category'), 'parent_name')
            ->addNewBtn(bkntc__('ADD Category'))
            ->addAction('edit', bkntc__('Edit'))
            ->addAction('delete', bkntc__('Delete'), [ $this, '_delete' ]);

        $dataTable->searchBy([ServiceCategory::getField('name'), 'parent_category.name']);

        $this->view('service-category', [
            'table' => $dataTable->renderHTML()
        ]);
    }

    /**
     * @throws NoCategorySelectedException
     * @throws CapabilitiesException
     * @throws RemoveSubCategoryException|HasServiceInThisCategoryException
     */
    public function _delete()
    {
        Capabilities::must('services_delete_category');

        $ids = Post::array('ids');

        $this->service->delete($ids);
    }
}
