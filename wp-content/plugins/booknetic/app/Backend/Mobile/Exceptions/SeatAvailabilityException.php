<?php

namespace BookneticApp\Backend\Mobile\Exceptions;

class SeatAvailabilityException extends \Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__("Seat Availability Error"));
    }
}
