<?php

namespace BookneticApp\Backend\Customers\Services;

use BookneticApp\Backend\Customers\DTOs\Request\CustomerFilterRequest;
use BookneticApp\Backend\Customers\Exceptions\CSVFileException;
use BookneticApp\Backend\Customers\Exceptions\CustomerHasAppointmentException;
use BookneticApp\Backend\Customers\Exceptions\FillRequiredFieldsException;
use BookneticApp\Backend\Customers\Exceptions\MultipleFieldsDetectedExceptions;
use BookneticApp\Backend\Customers\Mappers\CustomerMapper;
use BookneticApp\Backend\Customers\DTOs\Request\CustomerRequest;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Backend\Customers\Exceptions\CustomerNotFoundException;
use BookneticApp\Backend\Customers\Repositories\CustomerAppointmentRepository;
use BookneticApp\Backend\Customers\Repositories\CustomerCategoryRepository;
use BookneticApp\Backend\Customers\Repositories\CustomerRepository;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;

class CustomerService
{
    private CustomerRepository $repository;
    private CustomerAppointmentRepository $customerAppointmentRepository;

    private CustomerCategoryRepository $customerCategoryRepository;

    private CustomerMapper $mapper;

    public function __construct(
        CustomerRepository $repository,
        CustomerAppointmentRepository $customerAppointmentRepository,
        CustomerCategoryRepository $customerCategoryRepository,
        CustomerMapper $mapper
    ) {
        $this->repository = $repository;
        $this->customerAppointmentRepository = $customerAppointmentRepository;
        $this->customerCategoryRepository = $customerCategoryRepository;
        $this->mapper = $mapper;
    }

    /**
     * @throws InvalidCustomerDataException
     */
    public function handleImageUpload(array $image): string
    {
        if (empty($image['tmp_name'])) {
            return '';
        }

        $pathInfo          = pathinfo($image['name']);
        $extension         = strtolower($pathInfo['extension']);
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new InvalidCustomerDataException(bkntc__('Only JPG and PNG images allowed!'));
        }

        $newFileName = md5(base64_encode(rand(1, 9999999) . microtime(true))) . '.' . $extension;
        $filePath    = Helper::uploadedFile($newFileName, 'Customers');

        move_uploaded_file($image['tmp_name'], $filePath);

