<?php

namespace BookneticApp\Backend\Customers\Controllers;

use BookneticApp\Backend\Customers\DTOs\Request\CustomerCategoryRequest;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerCategoryResponse;
use BookneticApp\Backend\Customers\Exceptions\CustomerCategoryNotFoundException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerCategoryDataException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;
use BookneticApp\Backend\Customers\Services\CustomerCategoryService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\TabUI;

class CustomerCategoryAjaxController extends Controller
{
    private CustomerCategoryService $service;

    public function __construct(CustomerCategoryService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws CapabilitiesException
     * @throws CustomerCategoryNotFoundException
     */
    public function add_new()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('customer_category_edit');

            $customerCategory = $this->service->get($id);
        } else {
            Capabilities::must('customer_category_add');

            $customerCategory = CustomerCategoryResponse::createEmpty();
        }

        TabUI::get('customers_add_new_category')
            ->item('customer_category_details')
            ->setTitle(bkntc__('Customer Category details'))
            ->addView(__DIR__ . '/view/tab/add_customer_category_details.php')
            ->setPriority(1);

        $uncategorizedCustomerCount = $this->service->getUncategorizedCustomerCount();

        return $this->modalView('add_new_category', [
            'customerCategory' => $customerCategory,
            'uncategorizedCustomerCount' => $uncategorizedCustomerCount
        ]);
    }

    /**
     * @throws CustomerCategoryNotFoundException
     * @throws CapabilitiesException
     */
    public function info()
    {
        Capabilities::must('customer_categories');

        $id = Post::int('id');

        $response = $this->service->get($id);

        TabUI::get('customer_category_info')
            ->item('info_category')
            ->setTitle(bkntc__('Customer Category Info'))
            ->addView(__DIR__ . '/view/tab/customer_category_info_details.php')
            ->setPriority(1);

        return $this->modalView('info_category', $response);
    }

    /**
     * @throws InvalidCustomerCategoryDataException
     * @throws InvalidCustomerDataException
     * @throws CapabilitiesException
     */
    public function create()
    {
        Capabilities::must('customer_category_add');

        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->create($request);

        return $this->response(true, [
            'customer_category_id' => $id
        ]);
    }

    /**
     * @throws InvalidCustomerCategoryDataException
     * @throws InvalidCustomerDataException|CapabilitiesException
     */
    public function update()
    {
        Capabilities::must('customer_category_edit');

        $id = Post::int('id');
        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->update($id, $request);

        return $this->response(true, [
            'customer_category_id' => $id
        ]);
    }

    /**
     * @throws InvalidCustomerCategoryDataException
     * @throws InvalidCustomerDataException
     */
    public function prepareSaveRequestDTO(): CustomerCategoryRequest
    {
        $name = Post::string('name');
        $icon = Post::string('icon');
        $color = Post::string('color');
        $isDefault = Post::int('isDefault');
        $note = Post::string('note');
        $applyToUncategorizedCustomers = Post::bool('applyToUncategorizedCustomers');

        if (empty($name)) {
            throw new InvalidCustomerCategoryDataException(bkntc__('The name field is required!'));
        }

        $request = new CustomerCategoryRequest();

        $request->setName(trim($name))
            ->setColor($color)
            ->setIcon($icon)
            ->setNote($note)
            ->setApplyToUncategorizedCustomers($applyToUncategorizedCustomers)
            ->setIsDefault((bool)$isDefault);

        return $request;
    }
}
