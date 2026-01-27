<?php

namespace BookneticApp\Backend\Customers\Controllers;

use BookneticApp\Backend\Customers\DTOs\Request\CustomerFilterRequest;
use BookneticApp\Backend\Customers\DTOs\Request\CustomerRequest;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Backend\Customers\Exceptions\AlreadyConnectedUserException;
use BookneticApp\Backend\Customers\Exceptions\AnotherCustomerWithSameEmailException;
use BookneticApp\Backend\Customers\Exceptions\CanNotBeCustomerException;
use BookneticApp\Backend\Customers\Exceptions\CanNotChangeEmailOrPasswordException;
use BookneticApp\Backend\Customers\Exceptions\CustomerHasAppointmentException;
use BookneticApp\Backend\Customers\Exceptions\CustomerNotFoundException;
use BookneticApp\Backend\Customers\Exceptions\CustomException;
use BookneticApp\Backend\Customers\Exceptions\EmptyPasswordException;
use BookneticApp\Backend\Customers\Exceptions\InvalidArgumentException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;
use BookneticApp\Backend\Customers\Exceptions\InvalidWordpressUserIdException;
use BookneticApp\Backend\Customers\Exceptions\SelectWordpressUserException;
use BookneticApp\Backend\Customers\Services\CustomerService;
use BookneticApp\Config;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\RestRequest;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class CustomerRestController
{
    private CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @param RestRequest $request
     * @return array
     */
    public function getAll(RestRequest $request): array
    {
        $skip = $request->param('skip', 0, RestRequest::TYPE_INTEGER);
        $limit = $request->param('limit', 12, RestRequest::TYPE_INTEGER);
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $orderByField = $request->param(
            'orderByField',
            '',
            RestRequest::TYPE_STRING,
            ['id', 'first_name', 'last_name', 'email', 'phone_number', 'birthdate', 'gender']
        );

        $orderDirection = $request->param('orderDirection', 'DESC', RestRequest::TYPE_STRING, ['ASC', 'DESC']);

        $filterRequest = new CustomerFilterRequest();

        $filterRequest->setSearch($search)
            ->setSkip($skip)
            ->setLimit($limit)
            ->setOrderBy($orderByField)
            ->setOrderDirection($orderDirection);

        $customersData = $this->service->getAll($filterRequest);

        return [
            'data' => $customersData['data'],
            'meta' => [
                'total' => $customersData['total'],
                'skip' => $skip,
                'limit' => $limit,
            ]
        ];
    }

    /**
     * @param RestRequest $request
     * @return CustomerResponse
     * @throws CustomerNotFoundException
     */
    public function get(RestRequest $request): CustomerResponse
    {
        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);

        $customer = $this->service->get($id);

        $customer->setProfileImage(Helper::profileImage($customer->getProfileImage(), 'Customers'));

        return $customer;
    }

    /**
     * @param RestRequest $request
     * @return array
     * @throws CustomerHasAppointmentException|InvalidCustomerDataException
     */
    public function delete(RestRequest $request): array
    {
        $ids = $request->param('ids', [], RestRequest::TYPE_ARRAY);
        $deleteWpUser = $request->param('delete_wp_user', 1, RestRequest::TYPE_INTEGER);

        $ids = array_map(static function ($id) {
            return (int)$id;
        }, $ids);

        $this->service->deleteAll($ids, $deleteWpUser);

        return [];
    }

    /**
     * @throws AlreadyConnectedUserException
     * @throws CustomerNotFoundException
     * @throws InvalidWordpressUserIdException
     * @throws InvalidCustomerDataException
     * @throws CanNotBeCustomerException
     * @throws SelectWordpressUserException
     * @throws EmptyPasswordException
     * @throws InvalidArgumentException
     * @throws CanNotChangeEmailOrPasswordException
     * @throws AnotherCustomerWithSameEmailException
     * @throws CustomException
     */
    public function create(RestRequest $request): array
    {
        $wpUserPassword = $request->param('wp_user_password', '', RestRequest::TYPE_STRING);

        $requestData = $this->prepareSaveRequestRestDTO($request);

        $id = $this->service->create($requestData, $wpUserPassword);

        return [
            'customer_id' => $id,
        ];
    }

    /**
     * @param RestRequest $request
     * @return array
     * @throws AlreadyConnectedUserException
     * @throws AnotherCustomerWithSameEmailException
     * @throws CanNotBeCustomerException
     * @throws CanNotChangeEmailOrPasswordException
     * @throws CustomException
     * @throws CustomerNotFoundException
     * @throws EmptyPasswordException
     * @throws InvalidArgumentException
     * @throws InvalidCustomerDataException
     * @throws InvalidWordpressUserIdException
     * @throws SelectWordpressUserException
     */
    public function update(RestRequest $request): array
    {
        $requestData = $this->prepareSaveRequestRestDTO($request);

        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);

        $id = $this->service->update($id, $requestData);

        return [
            'customer_id' => $id,
        ];
    }

    /**
     * @param RestRequest $request
     * @return CustomerRequest
     * @throws AlreadyConnectedUserException
     * @throws AnotherCustomerWithSameEmailException
     * @throws CanNotBeCustomerException
     * @throws CanNotChangeEmailOrPasswordException
     * @throws CustomException
     * @throws CustomerNotFoundException
     * @throws EmptyPasswordException
     * @throws InvalidArgumentException
     * @throws InvalidCustomerDataException
     * @throws InvalidWordpressUserIdException
     * @throws SelectWordpressUserException
     */
    private function prepareSaveRequestRestDTO(RestRequest $request): CustomerRequest
    {
        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);
        $wpUser = $request->param('wp_user', 0, RestRequest::TYPE_INTEGER);
        $firstName = $request->param('first_name', '', RestRequest::TYPE_STRING);
        $lastName = $request->param('last_name', '', RestRequest::TYPE_STRING);
        $gender = $request->param('gender', '', RestRequest::TYPE_STRING, ['male', 'female']);
        $birthday = $request->param('birthday', '', RestRequest::TYPE_STRING);
        $phone = $request->param('phone', '', RestRequest::TYPE_STRING);
        $email = $request->param('email', '', RestRequest::TYPE_EMAIL);
        $allowLogin = $request->param('allow_customer_to_login', 0, RestRequest::TYPE_INTEGER, ['0', '1']);
        $wpUserUseExisting = $request->param('wp_user_use_existing', 'yes', RestRequest::TYPE_STRING, ['yes', 'no']);
        $wpUserPassword = $request->param('wp_user_password', '', RestRequest::TYPE_STRING);
        $note = $request->param('note', '', RestRequest::TYPE_STRING);
        $runWorkflows = $request->param('run_workflows', 1, RestRequest::TYPE_INTEGER);

        Config::getWorkflowEventsManager()->setEnabled($runWorkflows === 1);

        $showOnlyName = Helper::getOption('separate_first_and_last_name', 'on') === 'off';

        if (! $this->service->canBeCustomer($email)) {
            throw new CanNotBeCustomerException();
        }

        $isEdit = $id > 0;
        $getOldInf = null;

        if ($isEdit) {
            $getOldInf = $this->service->get($id);
        }

        $customerOnMultiTenants = false;
        if ($isEdit && Helper::isSaaSVersion()) {
            $customerOnMultiTenants = Customer::noTenant()->where('email', $getOldInf->getEmail())->count() > 1;
        }

        if ($wpUser > 0) {
            $selectedWpUser = Customer::query()->where('user_id', $wpUser)->fetch();

            if ($isEdit) {
                if ($selectedWpUser !== null && $getOldInf->getUserId() !== $wpUser) {
                    throw new AlreadyConnectedUserException($selectedWpUser->id);
                }
            } elseif ($selectedWpUser !== null) {
                throw new AlreadyConnectedUserException($selectedWpUser->id);
            }
        }

        if ($isEdit && $customerOnMultiTenants && ($getOldInf->getEmail() !== $email || (bool)$allowLogin != email_exists($getOldInf->getEmail()) || ! empty($wpUserPassword))) {
            throw new CanNotChangeEmailOrPasswordException();
        }

        $isEmailRequired = Helper::getOption('set_email_as_required', 'on') === 'on';
        $isPhoneRequired = Helper::getOption('set_phone_as_required', 'off') === 'on';

        if ($isEmailRequired && empty($email)) {
            throw new InvalidCustomerDataException(bkntc__('The email field is required!'));
        }

        if ($isPhoneRequired && empty($phone)) {
            throw new InvalidCustomerDataException(bkntc__('The phone field is required!'));
        }

        if ($email !== '' && (! $isEdit || $email !== $getOldInf->getEmail())) {
            if (Customer::where('email', $email)->count() > 0) {
                throw new AnotherCustomerWithSameEmailException();
            }

            if ($allowLogin && (email_exists($email) !== false || username_exists($email) !== false)) {
                if ($wpUserUseExisting !== 'yes') {
                    throw new AnotherCustomerWithSameEmailException();
                }

                if ($wpUser <= 0) {
                    throw new InvalidWordpressUserIdException();
                }

                $wpUserData = get_userdata($wpUser); //false or user data object

                if (! $wpUserData) {
                    throw new InvalidWordpressUserIdException();
                }

                if (! in_array('booknetic_customer', $wpUserData->roles)) {
                    throw new InvalidArgumentException();
                }
            }
        }

        if (!Permission::isAdministrator()) {
            $wpUser = $isEdit ? $getOldInf->getUserId() : 0;
        } elseif ($allowLogin === 1) {
            if ($wpUserUseExisting === 'yes' && !($wpUser > 0)) {
                throw new SelectWordpressUserException();
            }

            if ($wpUserUseExisting === 'yes' && $wpUser > 0) {
                get_userdata($wpUser)->add_role('booknetic_customer');
            } elseif ($wpUserUseExisting === 'no') {
                if (empty($wpUserPassword) && !($isEdit && $getOldInf->getUserId() > 0)) {
                    throw new EmptyPasswordException();
                }

                if ($isEdit && $getOldInf->getUserId() > 0) {
                    $wpUser = $getOldInf->getUserId();
                    $updateData = [];

                    if ($email !== $getOldInf->getEmail()) {
                        $updateData['user_login'] = $email;
                        $updateData['user_email'] = $email;
                    }

                    if ($firstName !== $getOldInf->getFirstName() || $lastName !== $getOldInf->getLastName()) {
                        $updateData['display_name'] = trim($firstName . ' ' . $lastName);
                        $updateData['first_name'] = $firstName;
                        $updateData['last_name'] = $lastName;
                    }

                    if (!empty($wpUserPassword)) {
                        $updateData['user_pass'] = $wpUserPassword;
                    }

                    if (!empty($updateData)) {
                        $updateData['ID'] = $getOldInf->getUserId();
                        $userData = wp_update_user($updateData);

                        if (isset($updateData['user_login'])) {
                            DB::DB()->update(DB::DB()->users, ['user_login' => $email], ['ID' => $updateData['ID']]);
                        }

                        if (is_wp_error($userData)) {
                            throw new CustomException($userData->get_error_message());
                        }
                    }
                } else {
                    $wpUser = wp_insert_user([
                        'user_login'	=>	$email,
                        'user_email'	=>	$email,
                        'display_name'	=>	trim($firstName . ' ' . $lastName),
                        'first_name'	=>	$firstName,
                        'last_name'		=>	$lastName,
                        'role'			=>	'booknetic_customer',
                        'user_pass'		=>	$wpUserPassword
                    ]);

                    if (is_wp_error($wpUser)) {
                        throw new InvalidCustomerDataException($wpUser->get_error_message());
                    }
                }
            }
        } else {
            if ($isEdit && $getOldInf->getUserId() > 0) {
                $userData = get_userdata($getOldInf->getUserId());
                if ($userData && in_array('booknetic_customer', $userData->roles)) {
                    require_once ABSPATH.'wp-admin/includes/user.php';
                    wp_delete_user($getOldInf->getUserId());
                }
            }

            $wpUser = 0;
        }

        $profileImage = '';

        if (isset($_FILES['image']) && is_string($_FILES['image']['tmp_name'])) {
            $profileImage =  $this->service->handleImageUpload($_FILES['image']);
        }
        $requestData = new CustomerRequest();

        $requestData->setFirstName(trim($firstName))
            ->setGender($gender)
            ->setBirthdate(empty($birthday) ? null : Date::reformatDateFromCustomFormat($birthday))
            ->setEmail($email)
            ->setPhoneNumber($phone)
            ->setNotes($note)
            ->setUserId(max($wpUser, 0));

        if (!$showOnlyName) {
            $requestData->setLastName(trim($lastName));
        }

        if ($isEdit) {
            if (!empty($profileImage)) {
                $requestData->setProfileImage($profileImage);
                if (!empty($getOldInf->getProfileImage())) {
                    $filePath = Helper::uploadedFile($getOldInf->getProfileImage(), 'Customers');

                    if (is_file($filePath) && is_writable($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        } else {
            if (!empty($profileImage)) {
                $requestData->setProfileImage($profileImage);
            }
            $requestData->setCreatedBy(Permission::userId())
                ->setCreatedAt(date('Y-m-d H:i:s'));
        }

        return $requestData;
    }
}
