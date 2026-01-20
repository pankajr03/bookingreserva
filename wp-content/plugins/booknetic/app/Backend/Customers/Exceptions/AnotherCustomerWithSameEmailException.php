<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class AnotherCustomerWithSameEmailException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('There is another Customer with the same email address!'));
    }
}
