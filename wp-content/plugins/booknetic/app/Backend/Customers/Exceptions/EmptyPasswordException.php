<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class EmptyPasswordException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please type the password of the WordPress user!'));
    }
}
