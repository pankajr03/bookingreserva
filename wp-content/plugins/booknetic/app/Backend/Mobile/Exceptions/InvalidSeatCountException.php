<?php

namespace BookneticApp\Backend\Mobile\Exceptions;

use Exception;

class InvalidSeatCountException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Seat count should be greater than 0'));
    }
}
