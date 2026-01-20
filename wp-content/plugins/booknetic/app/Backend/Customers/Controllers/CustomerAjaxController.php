<?php

namespace BookneticApp\Backend\Customers\Controllers;

use BookneticApp\Backend\Base\DTOs\Response\SelectOptionResponse;
use BookneticApp\Backend\Customers\DTOs\Request\CustomerRequest;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Backend\Customers\DTOs\Response\CustomerViewResponse;
use BookneticApp\Backend\Customers\Exceptions\AlreadyConnectedUserException;
use BookneticApp\Backend\Customers\Exceptions\CanNotBeCustomerException;
use BookneticApp\Backend\Customers\Exceptions\CanNotChangeEmailOrPasswordException;
use BookneticApp\Backend\Customers\Exceptions\CSVFileException;
use BookneticApp\Backend\Customers\Exceptions\CustomerNotFoundException;
use BookneticApp\Backend\Customers\Exceptions\CustomException;
use BookneticApp\Backend\Customers\Exceptions\FillRequiredFieldsException;
use BookneticApp\Backend\Customers\Exceptions\ImageTypeException;
use BookneticApp\Backend\Customers\Exceptions\InvalidArgumentException;
use BookneticApp\Backend\Customers\Exceptions\InvalidCustomerDataException;
use BookneticApp\Backend\Customers\Exceptions\InvalidWordpressUserIdException;
use BookneticApp\Backend\Customers\Exceptions\MultipleFieldsDetectedExceptions;
use BookneticApp\Backend\Customers\Exceptions\AnotherCustomerWithSameEmailException;
use BookneticApp\Backend\Customers\Exceptions\SelectWordpressUserException;
use BookneticApp\Backend\Customers\Exceptions\EmptyPasswordException;
use BookneticApp\Backend\Customers\Services\CustomerService;
use BookneticApp\Config;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\TabUI;

class CustomerAjaxController extends Controller
{
    private CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws CapabilitiesException|CustomerNotFoundException
     */
    public function add_new()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('customers_edit');

