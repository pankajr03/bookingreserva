<?php

namespace BookneticApp\Backend\Appearance\Exceptions;

use Exception;

use function bkntc__;

class RequiredFieldMissingException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please fill in all required fields correctly!'));
    }
}
