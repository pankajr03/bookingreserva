<?php

namespace BookneticApp\Backend\Workflow;

use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingRescheduledEventRequest;
use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingStartsEventRequest;
use BookneticApp\Backend\Workflow\DTOs\Request\SaveBookingStatusChangedEventRequest;
use BookneticApp\Backend\Workflow\DTOs\Request\SaveCustomerBirthdayEventRequest;
use BookneticApp\Backend\Workflow\DTOs\Request\SaveNewBookingEventRequest;
use BookneticApp\Backend\Workflow\DTOs\Request\SaveTenantNotifiedEventRequest;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\AppointmentPaidEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\BookingRescheduledEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\BookingStartsEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\BookingStatusChangedEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\CustomerBirthdayEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\CustomerCreatedEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\CustomerSignupEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\NewBookingEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowEventServices\TenantNotifiedEventService;
use BookneticApp\Backend\Workflow\Services\WorkflowService;
use BookneticApp\Providers\Mappers\DTOMapper;
use BookneticApp\Providers\Request\Post;

class EventsAjax extends \BookneticApp\Providers\Core\Controller
{
    private WorkflowService $service;

    private NewBookingEventService $newBookingEventService;

    private BookingRescheduledEventService $bookingRescheduledEventService;

    private BookingStatusChangedEventService $bookingStatusChangedEventService;

    private CustomerBirthdayEventService $customerBirthdayEventService;

    private BookingStartsEventService $bookingStartsEventService;

    private TenantNotifiedEventService $tenantNotifiedEventService;

    private CustomerCreatedEventService $customerCreatedEventService;

    private AppointmentPaidEventService $appointmentPaidEventService;

    private CustomerSignupEventService $customerSignupEventService;

    public function __construct()
    {
        $this->service = new WorkflowService();

        $this->newBookingEventService = new NewBookingEventService();
        $this->bookingRescheduledEventService = new BookingRescheduledEventService();
        $this->bookingStatusChangedEventService = new BookingStatusChangedEventService();
        $this->customerBirthdayEventService = new CustomerBirthdayEventService();
        $this->bookingStartsEventService = new BookingStartsEventService();
        $this->tenantNotifiedEventService = new TenantNotifiedEventService();
        $this->customerCreatedEventService = new CustomerCreatedEventService();
        $this->appointmentPaidEventService = new AppointmentPaidEventService();
        $this->customerSignupEventService = new CustomerSignupEventService();
    }

    public function event_new_booking()
    {
        $id = Post::int('id', -1);

        $params = $this->newBookingEventService->getEventParams($id);

        return $this->modalView('event_new_booking', $params);
    }

