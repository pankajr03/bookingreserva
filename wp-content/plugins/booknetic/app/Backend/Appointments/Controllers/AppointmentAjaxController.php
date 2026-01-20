<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Backend\Appointments\Exceptions\StatusNotFoundException;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Backend\Appointments\Services\AppointmentService as AppointmentServiceNew;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\TabUI;
use Exception;
use ReflectionException;

class AppointmentAjaxController extends Controller
{
    /**
     * @throws CapabilitiesException
     */
    public function add_new()
    {
        Capabilities::must('appointments_add');

        $date           = Helper::_post('date', '', 'string');
        $locations      = Location::where('is_active', 1)->fetchAll();
        $locationInf    = count($locations) == 1 ? $locations[0] : false;

        TabUI::get('appointments_add_new')
             ->item('details')
             ->setTitle(bkntc__('Appointment details'))
             ->addView(__DIR__ . '/view/tab/details.php')
             ->setPriority(1);

        TabUI::get('appointments_add_new')
             ->item('extras')
             ->setTitle(bkntc__('Extras'))
             ->addView(__DIR__ . '/view/tab/extras.php')
             ->setPriority(2);

        $data = [
            'location'  => $locationInf,
            'date'      => $date,
        ];

        return $this->modalView('add_new', [
            'data' => $data
        ]);
    }

    /**
     * @throws CapabilitiesException
     * @throws Exception
     */
    public function create_appointment()
    {
        Capabilities::must('appointments_add');

        $runWorkflows = Post::int('run_workflows', 1);
        Config::getWorkflowEventsManager()->setEnabled($runWorkflows === 1);
        $appointmentRequests = AppointmentRequests::load(true);

        if (! $appointmentRequests->validate()) {
            return $this->response(false, $appointmentRequests->getFirstError());
        }

        $appointmentData = $appointmentRequests->currentRequest();

        if ($appointmentData->isRecurring() && empty($appointmentData->recurringAppointmentsList)) {
            return $this->response(true, [ 'dates' => AppointmentService::getRecurringDates($appointmentData) ]);
        }

        AppointmentService::createAppointment();

        do_action('bkntc_backend_appointment_created', $appointmentData);

        PaymentGatewayService::find('local')->doPayment($appointmentRequests);

        return $this->response(true);
    }

