<?php

namespace BookneticApp\Backend\Mobile\Exceptions;

use Exception;

class NeedToSubscribeException extends Exception
{
    public function __construct()
    {
        parent::__construct(bkntc__('Firstly you need to subscribe'));
    }
}