        return $newFileName;
    }

    /**
     * @throws CustomerNotFoundException
     */
    public function get(int $id): CustomerResponse
    {
        $customer = $this->repository->get($id);

        if ($customer === null) {
            throw new CustomerNotFoundException();
        }

        $customer->profile_image_url = Helper::profileImage($customer->profile_image, 'Customers');

        return $this->mapper->toResponse($customer);
    }

    public function create(CustomerRequest $request): int
    {
        if ($request->getCategoryId() === 0) {
            $customerCategory = $this->customerCategoryRepository->getDefaultCategory();

            if ($customerCategory !== null) {
                $request->setCategoryId($customerCategory->id);
            }
        }

        $id = $this->repository->create([
            'user_id' => $request->getUserId(),
            'first_name' => $request->getFirstName(),
            'last_name' => $request->getLastName(),
            'email' => $request->getEmail(),
            'phone_number' => $request->getPhoneNumber(),
            'birthdate' => $request->getBirthDate(),
            'notes' => $request->getNotes(),
            'profile_image' => $request->getProfileImage(),
            'gender' => $request->getGender(),
            'created_at' => $request->getCreatedAt(),
            'created_by' => $request->getCreatedBy(),
            'category_id' => $request->getCategoryId()
        ]);

        do_action('bkntc_customer_created', $id, $request->getWpUserPassword());
        do_action('bkntc_customer_saved', $id);

        return $id;
    }

    /**
     * @throws CustomerNotFoundException
     */
    public function update(int $id, CustomerRequest $request): int
    {
        $customer = $this->repository->get($id);

        if ($customer === null) {
            throw new CustomerNotFoundException();
        }

        do_action('bkntc_customer_before_edit', $id);

        $data = [
            'user_id' => $request->getUserId(),
            'first_name' => $request->getFirstName(),
            'last_name' => $request->getLastName(),
            'email' => $request->getEmail(),
            'phone_number' => $request->getPhoneNumber(),
            'birthdate' => $request->getBirthDate(),
            'notes' => $request->getNotes(),
            'profile_image' => $request->getProfileImage() ?? $customer->profile_image,
            'gender' => $request->getGender(),
            'category_id' => $request->getCategoryId(),
        ];

        $this->repository->update($id, $data);

        do_action('bkntc_customer_saved', $id);

        return $id;
    }

    public function getCustomerCountByEmail($email): int
    {
        return $this->repository->getCustomerCountByEmail($email);
    }

    /**
     * @throws CustomerHasAppointmentException
     * @throws InvalidCustomerDataException
     */
    public function deleteAll($ids, $deleteWpUser): void
    {
        if (empty($ids)) {
            throw new InvalidCustomerDataException(bkntc__('Please select at least one user!'));
        }

        $deleteWpUser = $deleteWpUser === 1 && (Permission::isAdministrator() || Capabilities::userCan('customers_delete_wordpress_account'));

        // check if appointment exist
        $checkAppointments = $this->customerAppointmentRepository->getAppointmentCount($ids);
        if ($checkAppointments > 0) {
            throw new CustomerHasAppointmentException();
        }

        foreach ($ids as $id) {
            $customerInf = $this->repository->get($id);

            if ($customerInf === null) {
                continue;
            }
            do_action('bkntc_customer_deleted', $id);

            if ($customerInf->user_id > 0) {
                $userData = get_userdata($customerInf->user_id);

                $customerCountForWPUser = $this->repository->getCustomerCountByWpUserId($customerInf->user_id);

                if ($userData && $customerCountForWPUser === 1 && in_array('booknetic_customer', $userData->roles)) {
                    require_once ABSPATH.'wp-admin/includes/user.php';
                    if ($deleteWpUser && count($userData->roles) === 1) {
                        wp_delete_user($customerInf->user_id);
                    } else {
                        $userData->remove_role('booknetic_customer');
                    }
                }
            }

            $this->repository->delete($id);
        }
    }

    public function canBeCustomer($email): bool
    {
        return !
        (
            ($wp_user = get_user_by('email', $email))
            &&
            (
                in_array('booknetic_staff', $wp_user->roles)
                ||
                in_array('booknetic_saas_tenant', $wp_user->roles)
                ||
                in_array('administrator', $wp_user->roles)
            )
        );
    }

    /**
     * @throws MultipleFieldsDetectedExceptions
     * @throws CSVFileException
     * @throws FillRequiredFieldsException
     */
    public function importCustomer(array $file): void
    {
        $delimiter = Post::string('delimiter', ';', [';', ',']);
        $fields = Post::string('fields', '');

        $fields1 = [];

        foreach (explode(',', $fields) as $fieldName) {
            if (in_array($fieldName, ['first_name', 'last_name', 'email', 'phone_number', 'gender', 'birthdate', 'notes'])) {
                $fields1[] = $fieldName;
            }
        }

        if (empty($fields1)) {
            throw new FillRequiredFieldsException();
        }

        $fieldsCount = count($fields1);

        if (!(is_string($file['tmp_name']))) {
            throw new CSVFileException();
        }

        $csvFile = $file['tmp_name'];

        $csvArray = [];

        $fileResource = fopen($csvFile, 'rb');
        while (($result = fgetcsv($fileResource, 0, $delimiter)) !== false) {
            if (count($result) > $fieldsCount) {
                throw new MultipleFieldsDetectedExceptions();
            }

            $csvArray[] = $result;
        }

        fclose($fileResource);

        foreach ($csvArray as $rows) {
            $insertData = [];

            foreach ($rows as $fieldNum => $data) {
                $fieldName = $fields1[$fieldNum];

                $insertData[$fieldName] = $data === '-' ? '' : $data;
            }

            // check if email is correct...
            if (!empty($insertData['email']) && !filter_var($insertData['email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!empty($insertData['phone_number']) && strpos($insertData['phone_number'], '+') !== 0) {
                $insertData['phone_number'] = '+' . $insertData['phone_number'];
            }

            if (isset($insertData['birthdate'])) {
                $insertData['birthdate'] = Date::isValid($insertData['birthdate']) ? $insertData['birthdate'] : str_replace('/', '-', $insertData['birthdate']);
                $insertData['birthdate'] = empty($insertData['birthdate']) ? null : Date::dateSQL($insertData['birthdate']);
            }

            $insertData['created_by'] = Permission::userId();

            $this->repository->create($insertData);
        }
    }

    /**
     * @return CustomerResponse[]
     */
    public function getAll(CustomerFilterRequest $request): array
    {
        $customersData = $this->repository->getAll($request);

        $customers = $customersData['data'];

        foreach ($customers as $customer) {
            $customer->last_appointment_date = Date::format('Y-m-d H:i:s', $customer->last_appointment_date);
            $customer->profile_image_url = Helper::profileImage($customer->profile_image_url, 'Customers');
        }

        $customers = $this->mapper->toListResponse($customers);

        return [
            'total' => $customersData['total'],
            'data'  => $customers
        ];
    }
}
