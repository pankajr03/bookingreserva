<?php

namespace BookneticApp\Backend\Payments\Exceptions;

class PaymentProcessingException extends PaymentException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?? bkntc__('An error occurred while processing the payment.'));
    }
}
