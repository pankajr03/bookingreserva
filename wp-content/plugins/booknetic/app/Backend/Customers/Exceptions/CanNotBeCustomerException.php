<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CanNotBeCustomerException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Users with role of "Administrator" or "Staff" cannot be used as customers'));
    }
}
