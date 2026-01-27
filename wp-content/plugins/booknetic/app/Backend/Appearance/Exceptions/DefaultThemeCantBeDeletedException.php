<?php

namespace BookneticApp\Backend\Appearance\Exceptions;

use Exception;

use function bkntc__;

class DefaultThemeCantBeDeletedException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('You can not delete default theme!'));
    }
}
