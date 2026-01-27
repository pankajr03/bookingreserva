<?php

namespace BookneticApp\Backend\Customers\Exceptions;

use Exception;

class AlreadyConnectedUserException extends Exception
{
    public function __construct($id)
    {
        parent::__construct(bkntc__('This wordpress user is already connected to another booknetic customer (ID: %d)', [$id]));
    }
}
