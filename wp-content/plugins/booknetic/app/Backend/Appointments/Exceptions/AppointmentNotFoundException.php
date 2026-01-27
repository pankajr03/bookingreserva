<?php

namespace BookneticApp\Backend\Appointments\Exceptions;

use Exception;

class AppointmentNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Appointment not found');
    }
}
