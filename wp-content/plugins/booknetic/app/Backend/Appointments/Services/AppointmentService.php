<?php

namespace BookneticApp\Backend\Appointments\Services;

use BookneticApp\Backend\Appointments\Exceptions\AppointmentNotFoundException;
use BookneticApp\Backend\Appointments\Exceptions\StatusNotFoundException;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use BookneticApp\Backend\Appointments\Helpers\CalendarService;
use BookneticApp\Backend\Appointments\Repositories\AppointmentExtraRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentPriceRepository;
use BookneticApp\Backend\Appointments\Repositories\AppointmentRepository;
use BookneticApp\Backend\Services\Repositories\ServiceExtraRepository;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\Helpers\Helper;
use Exception;

class AppointmentService
{
    private AppointmentRepository $appointmentRepository;
    private AppointmentExtraRepository $appointmentExtraRepository;
    private AppointmentPriceRepository $appointmentPriceRepository;
    private ServiceExtraRepository $serviceExtraRepository;
    public function __construct(
        AppointmentRepository $appointmentRepository,
        AppointmentExtraRepository $appointmentExtraRepository,
        AppointmentPriceRepository $appointmentPriceRepository,
        ServiceExtraRepository $serviceExtraRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->appointmentExtraRepository = $appointmentExtraRepository;
        $this->appointmentPriceRepository = $appointmentPriceRepository;
        $this->serviceExtraRepository = $serviceExtraRepository;
    }

    private function runBeforeEditActions(AppointmentRequestData $appointmentObj): void
    {
        do_action('bkntc_appointment_before_edit', $appointmentObj);
        do_action('bkntc_appointment_before_mutation', $appointmentObj->appointmentId);
    }

    private function runAfterEditActions(AppointmentRequestData $appointmentObj): void
    {
        do_action('bkntc_appointment_after_edit', $appointmentObj);
        do_action('bkntc_appointment_after_mutation', $appointmentObj->appointmentId);
    }

    private function runWorkflows(int $runWorkflows): void
    {
        Config::getWorkflowEventsManager()->setEnabled($runWorkflows === 1);
    }

