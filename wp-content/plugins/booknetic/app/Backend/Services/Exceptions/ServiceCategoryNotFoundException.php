<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class ServiceCategoryNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__("Service Category Not Found"));
    }
}