    public function event_new_booking_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'locations' => Post::json('locations'),
            'services' => Post::json('services'),
            'staffs' => Post::json('staffs'),
            'statuses' => Post::json('statuses'),
            'locale' => Post::string('locale'),
            'calledFrom' => Post::string('called_from', '', ['backend', 'frontend']),
            'categories' => Post::json('categories'),
        ], SaveNewBookingEventRequest::class);

        $this->newBookingEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function event_booking_rescheduled()
    {
        $id = Post::int('id', -1);

        $params = $this->bookingRescheduledEventService->getEventParams($id);

        return $this->modalView('event_booking_rescheduled', $params);
    }

    public function event_booking_rescheduled_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'locations' => Post::json('locations'),
            'services' => Post::json('services'),
            'staffs' => Post::json('staffs'),
            'locale' => Post::string('locale'),
            'forEachCustomer' => Post::int('for_each_customer') === 1,
            'calledFrom' => Post::string('called_from', '', ['backend', 'frontend']),
            'categories' => Post::json('categories'),
        ], SaveBookingRescheduledEventRequest::class);

        $this->bookingRescheduledEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function event_booking_status_changed()
    {
        $id = Post::int('id', -1);

        $params = $this->bookingStatusChangedEventService->getEventParams($id);

        return $this->modalView('event_booking_status_changed', $params);
    }

    public function event_booking_status_changed_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'statuses' => Post::json('statuses'),
            'prevStatuses' => Post::json('prev_statuses'),
            'locations' => Post::json('locations'),
            'services' => Post::json('services'),
            'staffs' => Post::json('staffs'),
            'locale' => Post::string('locale'),
            'calledFrom' => Post::string('called_from', '', ['backend', 'frontend']),
            'categories' => Post::json('categories'),
        ], SaveBookingStatusChangedEventRequest::class);

        $this->bookingStatusChangedEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function event_customer_birthday()
    {
        $id = Post::int('id', -1);

        $params = $this->customerBirthdayEventService->getEventParams($id);

        return $this->modalView('event_customer_birthday', $params);
    }

    public function event_customer_birthday_changed_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'months' => Post::json('months'),
            'years' => Post::json('years'),
            'gender' => Post::string('gender'),
            'offsetSign' => Post::string('offset_sign'),
            'offsetValue' => Post::string('offset_value'),
            'inputTime' => Post::string('input_time'),
            'categories' => Post::json('categories'),
        ], SaveCustomerBirthdayEventRequest::class);

        $this->customerBirthdayEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function event_booking_starts()
    {
        $id = Post::int('id', -1);

        $params = $this->bookingStartsEventService->getEventParams($id);

        return $this->modalView('event_booking_starts', $params);
    }

    public function event_booking_starts_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'offsetSign' => Post::string('offset_sign'),
            'offsetValue' => Post::int('offset_value'),
            'offsetType' => Post::string('offset_type'),
            'statuses' => Post::json('statuses'),
            'locations' => Post::json('locations'),
            'services' => Post::json('services'),
            'staffs' => Post::json('staffs'),
            'locale' => Post::string('locale'),
            'forEachCustomer' => Post::int('for_each_customer', 1) === 1,
            'categories' => Post::json('categories'),
        ], SaveBookingStartsEventRequest::class);

        $this->bookingStartsEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function event_booking_ends()
    {
        return $this->event_booking_starts();
    }

    public function event_customer_created_view()
    {
        $id = Post::int('id', -1);

        $params = $this->customerCreatedEventService->getEventParams($id);

        return $this->modalView('event_customer_created', $params);
    }

    public function event_customer_created_save()
    {
        $id = Post::int('id', -1);
        $locale = Post::string('locale');
        $categories = Post::json('categories');

        $this->customerCreatedEventService->saveEventData($id, $locale, $categories);

        return $this->response(true);
    }

    public function event_appointment_paid_view()
    {
        $id = Post::int('id', -1);

        $params = $this->appointmentPaidEventService->getEventParams($id);

        return $this->modalView('event_appointment_paid', $params);
    }

    public function event_appointment_paid_save()
    {
        $id = Post::int('id', -1);
        $locale = Post::string('locale');

        $this->appointmentPaidEventService->saveEventData($id, $locale);

        return $this->response(true);
    }

    public function event_customer_signup_view()
    {
        $id = Post::int('id', -1);

        $params = $this->customerSignupEventService->getEventParams($id);

        return $this->modalView('event_customer_signup', $params);
    }

    public function event_customer_signup_save()
    {
        $id = Post::int('id', -1);
        $locale = Post::string('locale');
        $categories = Post::json('categories');

        $this->customerSignupEventService->saveEventData($id, $locale, $categories);

        return $this->response(true);
    }

    public function event_tenant_notified()
    {
        $id = Post::int('id', -1);

        $params = $this->tenantNotifiedEventService->getEventParams($id);

        return $this->modalView('event_tenant_notified', $params);
    }

    public function event_tenant_notified_save()
    {
        $id = Post::int('id', -1);
        $request = DTOMapper::map([
            'offsetValue' => Post::int('offset_value'),
            'offsetType' => Post::string('offset_type'),
        ], SaveTenantNotifiedEventRequest::class);

        $this->tenantNotifiedEventService->saveEventData($id, $request);

        return $this->response(true);
    }

    public function get_locations()
    {
        $query = Post::string('q');

        $result = $this->service->getLocations($query);

        return $this->response(true, ['results' => $result]);
    }

    public function get_services()
    {
        $query = Post::string('q');

        $result = $this->service->getServices($query);

        return $this->response(true, ['results' => $result]);
    }

    public function get_staffs()
    {
        $query = Post::string('q');

        $result = $this->service->getStaffs($query);

        return $this->response(true, ['results' => $result]);
    }

    public function get_customer_categories()
    {
        $query = Post::string('q');

        $result = $this->service->getCategories($query);

        return $this->response(true, ['results' => $result]);
    }

    public function get_statuses()
    {
        $result = $this->service->getStatuses();

        return $this->response(true, ['results' => $result]);
    }
}
