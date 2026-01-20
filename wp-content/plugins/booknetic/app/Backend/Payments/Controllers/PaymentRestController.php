<?php

namespace BookneticApp\Backend\Payments\Controllers;

use BookneticApp\Backend\Appointments\Mappers\AppointmentSmartObjectMapper;
use BookneticApp\Backend\Payments\DTOs\SavePaymentRequest;
use BookneticApp\Backend\Payments\Exceptions\AppointmentNotFoundForPaymentException;
use BookneticApp\Backend\Payments\Exceptions\InvalidPaymentDataException;
use BookneticApp\Backend\Payments\Exceptions\PaymentProcessingException;
use BookneticApp\Backend\Payments\Services\PaymentService;
use BookneticApp\Providers\Core\RestRequest;

class PaymentRestController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @throws AppointmentNotFoundForPaymentException
     */
    public function getInfo(RestRequest $request): array
    {
        $appointmentId = $request->param('id', 0, RestRequest::TYPE_INTEGER);

        $appointmentInfo = $this->paymentService->getAppointmentInfo($appointmentId);

        if (! empty($appointmentInfo)) {
            $appointmentInfo = AppointmentSmartObjectMapper::toArray($appointmentInfo);
        }

        return [
            'data' => $appointmentInfo
        ];
    }

    /**
     * @throws InvalidPaymentDataException
     * @throws InvalidPaymentDataException
     * @throws PaymentProcessingException
     * @throws AppointmentNotFoundForPaymentException
     */
    public function editPaymentInfo(RestRequest $request): array
    {
        $appointmentId = $request->param('id', 0, RestRequest::TYPE_INTEGER);
        $pricesInput = $request->param('prices', [], RestRequest::TYPE_ARRAY);
        $paidAmount = $request->param('paid_amount', null, RestRequest::TYPE_FLOAT);
        $status = $request->param('status', '', RestRequest::TYPE_STRING, ['pending', 'paid', 'canceled', 'not_paid']);

        $savePaymentRequest = new SavePaymentRequest();
        $savePaymentRequest->setAppointmentId($appointmentId)
            ->setPrices($pricesInput)
            ->setStatus($status);

        if (!empty($paidAmount)) {
            $savePaymentRequest->setPaidAmount($paidAmount);
        }

        $this->paymentService->savePayment($savePaymentRequest);

        return [];
    }
}
