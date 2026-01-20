<?php

namespace BookneticApp\Backend\Payments\Services;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Backend\Payments\DTOs\SavePaymentRequest;
use BookneticApp\Backend\Payments\Exceptions\AppointmentNotFoundForPaymentException;
use BookneticApp\Backend\Payments\Exceptions\InvalidPaymentDataException;
use BookneticApp\Backend\Payments\Exceptions\PaymentProcessingException;
use BookneticApp\Backend\Payments\Repositories\PaymentRepository;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\DB\QueryBuilder;
use BookneticApp\Providers\Helpers\Math;
use Exception;

class PaymentService
{
    private PaymentRepository $repository;

    public function __construct(PaymentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @throws AppointmentNotFoundForPaymentException
     */
    public function getAppointmentInfo(int $id): ?AppointmentSmartObject
    {
        $info = AppointmentSmartObject::load($id);

        if (!$info->validate()) {
            throw new AppointmentNotFoundForPaymentException($id);
        }

        return $info;
    }

    /**
     * @throws InvalidPaymentDataException | AppointmentNotFoundForPaymentException | PaymentProcessingException
     */
    public function savePayment(SavePaymentRequest $request): bool
    {
        $appointmentId = $request->getAppointmentId();
        $pricesInput = $request->getPrices();
        $paidAmount = $request->getPaidAmount();
        $status = $request->getStatus();

        $info = $this->getAppointmentInfo($appointmentId);

        if (count($info->getPrices()) != count($pricesInput) && count($pricesInput) > 0) {
            throw new InvalidPaymentDataException();
        }

        $isUpdated = false;

        foreach ($pricesInput as $priceUniqueKey => $priceValue) {
            $originalPrice = $info->getPrice($priceUniqueKey);
            if (!$originalPrice || !is_numeric($priceValue) || $priceValue < 0) {
                throw new InvalidPaymentDataException();
            }

            if (Math::floor($priceValue) !== Math::floor($originalPrice->price)) {
                $isUpdated = true;
            }
        }

        if ($isUpdated) {
            Appointment::setData($info->getId(), 'price_updated', 1);
        }

        try {
            foreach ($pricesInput as $priceUniqueKey => $priceValue) {
                AppointmentPrice::where('appointment_id', $appointmentId)
                    ->where('unique_key', $priceUniqueKey)
                    ->update(['price' => Math::floor($priceValue)]);
            }

            $dataToUpdate = [
                'payment_status' => $status
            ];

            if (!empty($paidAmount)) {
                $dataToUpdate['paid_amount'] = $paidAmount;
            }

            Appointment::query()->where('id', $appointmentId)->update($dataToUpdate);

            if ($status == 'paid') {
                do_action('bkntc_payment_confirmed_backend', $appointmentId);
            }

            return true;
        } catch (Exception $e) {
            throw new PaymentProcessingException();
        }
    }

    /**
     * @throws AppointmentNotFoundForPaymentException
     * @throws PaymentProcessingException
     * @throws InvalidPaymentDataException
     */
    public function completePayment(int $appointmentId): bool
    {
        if ($appointmentId <= 0) {
            throw new InvalidPaymentDataException(bkntc__('Invalid Appointment ID provided.'));
        }

        $info = $this->getAppointmentInfo($appointmentId);

        try {
            Appointment::where('id', $appointmentId)->update([
                'payment_status' => 'paid',
                'paid_amount' => $info->getTotalAmount()
            ]);

            $appointment = Appointment::get($appointmentId);

            do_action('bkntc_payment_confirmed_backend', $appointment->id);

            Config::getWorkflowEventsManager()->trigger('appointment_paid', [
                'appointment_id' => $appointment->id,
                'location_id' => $appointment->location_id,
                'service_id' => $appointment->service_id,
                'staff_id' => $appointment->staff_id,
                'customer_id' => $appointment->customer_id
            ]);

            return true;
        } catch (Exception $e) {
            throw new PaymentProcessingException();
        }
    }

    public function getPaymentsDataTableQuery(): QueryBuilder
    {
        return $this->repository->getPaymentsForDataTableQuery();
    }
}
