<?php

namespace BookneticApp\Backend\Customers\Controllers;

use BookneticApp\Backend\Customers\Services\CustomerCategoryService;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class CustomerCategoryController extends Controller
{
    private CustomerCategoryService $service;

    public function __construct(CustomerCategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        Capabilities::must('customer_categories');

        $dataTable = new DataTableUI(CustomerCategory::query()->select([
            CustomerCategory::getField("*"),
            DB::raw('COUNT(' . Customer::getField("category_id") . ') AS count')
        ])->leftJoin(Customer::getTableName(), ['id'], CustomerCategory::getField('id'), Customer::getField('category_id'))
            ->groupBy([CustomerCategory::getField('id')]));

        $dataTable->setIdFieldForQuery(CustomerCategory::getField('id'));

        $dataTable->setTitle(bkntc__('Customer Categories'));
        $dataTable->addNewBtn(bkntc__('ADD CUSTOMER CATEGORY'));

        $dataTable->addAction('info', bkntc__('Info'));

        $dataTable->addAction('edit', bkntc__('Edit'));

        $dataTable->addAction(
            'delete',
            bkntc__('Delete'),
            [$this, '_delete'],
            AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK
        );
        $dataTable->searchBy(['name']);

        $dataTable->addColumns(bkntc__('ID'), 'id', [], true);
        $dataTable->addColumns(bkntc__('CUSTOMER CATEGORY NAME'), 'name');
        $dataTable->addColumns(bkntc__('COLOR'), function ($customer_category) {
            return '<div class="appointment-status-icon ml-3" style="background-color: ' . htmlspecialchars($customer_category['color']) . ' "></div>';
        }, ['is_html' => true], true);
        $dataTable->addColumns(bkntc__('ICON'), function ($customer_category) {
            return '<i class="' . htmlspecialchars($customer_category['icon']) . '"></i>';
        }, ['is_html' => true], true);

        $dataTable->addColumns(bkntc__('IS DEFAULT'), function ($customer_category) {
            return ($customer_category['is_default'] ? '<i class="fa fa-star is_default" title="' . bkntc__('Default Role') . '"></i>' : '');
        }, ['is_html' => true], true);

        $dataTable->addColumns(bkntc__('CUSTOMERS COUNT'), 'count');

        $table = $dataTable->renderHTML();

        $this->view('category', ['table' => $table]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function _delete(array $ids): void
    {
        Capabilities::must('customer_category_delete');

        $this->service->deleteAll($ids);
    }
}
