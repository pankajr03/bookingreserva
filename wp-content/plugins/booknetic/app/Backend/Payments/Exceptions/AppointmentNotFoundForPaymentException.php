<?php

namespace BookneticApp\Backend\Payments\Exceptions;

class AppointmentNotFoundForPaymentException extends PaymentException
{
    public function __construct(int $id)
    {
        parent::__construct(bkntc__('Appointment with id %d not found', [$id]));
    }
}
