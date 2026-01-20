<?php

namespace BookneticApp\Backend\Appointments\Controllers;

use BookneticApp\Backend\Appointments\Exceptions\StatusNotFoundException;
use BookneticApp\Backend\Appointments\Services\AppointmentService;
use BookneticApp\Providers\Core\RestRequest;
use Exception;

class AppointmentRestController
{
    private AppointmentService $appointmentService;
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
    public function getAll(RestRequest $request): array
    {
        $skip = $request->param('skip', 0, RestRequest::TYPE_INTEGER);
        $limit = $request->param('limit', 12, RestRequest::TYPE_INTEGER);
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $orderByField = $request->param(
            'orderByField',
            '',
            RestRequest::TYPE_STRING,
            ['starts_at', 'customer_name', 'staff_name', 'service_name', 'duration', 'created_at']
        );

        $orderDirection = $request->param('orderDirection', 'DESC', RestRequest::TYPE_STRING, ['ASC', 'DESC']);

        $startsAt = $request->param('startsAt', null, RestRequest::TYPE_STRING);
        $endsAt = $request->param('endsAt', null, RestRequest::TYPE_STRING);
        $serviceId = $request->param('serviceId', null, RestRequest::TYPE_INTEGER);
        $customerId = $request->param('customerId', null, RestRequest::TYPE_INTEGER);
        $staffId = $request->param('staffId', null, RestRequest::TYPE_INTEGER);
        $status = $request->param('status', null, RestRequest::TYPE_STRING);
        $isFinished = $request->param('isFinished', null, RestRequest::TYPE_INTEGER, [0, 1]);

        $appointments = $this->appointmentService->getAppointments([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'service_id' => $serviceId,
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'status' => $status,
            'isFinished' => $isFinished,
        ], [
            'field' => $orderByField,
            'type' => $orderDirection,
        ], $search, $skip, $limit);

        return [
            'data' => $appointments['data'],
            'meta' => [
                'total' => $appointments['total'],
                'limit' => $limit,
                'skip' => $skip,
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function get(RestRequest $request): array
    {
        $id = $request->require('id', RestRequest::TYPE_INTEGER);

        $appointment = $this->appointmentService->getAppointment($id);

        return [
            'data' => $appointment,
        ];
    }

    public function create(RestRequest $request): array
    {
        return [];
    }

    public function update(RestRequest $request): array
    {
        return [];
    }

    /**
     * @throws Exception
     */
    public function delete(RestRequest $request): array
    {
        $id = $request->require('id', RestRequest::TYPE_INTEGER);
        $this->appointmentService->delete($id);

        return [];
    }

    public function getStatuses(RestRequest $request): array
    {
        $search = $request->param('search', '', RestRequest::TYPE_STRING);

        $statuses = $this->appointmentService->getAppointmentStatuses($search);

        return [
            'data' => $statuses,
        ];
    }

    /**
     * @throws StatusNotFoundException
     */
    public function changeStatus(RestRequest $request): array
    {
        $runWorkflows = $request->param('run_workflows', 1, RestRequest::TYPE_INTEGER, [0, 1]);

        $id = $request->param('id', 0, RestRequest::TYPE_INTEGER);
        $status = $request->param('status', '', RestRequest::TYPE_STRING);

        $this->appointmentService->changeStatus($id, $status, $runWorkflows);

        return [];
    }

    /**
     * @throws Exception
     */
    public function getAvailableTimes(RestRequest $request): array
    {
        $id				= $request->param('id', -1, RestRequest::TYPE_INTEGER);
        $search			= $request->param('q', '', RestRequest::TYPE_STRING);
        $date			= $request->param('date', '', RestRequest::TYPE_STRING);
        $location		= $request->param('location', 0, RestRequest::TYPE_INTEGER);
        $service		= $request->param('service', 0, RestRequest::TYPE_INTEGER);
        $staff			= $request->param('staff', 0, RestRequest::TYPE_INTEGER);
        $serviceExtras	= $request->param('service_extras', '[]', RestRequest::TYPE_STRING);

        $availableTimes = $this->appointmentService->getAvailableTimes(
            $id,
            $search,
            $date,
            $location,
            $service,
            $staff,
            $serviceExtras,
        );

        return [
            'data' => $availableTimes,
        ];
    }
}
