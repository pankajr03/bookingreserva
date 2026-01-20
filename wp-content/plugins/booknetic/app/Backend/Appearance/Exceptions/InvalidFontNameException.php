<?php

namespace BookneticApp\Backend\Appearance\Exceptions;

use Exception;

use function bkntc__;

class InvalidFontNameException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Please enter the valid font-family name!'));
    }
}
