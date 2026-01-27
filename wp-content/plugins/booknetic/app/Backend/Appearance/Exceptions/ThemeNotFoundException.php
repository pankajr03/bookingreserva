<?php

namespace BookneticApp\Backend\Appearance\Exceptions;

use Exception;

use function bkntc__;

class ThemeNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Theme not found!'));
    }
}
