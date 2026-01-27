<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CustomerNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("Customer not found");
    }
}
