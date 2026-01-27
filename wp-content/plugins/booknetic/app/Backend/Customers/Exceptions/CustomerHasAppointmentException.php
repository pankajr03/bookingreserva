<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CustomerHasAppointmentException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('The Customer has been added some Appointments. Firstly remove them!'));
    }
}
