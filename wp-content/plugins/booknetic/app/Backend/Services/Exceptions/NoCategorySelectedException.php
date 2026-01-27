<?php

namespace BookneticApp\Backend\Services\Exceptions;

use Exception;

class NoCategorySelectedException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('No Category is selected'));
    }
}
