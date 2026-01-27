<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class MultipleFieldsDetectedExceptions extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Too many fields detected on CSV file!'));
    }
}
