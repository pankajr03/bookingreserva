<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class CanNotChangeEmailOrPasswordException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('You cannot change user email or password, because this customer has been used on another tenant also'));
    }
}
