<?php

namespace BookneticApp\Backend\Mobile\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct($message = null)
    {
        $message = $message ? $message : bkntc__('Something went wrong');
        parent::__construct($message);
    }
}
