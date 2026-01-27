<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class ServiceCategoryParentCannotBeSelfException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__("A Service Category cannot be its own parent."));
    }
}