    private function updateAppointments(AppointmentRequestData $appointmentObj): void
    {
        $timeslot = $appointmentObj->getAllTimeslots()[ 0 ];

        $shouldChangePrices = (bool) $appointmentObj->getData('change_prices', 1, 'int', [ 1, 0 ]);

        $appointmentUpdateData = apply_filters('bkntc_appointment_update_data', [
            'location_id' => $appointmentObj->locationId,
            'service_id'  => $appointmentObj->serviceId,
            'staff_id'    => $appointmentObj->staffId,
            'customer_id' => $appointmentObj->customerId,
            'status'      => $appointmentObj->status,
            'weight'      => $appointmentObj->weight,
            'starts_at'   => $timeslot->getTimestamp(),
            'ends_at'     => $timeslot->getTimestamp() + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration()) * 60,
            'busy_from'   => $timeslot->getTimestamp() - ((int) $appointmentObj->serviceInf->buffer_before) * 60,
            'busy_to'     => $timeslot->getTimestamp() + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration() + (int) $appointmentObj->serviceInf->buffer_after) * 60,
            'note'        => $appointmentObj->note,
        ], $appointmentObj);

        $this->appointmentRepository->update($appointmentObj->appointmentId, $appointmentUpdateData);

        if (! $shouldChangePrices) {
            return;
        }

        $this->appointmentPriceRepository->deleteByAppointmentId($appointmentObj->appointmentId);
        $this->appointmentExtraRepository->deleteByAppointmentId($appointmentObj->appointmentId);

        foreach ($appointmentObj->getServiceExtras() as $extra) {
            $this->appointmentExtraRepository->create($appointmentObj->appointmentId, $extra);
        }
        foreach ($appointmentObj->getGroupedPrices() as $priceKey => $priceInf) {
            $this->appointmentPriceRepository->create($appointmentObj->appointmentId, [
                'unique_key'           =>  $priceKey,
                'price'                =>  Math::abs($priceInf->getPrice()),
                'negative_or_positive' =>  $priceInf->getNegativeOrPositive()
            ]);
        }

        Appointment::setData($appointmentObj->appointmentId, 'price_updated', 0);
    }

    public function update(AppointmentRequestData $appointmentObj, int $runWorkflows): void
    {
        $this->runWorkflows($runWorkflows);
        $this->runBeforeEditActions($appointmentObj);
        $this->updateAppointments($appointmentObj);
        $this->runAfterEditActions($appointmentObj);
    }

    public function delete(int $appointmentId): void
    {
        do_action('bkntc_appointment_before_mutation', $appointmentId);
        do_action('bkntc_appointment_after_mutation', null);

        do_action('bkntc_appointment_deleted', $appointmentId);

        $this->appointmentExtraRepository->deleteByAppointmentId($appointmentId);
        $this->appointmentPriceRepository->deleteByAppointmentId($appointmentId);
        $this->appointmentRepository->delete($appointmentId);
    }

    public function deleteBulk(array $appointmentIds): void
    {
        foreach ($appointmentIds as $appointmentId) {
            $this->delete($appointmentId);
        }
    }

    /**
     * @throws StatusNotFoundException
     */
    public function changeStatusBulk(array $ids, string $status, int $runWorkflows): void
    {
        $this->runWorkflows($runWorkflows);

        $this->validateStatus($status);

        foreach ($ids as $appointmentId) {
            if (! (is_numeric($appointmentId) && $appointmentId > 0)) {
                continue;
            }

            $appointment = $this->appointmentRepository->get($appointmentId);

            if ($appointment === null || $appointment->status == $status) {
                continue;
            }

            $this->set($appointmentId, ['status' =>	$status]);
        }
    }

    /**
     * @throws StatusNotFoundException
     */
    private function validateStatus(string $status): void
    {
        $availableStatuses = Helper::getAppointmentStatuses();

        if (! array_key_exists($status, $availableStatuses)) {
            throw new StatusNotFoundException();
        }
    }

    /**
     * @throws StatusNotFoundException
     */
    public function changeStatus(int $appointmentId, string $status, int $runWorkflows): void
    {
        $this->runWorkflows($runWorkflows);

        $this->validateStatus($status);

        if (! ($appointmentId > 0)) {
            return;
        }

        $appointment = $this->appointmentRepository->get($appointmentId);

        if ($appointment === null || $appointment->status == $status) {
            return;
        }

        $this->set($appointmentId, ['status' =>	$status]);
    }

    private function set($id, $data): void
    {
        do_action('bkntc_appointment_before_mutation', $id);

        $this->appointmentRepository->update($id, $data);

        do_action('bkntc_appointment_after_mutation', $id);
    }

    public function getAppointments(array $filters = [], array $orderBy = [], string $search = "", int $skip = 0, int $limit = 12): array
    {
        $appointmentsObj = $this->appointmentRepository->getAppointments($filters, $orderBy, $search, $skip, $limit);
        $statuses = Helper::getAppointmentStatuses();

        $appointments = $appointmentsObj['data'];

        for ($i = 0; $i < count($appointments); $i++) {
            $appointments[$i]['statusObject'] = $statuses[$appointments[$i]->status] ?? [];

            if (!empty($appointments[$i]['customer_profile_image'])) {
                $appointments[$i]['customer_profile_image'] = Helper::profileImage($appointments[$i]['customer_profile_image'], 'Customers');
            }

            if (!empty($appointments[$i]['staff_profile_image'])) {
                $appointments[$i]['staff_profile_image'] =  Helper::profileImage($appointments[$i]['staff_profile_image'], 'staff');
            }
        }

        return ['data' => $appointments, 'total' => $appointmentsObj['total']];
    }

    /**
     * @throws AppointmentNotFoundException
     */
    public function getAppointment(int $appointmentId): array
    {
        $appointment = $this->appointmentRepository->getDetails($appointmentId);

        if (!$appointment) {
            throw new AppointmentNotFoundException();
        }

        $statuses = Helper::getAppointmentStatuses();

        $appointment['statusObject'] = $statuses[$appointment->status] ?? [];

        $extrasArr = $this->appointmentRepository->getExtras($appointmentId);

        $paymentGatewayList = [];
        $appointmentPrice = $this->appointmentPriceRepository->getTotalAmount($appointmentId);

        if (!empty($appointmentPrice) && $appointmentPrice->total_amount != $appointment->paid_amount) {
            $paymentGatewayList = PaymentGatewayService::getInstalledGatewayNames();
            $paymentGatewayList = array_filter($paymentGatewayList, function ($paymentGateway) {
                return property_exists(PaymentGatewayService::find($paymentGateway), 'createPaymentLink');
            });
        }

        return [
            'info'     => $appointment,
            'extras'   => $extrasArr,
            'paymentGateways' => $paymentGatewayList
        ];
    }

    public function getAppointmentStatuses(string $search): array
    {
        $statuses = Helper::getAppointmentStatuses();

        if (empty($search)) {
            return $statuses;
        }

        $search = strtolower($search);

        return array_filter($statuses, fn ($status) => strpos(strtolower($status['title']), $search) !== false);
    }

    /**
     * @throws Exception
     */
    public function getAvailableTimes(
        int $id,
        string $search,
        string $date,
        int $location,
        int $service,
        int $staff,
        string $selectedExtras
    ): array {
        $date           = Date::reformatDateFromCustomFormat($date);
        $calendarData   = new CalendarService($date);

        $calendarData->initServiceInf($service);

        $selectedExtraArr = json_decode($selectedExtras, true) ?: [];

        $extras	= [];
        foreach ($selectedExtraArr as $selectedExtra) {
            if (!(is_array($selectedExtra)
                && isset($selectedExtra['extra']) && is_numeric($selectedExtra['extra']) && $selectedExtra['extra'] > 0
                && isset($selectedExtra['quantity']) && is_numeric($selectedExtra['quantity']) && $selectedExtra['quantity'] > 0)
            ) {
                continue;
            }

            $extra = $this->serviceExtraRepository->getByServiceIdAndExtraId($service, $selectedExtra['extra']);

            if (!$extra || $extra['max_quantity'] < $selectedExtra['quantity']) {
                continue;
            }

            $extra['quantity'] = $selectedExtra['quantity'];

            $extras[] = $extra;
        }

        $dataForReturn = [];

        $calendarData->setStaffId($staff)
            ->setLocationId($location)
            ->setServiceExtras($extras)
            ->setExcludeAppointmentId($id)
            ->setShowExistingTimeSlots(false)
            ->setCalledFromBackEnd(true);

        $calendarData = $calendarData->getCalendar();
        $data = $calendarData['dates'];

        if (isset($data[ $date ])) {
            foreach ($data[ $date ] as $dataInf) {
                $startTime = $dataInf['start_time_format'];

                if (!empty($search) && !str_contains($startTime, $search)) {
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

        return $dataForReturn;
    }
}
