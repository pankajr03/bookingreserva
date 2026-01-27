<?php

namespace BookneticApp\Backend\Payments\Exceptions;

class InvalidPaymentDataException extends PaymentException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?? bkntc__('Invalid data provided for payment operation.'));
    }
}
