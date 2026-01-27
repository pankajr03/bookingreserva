<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class InvalidCustomerCategoryDataException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