            $customer = $this->service->get($id);
        } else {
            Capabilities::must('customers_add');

            $customer = CustomerResponse::createEmpty();
        }

        $hasWpUser = true;
        // get total customers count
        if (!empty($customer->getEmail()) && Helper::isSaaSVersion()) {
            $hasWpUser = !($this->service->getCustomerCountByEmail($customer->getEmail()) > 1);
        }

        TabUI::get('customers_add_new')
            ->item('customer_details')
            ->setTitle(bkntc__('Customer details'))
            ->addView(__DIR__ . '/view/tab/add_customer_details.php')
            ->setPriority(1);

        $isEmailRequired = Helper::getOption('set_email_as_required', 'on') === 'on';
        $isPhoneRequired = Helper::getOption('set_phone_as_required', 'off') === 'on';

        $users = array_map(static fn ($user) => new SelectOptionResponse($user->ID, (string) $user->display_name, ), get_users([
            'fields' => ['ID', 'display_name'],
            'role__not_in' => ['booknetic_staff']
        ]));

        $categories = CustomerCategory::query()->select(['id', 'name'])->fetchAll();

        $viewResponse = new CustomerViewResponse();

        $viewResponse->setCustomer($customer);
        $viewResponse->setIsEmailRequired($isEmailRequired);
        $viewResponse->setIsPhoneRequired($isPhoneRequired);
        $viewResponse->setUsers($users);
        $viewResponse->setHasWpUser($hasWpUser);
        $viewResponse->setCategories($categories);
        $viewResponse->setIsFullNameEnabled(Helper::getOption('separate_first_and_last_name', 'on') === 'off');

        return $this->modalView('add_new', $viewResponse);
    }

    public function getWpUserData()
    {
        $id = Post::int('id');

        $user = get_userdata($id);

        if ($user === false) {
            return $this->response(false, bkntc__('User not found!'));
        }

        return $this->response(true, [
            'id' => $user->ID,
            'email' => $user->user_email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name
        ]);
    }

    /**
     * @throws CustomerNotFoundException
     * @throws CapabilitiesException
     */
    public function info()
    {
        Capabilities::must('customers');

        $id = Post::int('id');

        $customer = $this->service->get($id);

        TabUI::get('customers_info')
            ->item('info')
            ->setTitle(bkntc__('Customer Info'))
            ->addView(__DIR__ . '/view/tab/customer_info_details.php')
            ->setPriority(1);

        return $this->modalView('info', [
            'customer' => $customer,
        ]);
    }

    public function import()
    {
        Capabilities::must('customers_import');

        return $this->modalView('import');
    }

    /**
     * @throws AlreadyConnectedUserException
     * @throws InvalidWordpressUserIdException
     * @throws FillRequiredFieldsException
     * @throws ImageTypeException
     * @throws SelectWordpressUserException
     * @throws EmptyPasswordException
     * @throws CanNotChangeEmailOrPasswordException
     * @throws AnotherCustomerWithSameEmailException
     * @throws CustomException
     * @throws CustomerNotFoundException
     * @throws CapabilitiesException
     * @throws InvalidCustomerDataException
     * @throws CanNotBeCustomerException
     * @throws InvalidArgumentException
     */
    public function create()
    {
        Capabilities::must('customers_add');

        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->create($request);

        return $this->response(true, [
            'customer_id' => $id
        ]);
    }

    /**
     * @return mixed|null
     * @throws AlreadyConnectedUserException
     * @throws AnotherCustomerWithSameEmailException
     * @throws CanNotBeCustomerException
     * @throws CanNotChangeEmailOrPasswordException
     * @throws CapabilitiesException
     * @throws CustomException
     * @throws CustomerNotFoundException
     * @throws EmptyPasswordException
     * @throws FillRequiredFieldsException
     * @throws ImageTypeException
     * @throws InvalidArgumentException
     * @throws InvalidCustomerDataException
     * @throws InvalidWordpressUserIdException
     * @throws SelectWordpressUserException
     */
    public function update()
    {
        Capabilities::must('customers_edit');

        $id = Post::int('id');
        $request = $this->prepareSaveRequestDTO();

        $id = $this->service->update($id, $request);

        return $this->response(true, [
            'customer_id' => $id
        ]);
    }

    /**
     * @throws CapabilitiesException
     * @throws FillRequiredFieldsException
     * @throws CSVFileException
     * @throws MultipleFieldsDetectedExceptions
     */
    public function import_customers()
    {
        Capabilities::must('customers_import');

        $file = $_FILES['csv'];

        $this->service->importCustomer($file);

        return $this->response(true);
    }

    /**
     * @throws InvalidCustomerDataException
     * @throws AnotherCustomerWithSameEmailException
     * @throws InvalidWordpressUserIdException
     * @throws CustomerNotFoundException
     * @throws AlreadyConnectedUserException
     * @throws SelectWordpressUserException
     * @throws EmptyPasswordException
     * @throws ImageTypeException
     * @throws CanNotBeCustomerException
     * @throws FillRequiredFieldsException
     * @throws InvalidArgumentException
     * @throws CustomException|CanNotChangeEmailOrPasswordException
     */
    public function prepareSaveRequestDTO(): CustomerRequest
    {
        $id = Post::int('id');
        $categoryId = Post::int('categoryId');
        $wpUser = Post::int('wp_user');
        $firstName = Post::string('first_name');
        $lastName = Post::string('last_name');
        $gender = Post::string('gender', '', ['male', 'female']);
        $birthday = Post::string('birthday');
        $phone = Post::string('phone');
        $email = Post::email('email');
        $allowLogin = Post::int('allow_customer_to_login', 0, ['0', '1']);
        $wpUserUseExisting = Post::string('wp_user_use_existing', 'yes', ['yes', 'no']);
        $wpUserPassword = Post::string('wp_user_password');
        $note = Post::string('note');

        $runWorkflows = Post::int('run_workflows', 1);
        Config::getWorkflowEventsManager()->setEnabled($runWorkflows === 1);

        $showOnlyName = Helper::getOption('separate_first_and_last_name', 'on') === 'off';

        if (!$this->service->canBeCustomer($email)) {
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
            $selectedWpUser = Customer::where('user_id', $wpUser)->fetch();

            if ($isEdit) {
                if ($selectedWpUser !== null && $getOldInf->getUserId() !== $wpUser) {
                    throw new AlreadyConnectedUserException($selectedWpUser->id);
                }
            } elseif ($selectedWpUser !== null) {
                throw new AlreadyConnectedUserException($selectedWpUser->id);
            }
        }

        if ($isEdit && $customerOnMultiTenants && ($getOldInf->getEmail() !== $email || (bool)$allowLogin != email_exists($getOldInf->getEmail()) || !empty($wpUserPassword))) {
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

        if ($email !== '' && (!$isEdit || $email !== $getOldInf->getEmail())) {
            if (Customer::query()->where('email', $email)->count() > 0) {
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

                if (!$wpUserData) {
                    throw new InvalidWordpressUserIdException();
                }

                if (!in_array('booknetic_customer', $wpUserData->roles)) {
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
                        'user_login' => $email,
                        'user_email' => $email,
                        'display_name' => trim($firstName . ' ' . $lastName),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'role' => 'booknetic_customer',
                        'user_pass' => $wpUserPassword
                    ]);

                    if (is_wp_error($wpUser)) {
                        return $this->response(false, $wpUser->get_error_message());
                    }
                }
            }
        } else {
            if ($isEdit && $getOldInf->getUserId() > 0) {
                $userData = get_userdata($getOldInf->getUserId());
                if ($userData && in_array('booknetic_customer', $userData->roles)) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user($getOldInf->getUserId());
                }
            }

            $wpUser = 0;
        }

        $profileImage = '';

        if (isset($_FILES['image']) && is_string($_FILES['image']['tmp_name'])) {
            $profileImage = $this->service->handleImageUpload($_FILES['image']);
        }
        $request = new CustomerRequest();

        $request->setFirstName(trim($firstName))
            ->setGender($gender)
            ->setBirthdate(empty($birthday) ? null : Date::reformatDateFromCustomFormat($birthday))
            ->setEmail($email)
            ->setPhoneNumber($phone)
            ->setNotes($note)
            ->setWpUserPassword($wpUserPassword)
            ->setCategoryId(max($categoryId, 0))
            ->setUserId(max($wpUser, 0));

        if (!$showOnlyName) {
            $request->setLastName(trim($lastName));
        }

        if ($isEdit) {
            if (!empty($profileImage)) {
                $request->setProfileImage($profileImage);
                if (!empty($getOldInf->getProfileImage())) {
                    $filePath = Helper::uploadedFile($getOldInf->getProfileImage(), 'Customers');

                    if (is_file($filePath) && is_writable($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        } else {
            if (!empty($profileImage)) {
                $request->setProfileImage($profileImage);
            }
            $request->setCreatedBy(Permission::userId())
                ->setCreatedAt(date('Y-m-d H:i:s'));
        }

        return $request;
    }
}
