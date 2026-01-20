<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class FillRequiredFieldsException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please fill in all required fields correctly!'));
    }
}
