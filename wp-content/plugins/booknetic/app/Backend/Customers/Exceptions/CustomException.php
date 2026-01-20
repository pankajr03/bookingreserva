<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
