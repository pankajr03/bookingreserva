<?php

namespace BookneticApp\Backend\Mobile\Exceptions;

use Exception;

class AppPasswordCreatingException extends Exception
{
    public function __construct()
    {
        parent::__construct('App password creation failed', 400);
    }
}
