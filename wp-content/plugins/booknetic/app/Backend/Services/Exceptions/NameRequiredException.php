<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class NameRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Name is required'));
    }
}
