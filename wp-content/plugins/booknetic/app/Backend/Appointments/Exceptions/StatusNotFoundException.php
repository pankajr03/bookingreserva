<?php

namespace BookneticApp\Backend\Appointments\Exceptions;

use Exception;

class StatusNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Selected Status not found');
    }
}
