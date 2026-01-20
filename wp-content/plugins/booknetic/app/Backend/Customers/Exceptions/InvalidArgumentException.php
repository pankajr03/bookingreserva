<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class InvalidArgumentException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('The WordPress user you are trying to associate with this customer does not have the Booknetic Customer role!'));
    }
}
