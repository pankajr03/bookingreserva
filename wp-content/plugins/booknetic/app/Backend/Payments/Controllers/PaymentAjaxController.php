<?php

namespace BookneticApp\Backend\Payments\Controllers;

use BookneticApp\Backend\Payments\DTOs\SavePaymentRequest;
use BookneticApp\Backend\Payments\Exceptions\AppointmentNotFoundForPaymentException;
use BookneticApp\Backend\Payments\Exceptions\InvalidPaymentDataException;
use BookneticApp\Backend\Payments\Exceptions\PaymentProcessingException;
use BookneticApp\Backend\Payments\Services\PaymentService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Request\Post;

class PaymentAjaxController extends Controller
{
    private PaymentService $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    /**
     * @throws CapabilitiesException
     * @throws AppointmentNotFoundForPaymentException
     */
    public function info()
    {
        Capabilities::must('payments');

        $appointmentId = Post::int('id');
        $info = $this->service->getAppointmentInfo($appointmentId);

        return $this->modalView('info', ['info' => $info]);
    }

    /**
     * @throws CapabilitiesException
     * @throws AppointmentNotFoundForPaymentException
     */
    public function edit_payment()
    {
        Capabilities::must('payments_edit');
        $appointmentId = Post::int('payment');
        $mn2 = Post::int('mn2');
        $info = $this->service->getAppointmentInfo($appointmentId);

        return $this->modalView('edit_payment', ['payment' => $info, 'mn2' => $mn2]);
    }

    /**
     * @throws CapabilitiesException
     * @throws AppointmentNotFoundForPaymentException
     * @throws InvalidPaymentDataException
     * @throws PaymentProcessingException
     */
    public function save_payment()
    {
        Capabilities::must('payments_edit');

        $appointmentId = Post::int('id');
        $pricesInput = Post::json('prices');
        $paidAmount = Post::float('paid_amount');
        $status = Post::string('status', '', ['pending', 'paid', 'canceled', 'not_paid']);

        $savePaymentRequest = new SavePaymentRequest();
        $savePaymentRequest->setAppointmentId($appointmentId)
            ->setPrices($pricesInput)
            ->setPaidAmount($paidAmount)
            ->setStatus($status);

        $this->service->savePayment($savePaymentRequest);

        return $this->response(true, ['message' => bkntc__('Payment saved successfully!')]);
    }

    /**
     * @throws CapabilitiesException
     * @throws AppointmentNotFoundForPaymentException
     * @throws InvalidPaymentDataException
     * @throws PaymentProcessingException
     */
    public function complete_payment()
    {
        Capabilities::must('payments_edit');
        $appointmentId = Post::int('id');

        $this->service->completePayment($appointmentId);

        return $this->response(true, ['message' => bkntc__('Payment completed successfully!')]);
    }
}
