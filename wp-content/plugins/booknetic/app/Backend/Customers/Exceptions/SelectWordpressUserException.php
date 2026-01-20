<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class SelectWordpressUserException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please select WordPress user!'));
    }
}