    /**
     * @throws CapabilitiesException
     */
    public function edit()
    {
        Capabilities::must('appointments_edit');

        $id = Post::int('id');

        $appointmentSO = AppointmentSmartObject::load($id);

        if (! $appointmentSO->validate()) {
            return $this->response(false, bkntc__('Selected appointment not found!'));
        }

        // get service categories...
        $serviceInfo = $appointmentSO->getServiceInf();

        $categories = [];

        $categoryId = $serviceInfo['category_id'];
        $deep = 15;
        while (true) {
            $categoryInf = ServiceCategory::query()->get($categoryId);
            $categories[] = $categoryInf;

            $categoryId = (int)$categoryInf['parent_id'];

            if (($deep--) < 0 || $categoryId <= 0) {
                break;
            }
        }

        TabUI::get('appointments_edit')
             ->item('details')
             ->setTitle(bkntc__('Appointment details'))
             ->addView(__DIR__ . '/view/tab/edit_details.php', [
                 'id'            => $id,
                 'appointment'   => $appointmentSO,
                 'categories'    => array_reverse($categories)
             ])
             ->setPriority(1);

        TabUI::get('appointments_edit')
             ->item('extras')
             ->setTitle(bkntc__('Extras'))
             ->addView(__DIR__ . '/view/tab/edit_extras.php')
             ->setPriority(2);

        return $this->modalView('edit', [
            'id'				=> $id,
            'service_capacity'	=> $serviceInfo['max_capacity'],
            'priceUpdated'      => Appointment::getData($appointmentSO->getId(), 'price_updated', 0),
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function save_edited_appointment()
    {
        Capabilities::must('appointments_edit');

        $runWorkflows = Post::int('run_workflows', 1);

        Config::getWorkflowEventsManager()->setEnabled($runWorkflows === 1);

        $appointmentRequests = AppointmentRequests::load(true);

        if (! $appointmentRequests->validate()) {
            return $this->response(false, $appointmentRequests->getFirstError());
        }

        $appointmentObj = $appointmentRequests->currentRequest();

        do_action('bkntc_appointment_before_edit', $appointmentObj);
        do_action('bkntc_appointment_before_mutation', $appointmentObj->appointmentId);

        AppointmentService::editAppointment($appointmentObj);

        do_action('bkntc_appointment_after_edit', $appointmentObj);
        do_action('bkntc_appointment_after_mutation', $appointmentObj->appointmentId);

        return $this->response(true, ['id' => $appointmentObj->appointmentId]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function info()
    {
        Capabilities::must('appointments');

        $id = Post::int('id');

        $appointmentInfo = Appointment::query()
            ->leftJoin('customer', ['first_name', 'last_name', 'phone_number', 'email', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->leftJoin('staff', ['name', 'profile_image', 'email', 'phone_number'])
            ->where(Appointment::getField('id'), $id)->fetch();

        if (!$appointmentInfo) {
            return $this->response(false, bkntc__('Appointment not found!'));
        }

        $extrasArr = AppointmentExtra::query()
            ->where('appointment_id', $id)
            ->leftJoin(ServiceExtra::class, ['name', 'image'], ServiceExtra::getField('id'), AppointmentExtra::getField('extra_id'))
            ->fetchAll();

        $paymentGatewayList = [];
        $appointmentPrice = AppointmentPrice::query()
            ->where('appointment_id', $appointmentInfo->id)
            ->select('sum(price * negative_or_positive) as total_amount', true)->fetch();

        if ($appointmentPrice->total_amount != $appointmentInfo->paid_amount) {
            $paymentGatewayList = PaymentGatewayService::getInstalledGatewayNames();
            $paymentGatewayList = array_filter($paymentGatewayList, function ($paymentGateway) {
                return property_exists(PaymentGatewayService::find($paymentGateway), 'createPaymentLink');
            });
        }

        TabUI::get('appointments_info')
             ->item('details')
             ->setTitle(bkntc__('Appointment details'))
             ->addView(__DIR__ . '/view/tab/info_details.php', [
                 'info' => $appointmentInfo,
                 'paymentGateways' => $paymentGatewayList
             ])
             ->setPriority(1);

        TabUI::get('appointments_info')
             ->item('extras')
             ->setTitle(bkntc__('Extras'))
             ->addView(__DIR__ . '/view/tab/info_extras.php', [
                 'info'     => $appointmentInfo,
                 'extras'   => $extrasArr
             ])
             ->setPriority(2);

        return $this->modalView('info', [
            'id'            => $id,
        ]);
    }

    /**
     * @throws CapabilitiesException
     */
    public function change_status()
    {
        Capabilities::must('appointments_change_status');

        $ids = Post::array('ids');
        $statuses = Helper::getAppointmentStatuses();
        $selectedStatus = '';

        if (count($ids) === 1) {
            $appointmentInf = Appointment::query()->get($ids[0]);
            $appointmentStatus = $appointmentInf->status;

            if (array_key_exists($appointmentStatus, $statuses)) {
                $selectedStatus = $appointmentStatus;
            }
        }

        return $this->modalView('change_status', [
            'ids'               => $ids,
            'statuses'          =>  $statuses,
            'selected_status'   =>  [
                'slug'  =>  $selectedStatus,
                'icon'  =>  empty($selectedStatus) ? '' : $statuses[$selectedStatus]['icon'],
                'color'  =>  empty($selectedStatus) ? '#FFF' : $statuses[$selectedStatus]['color'],
                'title'  =>  empty($selectedStatus) ? bkntc__('Select status...') : $statuses[$selectedStatus]['title']
            ]
        ]);
    }

    /**
     * @throws CapabilitiesException
     * @throws ReflectionException
     * @throws StatusNotFoundException
     */
    public function change_status_save()
    {
        Capabilities::must('appointments_change_status');

        $runWorkflows = Post::int('run_workflows', 1);
        $ids = Post::array('ids');
        $status = Post::string('status');

        $appointmentServiceNew = Container::get(AppointmentServiceNew::class);

        $appointmentServiceNew->changeStatusBulk($ids, $status, $runWorkflows);

        return $this->response(true);
    }

    public function get_services()
    {
        $search		= Post::string('q');
        $category	= Post::int('category');

        $query = Service::query()
            ->select(Service::getField('*'))
            ->innerJoin(ServiceStaff::class, [], Service::getField('id'), ServiceStaff::getField('service_id'))
            ->where('is_active', 1)
            ->groupBy(Service::getField('id'));

        if (! empty($category)) {
            $query = $query->where('category_id', $category);
        }

        if (! empty($search)) {
            $query = $query->like('name', $search);
        }

        $services = $query->fetchAll();

        $data = array_map(fn ($service) => [
            'id'				=>	(int)$service['id'],
            'text'				=>	htmlspecialchars($service['name']),
            'repeatable'		=>	(int)$service['is_recurring'],
            'repeat_type'		=>	htmlspecialchars((string)$service['repeat_type']),
            'repeat_frequency'	=>	htmlspecialchars((string)$service['repeat_frequency']),
            'full_period_type'	=>	htmlspecialchars((string)$service['full_period_type']),
            'full_period_value'	=>	(int)$service['full_period_value'],
            'max_capacity'		=>	(int)$service['max_capacity'],
            'date_based'		=>	$service['duration'] >= 1440
        ], $services);

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_locations()
    {
        $search = Post::string('q');
        $locations = Location::my()
            ->where('is_active', 1)
            ->like('name', $search)
            ->fetchAll();
        $data = [];

        foreach ($locations as $location) {
            $data[] = [
                'id'	=> (int)$location['id'],
                'text'	=> htmlspecialchars($location['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_service_categories()
    {
        $search = Post::string('q');
        $category = Post::int('category');

        $allowedStafIDs = array_column(Staff::query()->fetchAll(), 'id');

        $serviceCategories = ServiceCategory::query()
            ->like('name', $search)
            ->where('parent_id', $category)
            ->fetchAll();

        $data = [];

        foreach ($serviceCategories as $category) {
            $subcategories = Helper::getAllSubCategories($category['id']);

            $isAllowedServiceForStaff = ServiceStaff::query()
                ->where('staff_id', $allowedStafIDs)
                ->where(
                    'service_id',
                    Service::query()
                    ->where('category_id', $subcategories)
                    ->select('id')
                )
                ->count();

            if ($isAllowedServiceForStaff == 0) {
                continue;
            }

            $data[] = [
                'id'                => (int)$category['id'],
                'text'              => htmlspecialchars($category['name']),
                'have_sub_categ'    => count($subcategories) - 1 // Helper::getAllSubCategories() methodu arrayin ichinde sechilen categoriyanida elave edir deye, neticenin countundan -1 edirik.
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_staff()
    {
        $search		= Post::string('q');
        $location	= Post::int('location');
        $service	= Post::int('service');

        $query = Staff::query()
            ->where('is_active', 1)
            ->like('name', $search);

        if (!empty($location)) {
            $query->whereFindInSet('locations', $location);
        }

        if (!empty($service)) {
            $serviceStaffSubQuery = ServiceStaff::query()
                ->where('service_id', $service)
                ->select('staff_id');
            $query->where('id', 'IN', $serviceStaffSubQuery);
        }

        $staffs = $query->fetchAll();

        $data = array_map(fn ($staff) => [
            'id'	=> (int)$staff['id'],
            'text'	=> htmlspecialchars($staff['name'])
        ], $staffs);

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_customers()
    {
        $search = Post::string('q');

        $customers = Customer::my();

        if (! empty($search)) {
            $customers = $customers->where('CONCAT(`first_name`, \' \', `last_name`)', 'like', "%{$search}%")
                ->orLike('email', $search)
                ->orLike('phone_number', $search);
        }

        $customers = $customers->select([ 'id', 'first_name', 'last_name' ])->limit(100)->fetchAll();

        $data = array_map(fn ($elem) => [
            'id'	=> (int) $elem[ 'id' ],
            'text'	=> htmlspecialchars($elem[ 'first_name' ] . ' ' . $elem[ 'last_name' ])
        ], $customers);

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_available_times($calledFromBackend = true)
    {
        $id				= Post::int('id', -1);
        $search			= Post::string('q');
        $date			= Post::string('date');

        $date           = Date::reformatDateFromCustomFormat($date);
        $calendarData   = new CalendarService($date);

        if ($calledFromBackend) {
            $location		= Post::int('location');
            $service		= Post::int('service');
            $staff			= Post::int('staff');
            $service_extras	= Post::string('service_extras', '[]');

            $calendarData->initServiceInf($service);
        } else {
            $appointmentRequestData = AppointmentRequests::load()->currentRequest();
            $location		= $appointmentRequestData->getData('location', 0, 'int');
            $service		= $appointmentRequestData->getData('service', 0, 'int');
            $staff			= $appointmentRequestData->getData('staff', 0, 'int');
            $service_extras	= $appointmentRequestData->getData('service_extras', '[]', 'string');

            $calendarData->setServiceInf($appointmentRequestData->serviceInf);
        }

        $service_extras	= json_decode($service_extras, true);

        $extras_arr	= [];
        foreach ($service_extras as $extraInf) {
            if (!(is_array($extraInf)
                   && isset($extraInf['extra']) && is_numeric($extraInf['extra']) && $extraInf['extra'] > 0
                   && isset($extraInf['quantity']) && is_numeric($extraInf['quantity']) && $extraInf['quantity'] > 0)
            ) {
                continue;
            }

            $extra_inf = ServiceExtra::query()
                ->where('service_id', $service)
                ->where('id', $extraInf['extra'])
                ->fetch();

            if ($extra_inf && $extra_inf['max_quantity'] >= $extraInf['quantity']) {
                $extra_inf['quantity'] = $extraInf['quantity'];

                $extras_arr[] = $extra_inf;
            }
        }

        $dataForReturn = [];

        $calendarData->setStaffId($staff)
                     ->setLocationId($location)
                     ->setServiceExtras($extras_arr)
                     ->setExcludeAppointmentId($id)
                     ->setShowExistingTimeSlots(false)
                     ->setCalledFromBackEnd($calledFromBackend);

        $calendarData = $calendarData->getCalendar();
        $data = $calendarData['dates'];

        if (isset($data[ $date ])) {
            foreach ($data[ $date ] as $dataInf) {
                $startTime = $dataInf['start_time_format'];

                // search...
                if (!empty($search) && strpos($startTime, $search) === false) {
                    continue;
                }

                $result = [
                    'id'					=>	$dataInf['start_time'],
                    'text'					=>	$startTime,
                    'max_capacity'			=>	$dataInf['max_capacity'],
                    'weight'                =>	$dataInf['weight']
                ];
                $dataForReturn[] = apply_filters('bkntc_backend_appointment_date_time', $result, $dataInf);
            }
        }

        return $this->response(true, [ 'results' => $dataForReturn ]);
    }

    public function get_available_times_all()
    {
        $search		= Post::string('q');
        $service	= Post::int('service');
        $location	= Post::int('location');
        $staff		= Post::int('staff');
        $dayOfWeek	= Post::int('day_number', 1);

        if ($dayOfWeek != -1) {
            $dayOfWeek -= 1;
        }

        $calendarServ = new CalendarService();

        $calendarServ->setStaffId($staff)
                     ->setServiceId($service)
                     ->setLocationId($location);

        return $this->response(true, [
            'results' => $calendarServ->getCalendarByDayOfWeek($dayOfWeek, $search)
        ]);
    }

    public function get_day_offs()
    {
        $appointmentRequests = AppointmentRequests::load();
        $appointmentObj = $appointmentRequests->currentRequest();

        if (
            ! Date::isValid($appointmentObj->recurringStartDate)
            || ! Date::isValid($appointmentObj->recurringEndDate)
            || $appointmentObj->serviceId <= 0
        ) {
            return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
        }

        $calendarService = new CalendarService($appointmentObj->recurringStartDate, $appointmentObj->recurringEndDate);
        $calendarService->setDefaultsFrom($appointmentObj);

        return $this->response(true, $calendarService->getDayOffs());
    }

    public function get_service_extras()
    {
        $appointment_id			= Post::int('appointment_id');
        $service_id	            = Post::int('service_id');

        $showAllExtras =  Helper::getOption('show_all_service_extras', 'on');

        if ($showAllExtras == 'on') {
            $extras = ServiceExtra::query()->fetchAll();
        } else {
            $extras = ServiceExtra::query()
                ->where('service_id', $service_id)
                ->fetchAll();
        }

        $appointmentExtras = AppointmentExtra::query()
            ->where('appointment_id', $appointment_id)
            ->fetchAll();
        $appointmentExtras = Helper::assocByKey($appointmentExtras, 'extra_id');

        foreach ($extras as $extra) {
            $extra->quantity = array_key_exists($extra->id, $appointmentExtras) ? $appointmentExtras[$extra->id]->quantity : 0;
        }

        return $this->modalView('service_extras', [
            'extras'    => $extras,
        ]);
    }

    public function create_payment_link()
    {
        $paymentGateway = Post::string('payment_gateway');
        $id = Post::int('id');

        $totalAmountQuery = AppointmentPrice::query()
            ->where('appointment_id', DB::field(Appointment::getField('id')))
            ->select('sum(price * negative_or_positive)', true);

        $appointments = Appointment::query()
            ->leftJoin('customer', ['first_name', 'last_name', 'email', 'profile_image', 'phone_number'])
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->where(Appointment::getField('id'), $id)
            ->selectSubQuery($totalAmountQuery, 'total_price');

        $appointment = $appointments->fetch();
        if (empty($appointments)) {
            return $this->response(false);
        }

        $paymentGatewayService = PaymentGatewayService::find($paymentGateway);

        if (! property_exists($paymentGatewayService, 'createPaymentLink')) {
            return $this->response(false);
        }

        $data = $paymentGatewayService->createPaymentLink([$appointment]);

        return $this->response(true, ['url' => $data->data['url']]);
    }
}
