<?php

namespace BookneticApp\Backend\Customers\Controllers;

use BookneticApp\Backend\Customers\Exceptions\CustomerHasAppointmentException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;
use BookneticApp\Backend\Customers\Services\CustomerService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;

class CustomerController extends Controller
{
    private CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    public function index(): void
    {
        Capabilities::must('customers');

        $lastAppDateSubQuery = Appointment::query()
            ->where('customer_id', '=', DB::field('id', 'customers'))
            ->select('created_at', true)
            ->orderBy('created_at desc')
            ->limit(1);
        $category = CustomerCategory::query()
            ->where('id', '=', DB::field('category_id', 'customers'))
            ->select('name as category', true)
            ->limit(1);

        $dataTable = new DataTableUI(
            Customer::query()
            ->select('*')
            ->selectSubQuery($lastAppDateSubQuery, 'last_appointment_date')
            ->selectSubQuery($category, 'category')
        );

        $dataTable->setTitle(bkntc__('Customers'));
        $dataTable->addNewBtn(bkntc__('ADD CUSTOMER'));

        if (Capabilities::userCan('customers_import')) {
            $dataTable->activateExportBtn();
            $dataTable->activateImportBtn();
        }

        $dataTable->addAction('info', bkntc__('Info'));

        $dataTable->addAction('edit', bkntc__('Edit'));

        $dataTable->addAction(
            'delete',
            bkntc__('Delete'),
            [$this, '_delete'],
            AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK
        );
        $dataTable->searchBy(["CONCAT(first_name, ' ', last_name)", 'email', 'phone_number']);

        $dataTable->addColumns(bkntc__('ID'), 'id', [], true);
        $dataTable->addColumns(bkntc__('CUSTOMER NAME'), function ($customer) {
            return Helper::profileCard(
                $customer['first_name'] . ' ' . $customer['last_name'],
                $customer['profile_image'],
                $customer['email'],
                'Customers'
            );
        }, ['is_html' => true, 'order_by_field' => "first_name,last_name"], true);
        $dataTable->addColumns(bkntc__('Category '), 'category');

        $dataTable->addColumnsForExport(bkntc__('First name'), 'first_name');
        $dataTable->addColumnsForExport(bkntc__('Last name'), 'last_name');
        $dataTable->addColumnsForExport(bkntc__('Email'), 'email');

        $dataTable->addColumns(bkntc__('PHONE'), 'phone_number');
        $dataTable->addColumns(bkntc__('LAST APPOINTMENT'), 'last_appointment_date', ['type' => 'date']);
        $dataTable->addColumns(bkntc__('GENDER'), function ($customer) {
            return bkntc__(ucfirst((string)($customer['gender'] ?? '')));
        }, ['is_html' => true, 'order_by_field' => "gender"], true);
        $dataTable->addColumns(bkntc__('Date of birth'), 'birthdate', ['type' => 'date']);

        $dataTable->addColumnsForExport(bkntc__('Note'), 'notes');

        $table = $dataTable->renderHTML();

        add_filter('bkntc_localization', static function ($localization) {
            $localization['delete_associated_wordpress_account'] = bkntc__('Delete associated WordPress account');

            return $localization;
        });

        $this->view('index', ['table' => $table]);
    }

    /**
     * @throws CapabilitiesException
     * @throws CustomerHasAppointmentException|InvalidCustomerDataException
     */
    public function _delete(array $ids): void
    {
        Capabilities::must('customers_delete');

        $deleteWpUser = Post::int('delete_wp_user', 1);

        $this->service->deleteAll($ids, $deleteWpUser);
    }
}
