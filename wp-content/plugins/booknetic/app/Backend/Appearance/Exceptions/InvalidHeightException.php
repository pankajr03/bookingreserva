<?php

namespace BookneticApp\Backend\Appearance\Exceptions;

use Exception;

use function bkntc__;

class InvalidHeightException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please enter the valid value for the Height field!'));
    }
}
